<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\AccommodationProviderMap;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripAvailabilityRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCalendarWindowRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripRefreshHorizonRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarRangeLimit;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarWindows;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class RefreshAvailability
{
    public function __construct(
        private readonly ProviderOutboundGuard $outboundGuard,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripAvailabilityRepository $availability,
        private readonly SnappTripCalendarWindowRepository $calendarWindows,
        private readonly SnappTripRefreshHorizonRepository $refreshHorizons,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
    ) {
    }

    /** @return Collection<int,array<string,mixed>> */
    public function execute(
        AccommodationProviderMap $map,
        string $from,
        string $to,
        bool $persist = true,
    ): Collection {
        $provider = $this->outboundGuard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
        if ((int) $map->provider_id !== (int) $provider->id || trim((string) $map->provider_property_id) === '') {
            throw new InvalidArgumentException('SnappTrip availability requires a valid SnappTrip accommodation map.');
        }
        if ($map->is_disabled) {
            return collect();
        }

        $startDate = CarbonImmutable::parse($from)->startOfDay();
        $requestedEndDate = CarbonImmutable::parse($to)->startOfDay();
        if (!$startDate->lt($requestedEndDate)) {
            throw new InvalidArgumentException('SnappTrip availability end date must be after start date.');
        }

        $endDate = $requestedEndDate;
        $horizonOverride = $this->refreshHorizons->overrideDays($map);
        if ($horizonOverride !== null) {
            $configuredEnd = $startDate->addDays($horizonOverride);
            if ($configuredEnd->lt($endDate)) {
                $endDate = $configuredEnd;
            }
        }

        $start = $startDate->toDateString();
        $end = $endDate->toDateString();
        $gateway = $this->gateways->make($provider)->withProviderAlertContext(
            (int) $map->accommodation_id,
            (string) $map->provider_property_id,
        );

        $resultRows = collect();
        $windowDays = $this->calendarWindows->windowDays($map);
        $windows = SnappTripCalendarWindows::split($start, $end, $windowDays);

        while ($windows !== []) {
            $window = array_shift($windows);

            try {
                $domestic = $gateway->hotelCalendar(
                    (string) $map->provider_property_id,
                    $window['from'],
                    $window['to'],
                    false,
                );
                $foreign = $gateway->hotelCalendar(
                    (string) $map->provider_property_id,
                    $window['from'],
                    $window['to'],
                    true,
                );
            } catch (RequestException $exception) {
                if ($exception->response?->status() === 404) {
                    $this->maps->disableForAccommodationAndProvider(
                        (int) $map->accommodation_id,
                        (int) $provider->id,
                    );
                    return collect();
                }

                $reportedLimit = SnappTripCalendarRangeLimit::fromResponse($exception->response);
                $windowFrom = CarbonImmutable::parse($window['from'])->startOfDay();
                $windowTo = CarbonImmutable::parse($window['to'])->startOfDay();
                $currentWindowDays = (int) $windowFrom->diffInDays($windowTo);

                if ($reportedLimit !== null && $reportedLimit < $currentWindowDays) {
                    $windowDays = $persist
                        ? $this->calendarWindows->learn($map, $reportedLimit)
                        : min($windowDays, $reportedLimit, SnappTripCalendarWindows::MAX_DAYS);

                    // First treat a smaller reported limit as an endpoint window cap.
                    // If the provider also has a forward-horizon cap, a later smaller
                    // chunk will expose that separately and we learn it below.
                    $windows = SnappTripCalendarWindows::split(
                        $window['from'],
                        $end,
                        $windowDays,
                    );
                    continue;
                }

                if ($reportedLimit !== null) {
                    $reportedEnd = $startDate->addDays($reportedLimit);

                    if ($reportedEnd->lt($endDate) && $windowTo->gt($reportedEnd)) {
                        $horizonDays = $persist
                            ? $this->refreshHorizons->learn($map, $reportedLimit)
                            : $reportedLimit;
                        $learnedEnd = $startDate->addDays($horizonDays);

                        if ($learnedEnd->lt($endDate)) {
                            $endDate = $learnedEnd;
                            $end = $endDate->toDateString();
                        }

                        if (!$windowFrom->lt($endDate)) {
                            $windows = [];
                            continue;
                        }

                        // Retry only the useful remainder of the failed window. The
                        // already-persisted earlier windows are never requested again.
                        $windows = SnappTripCalendarWindows::split(
                            $window['from'],
                            $end,
                            $windowDays,
                        );
                        continue;
                    }
                }

                throw $exception;
            }

            if (!$persist) {
                $resultRows->push([
                    'from' => $window['from'],
                    'to' => $window['to'],
                    'foreigner' => false,
                    'data' => $domestic,
                ]);
                $resultRows->push([
                    'from' => $window['from'],
                    'to' => $window['to'],
                    'foreigner' => true,
                    'data' => $foreign,
                ]);
                continue;
            }

            $resultRows = $resultRows->concat($this->availability->persist(
                $provider,
                $map,
                $domestic['rows'] ?? [],
                $domestic['packages'] ?? [],
                false,
            ));
            $resultRows = $resultRows->concat($this->availability->persist(
                $provider,
                $map,
                $foreign['rows'] ?? [],
                $foreign['packages'] ?? [],
                true,
            ));
        }

        return $resultRows->values();
    }
}
