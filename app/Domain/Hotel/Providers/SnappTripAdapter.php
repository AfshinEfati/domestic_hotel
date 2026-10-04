<?php

namespace App\Domain\Hotel\Providers;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripBookingService;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripCancellationService;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripAvailabilityRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCatalogRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGateway;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Compatibility facade for legacy ProviderAdapterInterface consumers.
 * SnappTrip HTTP, mapping, quota and business integration live in the V2 module.
 */
class SnappTripAdapter extends BaseAdapter implements ProviderAdapterInterface
{
    public function __construct(
        Provider $provider,
        private readonly SnappTripGatewayFactory $gateways,
        private readonly SnappTripCatalogRepository $catalog,
        private readonly SnappTripAvailabilityRepository $availability,
        private readonly AccommodationProviderMapRepositoryInterface $maps,
        private readonly SnappTripBookingService $booking,
        private readonly SnappTripCancellationService $cancellations,
    ) {
        parent::__construct($provider);
    }

    public function code(): string
    {
        return SnappTripSettings::PROVIDER_CODE;
    }

    public function authenticate(): void
    {
        $apiKey = trim((string) data_get(SnappTripSettings::from($this->provider), 'api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('SnappTrip provider is missing api_key.');
        }
    }

    public function fetchCities(): Collection
    {
        return $this->gateway()->cities();
    }

    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 1000): Collection
    {
        $count = max(1, min(1000, $count));
        $offset = max(0, ($page - 1) * $count);

        return $this->gateway()->cityHotels($providerCityId, $count, $offset);
    }

    public function fetchProperties(): Collection
    {
        $gateway = $this->gateway();
        $properties = collect();

        foreach ($gateway->cities() as $city) {
            $offset = 0;
            do {
                $batch = $gateway->cityHotels((string) $city['id'], 100, $offset);
                $properties = $properties->concat($batch);
                $offset += $batch->count();
            } while ($batch->count() === 100);
        }

        return $properties->unique('provider_property_id')->values();
    }

    public function fetchRoomTypes(string $providerPropertyId): ?Collection
    {
        return collect($this->gateway()->rooms([$providerPropertyId])->get($providerPropertyId, []));
    }

    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        return collect($this->gateway()->rooms([$providerPropertyId])->get($providerPropertyId, []))
            ->map(function (array $room): array {
                $board = trim((string) ($room['board_type'] ?? 'room_only')) ?: 'room_only';

                return [
                    'id' => "board:{$board}:domestic",
                    'name' => $board,
                    'name_en' => $board,
                    'meal_type_included' => match ($board) {
                        'bed_breakfast' => 'breakfast',
                        'half_board' => 'half_board',
                        'full_board' => 'full_board',
                        default => null,
                    },
                    'cancelable' => true,
                ];
            })
            ->unique('id')
            ->values();
    }

    public function fetchAvailability(
        string $providerPropertyId,
        DateTimeInterface $from,
        DateTimeInterface $to,
    ): Collection {
        $map = $this->maps->findForProviderProperty((int) $this->provider->id, $providerPropertyId);
        if ($map === null || $map->is_disabled) {
            return collect();
        }

        $start = CarbonImmutable::parse($from)->toDateString();
        $end = CarbonImmutable::parse($to)->toDateString();
        $gateway = $this->gateway();
        $output = collect();

        foreach ([false, true] as $foreigner) {
            $calendar = $gateway->hotelCalendar($providerPropertyId, $start, $end, $foreigner);
            $live = $gateway->hotelAvailability($providerPropertyId, $start, $end, $foreigner);
            $liveByRoom = collect($live['rows'] ?? [])->keyBy(
                fn (array $row): string => (string) ($row['provider_room_type_id'] ?? ''),
            );

            $persisted = $this->availability->persist(
                $this->provider,
                $map,
                $calendar['rows'] ?? [],
                array_merge($calendar['packages'] ?? [], $live['packages'] ?? []),
                $foreigner,
            );

            $output = $output->concat($persisted->map(function (array $row) use ($liveByRoom): array {
                $live = $liveByRoom->get((string) ($row['room_type_id'] ?? ''));
                if (is_array($live)) {
                    $row['stay_total_rate'] = $live['price'] ?? null;
                    $row['stay_rack_rate'] = $live['original_sell_price'] ?? null;
                    $row['stay_child_rate'] = $live['child_price'] ?? null;
                    $row['stay_extra_bed_rate'] = $live['extra_bed_price'] ?? null;
                }

                return $row;
            }));
        }

        return $output->values();
    }

    public function reserve(array $payload): array
    {
        return $this->booking->create($payload);
    }

    public function extendExpire(string $reserveId): array
    {
        $this->booking->lock($reserveId);

        return ['reservation_code' => $reserveId, 'locked' => true];
    }

    public function book(array $payload): array
    {
        $code = trim((string) ($payload['reservation_code'] ?? $payload['reserve_id'] ?? $payload['code'] ?? ''));
        if ($code === '') {
            throw new RuntimeException('SnappTrip booking confirmation requires reservation_code.');
        }

        return $this->booking->confirm($code);
    }

    public function modify(array $payload): array
    {
        throw new RuntimeException('SnappTrip does not expose a booking modify endpoint in API v2.');
    }

    public function cancel(array $payload): array
    {
        $code = trim((string) ($payload['tracking_code'] ?? $payload['reservation_code'] ?? ''));
        if ($code === '') {
            throw new RuntimeException('SnappTrip cancellation requires tracking_code.');
        }

        $action = strtolower(trim((string) ($payload['action'] ?? 'create')));

        return match ($action) {
            'create' => $this->cancellations->create($code, isset($payload['reservation_purchase_id']) ? (int) $payload['reservation_purchase_id'] : null)->toArray(),
            'inquiry' => $this->cancellations->inquire($code)->toArray(),
            'accept' => $this->cancellations->accept($code)->toArray(),
            'reject' => $this->cancellations->reject($code)->toArray(),
            default => throw new RuntimeException("Unsupported SnappTrip cancellation action: {$action}"),
        };
    }

    public function reservesList(array $filters = []): Collection
    {
        return collect();
    }

    public function reserveDetails(string $reserveId): array
    {
        return $this->booking->status($reserveId);
    }

    public function supportedWebhooks(): array
    {
        return [];
    }

    public function fetchFacilities(): Collection
    {
        $output = collect();
        foreach ($this->catalog->mappedHotels($this->provider)->chunk(10) as $chunk) {
            $ids = $chunk->pluck('provider_property_id')->map(fn ($id): string => (string) $id)->all();
            $output = $output->merge($this->gateway()->facilities($ids));
        }

        return $output;
    }

    private function gateway(): SnappTripGateway
    {
        $gateway = $this->gateways->make($this->provider);
        $context = $this->requestLogContext;
        if ($context === null) {
            return $gateway;
        }

        return $gateway->withRequestLogContext(
            reservationId: $context['reservation_id'] ?? null,
            handlerClass: $context['handler_class'] ?? null,
            handlerMethod: $context['handler_method'] ?? null,
            attempt: (int) ($context['attempt'] ?? 1),
            force: (bool) ($context['force'] ?? false),
        );
    }
}
