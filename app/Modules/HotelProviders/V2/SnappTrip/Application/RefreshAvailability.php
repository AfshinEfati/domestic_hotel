<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\AccommodationProviderMap;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripAvailabilityRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
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

        $start = CarbonImmutable::parse($from)->toDateString();
        $end = CarbonImmutable::parse($to)->toDateString();
        if ($start >= $end) {
            throw new InvalidArgumentException('SnappTrip availability end date must be after start date.');
        }

        $gateway = $this->gateways->make($provider);

        try {
            $domestic = $gateway->hotelCalendar((string) $map->provider_property_id, $start, $end, false);
            $foreign = $gateway->hotelCalendar((string) $map->provider_property_id, $start, $end, true);
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 404) {
                $this->maps->disableForAccommodationAndProvider((int) $map->accommodation_id, (int) $provider->id);
                return collect();
            }
            throw $exception;
        }

        if (!$persist) {
            return collect([
                ['foreigner' => false, 'data' => $domestic],
                ['foreigner' => true, 'data' => $foreign],
            ]);
        }

        $rows = $this->availability->persist(
            $provider,
            $map,
            $domestic['rows'] ?? [],
            $domestic['packages'] ?? [],
            false,
        );
        $rows = $rows->concat($this->availability->persist(
            $provider,
            $map,
            $foreign['rows'] ?? [],
            $foreign['packages'] ?? [],
            true,
        ));

        return $rows->values();
    }
}
