<?php

namespace App\Domain\Hotel\V2;

use App\Domain\Hotel\Providers\GRSAdapter;
use Closure;
use DateTimeInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/** Availability traffic participates in the shared provider-wide GRS background quota. */
class RateLimitedGrsAdapter extends GRSAdapter
{
    public ?Collection $lastAvailability = null;
    public ?\Throwable $supplementalError = null;

    /**
     * Last GRS availability HTTP exchange without authentication headers.
     *
     * @var array<string, mixed>|null
     */
    public ?array $lastAvailabilityExchange = null;

    private ?Closure $availabilityStarting = null;
    private ?Closure $availabilitySucceeded = null;

    public function trackAvailability(Closure $starting, Closure $succeeded): void
    {
        $this->availabilityStarting = $starting;
        $this->availabilitySucceeded = $succeeded;
    }

    public function fetchAvailability(string $providerPropertyId, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $this->acquireQuota();

        try {
            // Quota is acquired before recording the actual HTTP attempt.
            if ($this->availabilityStarting !== null) {
                ($this->availabilityStarting)();
            }

            $this->authenticate();

            $query = [
                'property_id' => $providerPropertyId,
                'check_in' => $from->format('Y-m-d'),
                'check_out' => $to->format('Y-m-d'),
            ];

            $this->lastAvailabilityExchange = [
                'method' => 'GET',
                'endpoint' => $this->baseUrl.'/v1/available-rooms',
                'request' => [
                    'query' => $query,
                ],
                'response' => null,
            ];

            $response = $this->client()->get('/v1/available-rooms', $query);
            $decoded = $response->json();

            $this->lastAvailabilityExchange['response'] = [
                'status' => $response->status(),
                'body' => is_array($decoded) ? $decoded : $response->body(),
            ];

            $response->throw();

            $payload = is_array($decoded) ? $decoded : [];
            $rooms = data_get($payload, 'value.rooms', []);

            $availability = collect($rooms)->flatMap(function ($room) {
                $propertyId = (string) data_get($room, 'property_id', '');
                $roomTypeId = (string) data_get($room, 'room_type_id', '');
                $ratePlans = data_get($room, 'rate_plans', []);

                return collect($ratePlans)->flatMap(function ($ratePlan) use ($propertyId, $roomTypeId) {
                    $ratePlanId = (string) data_get($ratePlan, 'id', '');
                    $ratePlanName = data_get($ratePlan, 'name', '');
                    $prices = data_get($ratePlan, 'prices', []);

                    return collect($prices)->map(fn ($price) => [
                        'day' => data_get($price, 'day'),
                        'inventory' => data_get($price, 'inventory'),
                        'rack_rate' => data_get($price, 'rack_rate'),
                        'daily_rate' => data_get($price, 'daily_rate'),
                        'grs_rate' => data_get($price, 'grs_rate'),
                        'min_stay' => data_get($price, 'min_stay'),
                        'max_stay' => data_get($price, 'max_stay'),
                        'cta' => (bool) data_get($price, 'close_to_arrival', false),
                        'ctd' => (bool) data_get($price, 'close_to_departure', false),
                        'closed' => (bool) data_get($price, 'closed', false),
                        'room_type_id' => $roomTypeId,
                        'rate_plan_id' => $ratePlanId,
                        'rate_plan_name' => $ratePlanName,
                        'provider_property_id' => $propertyId,
                    ]);
                });
            });

            // Reaching this point means the provider returned an HTTP success.
            if ($this->availabilitySucceeded !== null) {
                ($this->availabilitySucceeded)();
            }

            return $this->lastAvailability = $availability;
        } catch (RequestException $e) {
            $this->handleHttpError($e);
            throw $e;
        }
    }

    public function fetchRoomTypes(string $providerPropertyId): Collection
    {
        try {
            $this->acquireQuota();
            $rooms = parent::fetchRoomTypes($providerPropertyId);
            // Catalog metadata belongs to the separate hotel catalog flow.
            return $rooms->map(static function (array $room): array {
                unset($room['property_facilities'], $room['property_rules']);
                return $room;
            });
        } catch (\Throwable $e) {
            // HotelSyncService catches supplemental exceptions. Remember them
            // so a partial refresh cannot be marked successful.
            $this->supplementalError = $e;
            if ($e instanceof RequestException) {
                $this->handleHttpError($e);
            }
            throw $e;
        }
    }

    public static function cooldownSeconds(): int
    {
        return GrsApiQuota::cooldownSeconds();
    }

    private function acquireQuota(): void
    {
        GrsApiQuota::acquire();
    }

    private function handleHttpError(RequestException $e): void
    {
        if ($e->response?->status() !== 429) {
            return;
        }

        // GRS does not provide a usable Retry-After value. A provider-side 429
        // pauses every background GRS request participating in the shared gate
        // for five minutes; no provider HTTP is sent while the cooldown is active.
        GrsApiQuota::registerProvider429();
    }
}
