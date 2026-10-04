<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Http;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripApiQuota;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class SnappTripClient
{
    /** @var array{reservation_id:?int,handler_class:?string,handler_method:?string,attempt:int,force:bool}|null */
    private ?array $requestLogContext = null;

    public function __construct(
        private readonly Provider $provider,
        private readonly ProviderOutboundGuard $outboundGuard,
    ) {
    }

    public function provider(): Provider
    {
        return $this->provider;
    }

    public function withRequestLogContext(
        ?int $reservationId = null,
        ?string $handlerClass = null,
        ?string $handlerMethod = null,
        int $attempt = 1,
        bool $force = false,
    ): self {
        $clone = clone $this;
        $clone->requestLogContext = [
            'reservation_id' => $reservationId,
            'handler_class' => $handlerClass,
            'handler_method' => $handlerMethod,
            'attempt' => max(1, $attempt),
            'force' => $force,
        ];

        return $clone;
    }

    public function cities(): array
    {
        return $this->get('/cities/');
    }

    public function cityHotels(int|string $cityId, int $limit = 100, int $offset = 0): array
    {
        return $this->get('/cities/'.rawurlencode((string) $cityId).'/hotels', [
            'limit' => max(1, $limit),
            'offset' => max(0, $offset),
        ]);
    }

    public function hotels(array $hotelIds): array
    {
        return $this->get('/hotels/', ['id' => $this->idList($hotelIds, 10)]);
    }

    public function facilities(array $hotelIds): array
    {
        return $this->get('/hotels/facilities', ['id' => $this->idList($hotelIds, 10)]);
    }

    public function galleries(array $hotelIds): array
    {
        return $this->getOptional('/hotels/galleries', ['id' => $this->idList($hotelIds, 10)], [404]);
    }

    public function reviews(array $hotelIds): array
    {
        return $this->getOptional('/hotels/reviews', ['id' => $this->idList($hotelIds, 10)], [403, 404]);
    }

    public function rooms(array $hotelIds): array
    {
        return $this->get('/hotels/rooms', ['id' => $this->idList($hotelIds, 10)]);
    }

    public function cityAvailability(array $payload): array
    {
        return $this->post('/availability/cities', $payload);
    }

    public function hotelsAvailability(
        array $hotelIds,
        string $checkIn,
        string $checkOut,
        bool $foreigner = false,
    ): array {
        return $this->get('/availability/hotels', [
            'id' => $this->idList($hotelIds, 50),
            'checkin' => $checkIn,
            'checkout' => $checkOut,
            'foreigner' => $foreigner ? 'true' : 'false',
        ]);
    }

    public function hotelAvailability(
        int|string $hotelId,
        string $checkIn,
        string $checkOut,
        bool $foreigner = false,
    ): array {
        return $this->get('/availability/hotels/'.rawurlencode((string) $hotelId), [
            'checkin' => $checkIn,
            'checkout' => $checkOut,
            'foreigner' => $foreigner ? 'true' : 'false',
        ]);
    }

    public function hotelCalendar(
        int|string $hotelId,
        string $from,
        string $to,
        bool $foreigner = false,
    ): array {
        return $this->get('/availability/hotels/'.rawurlencode((string) $hotelId).'/calendar', [
            'from' => $from,
            'to' => $to,
            'foreigner' => $foreigner ? 'true' : 'false',
        ]);
    }

    public function roomCalendar(
        int|string $hotelId,
        int|string $roomId,
        string $from,
        string $to,
        bool $foreigner = false,
    ): array {
        return $this->get(
            '/availability/hotels/'.rawurlencode((string) $hotelId)
            .'/room/'.rawurlencode((string) $roomId).'/calendar',
            [
                'from' => $from,
                'to' => $to,
                'foreigner' => $foreigner ? 'true' : 'false',
            ],
        );
    }

    public function balance(): array
    {
        return $this->get('/balance/');
    }

    public function createBooking(array $payload): array
    {
        return $this->post('/booking/create', $payload);
    }

    public function booking(string $reservationCode): array
    {
        return $this->get('/booking/'.rawurlencode($reservationCode));
    }

    public function lockBooking(string $reservationCode): void
    {
        $this->request('POST', '/booking/'.rawurlencode($reservationCode).'/lock');
    }

    public function confirmBooking(string $reservationCode): array
    {
        return $this->post('/booking/'.rawurlencode($reservationCode).'/confirm');
    }

    public function cancellationRules(
        int|string $hotelId,
        int|string $roomId,
        string $checkIn,
        string $checkOut,
    ): array {
        return $this->get(
            '/cancellation/hotels/'.rawurlencode((string) $hotelId)
            .'/rooms/'.rawurlencode((string) $roomId).'/rules',
            ['checkin' => $checkIn, 'checkout' => $checkOut],
        );
    }

    public function createCancellation(string $trackingCode): array
    {
        return $this->post('/cancellation/'.rawurlencode($trackingCode));
    }

    public function cancellationInquiry(string $trackingCode): array
    {
        return $this->get('/cancellation/'.rawurlencode($trackingCode).'/inquiry');
    }

    public function acceptCancellation(string $trackingCode): void
    {
        $this->request('PATCH', '/cancellation/'.rawurlencode($trackingCode).'/accept');
    }

    public function rejectCancellation(string $trackingCode): void
    {
        $this->request('PATCH', '/cancellation/'.rawurlencode($trackingCode).'/reject');
    }

    public function health(): array
    {
        return $this->get('/health');
    }

    private function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, query: $query);
    }

    private function getOptional(string $path, array $query, array $acceptedErrorStatuses): array
    {
        return $this->request('GET', $path, query: $query, acceptedErrorStatuses: $acceptedErrorStatuses);
    }

    private function post(string $path, array $json = []): array
    {
        return $this->request('POST', $path, json: $json);
    }

    private function request(
        string $method,
        string $path,
        array $query = [],
        array $json = [],
        array $acceptedErrorStatuses = [],
    ): array {
        $this->outboundGuard->assertAllowed($this->provider);
        $settings = SnappTripSettings::from($this->provider);
        $baseUrl = trim((string) ($settings['base_url'] ?? ''));
        $apiKey = trim((string) ($settings['api_key'] ?? ''));

        if ($baseUrl === '' || $apiKey === '') {
            throw new RuntimeException('SnappTrip requires base_url and api_key in provider config.');
        }

        SnappTripApiQuota::acquire($this->provider);

        $options = [];
        if ($query !== []) {
            $options['query'] = $query;
        }
        if ($json !== []) {
            $options['json'] = $json;
        }

        $response = $this->http($baseUrl, $apiKey)->send($method, $path, $options);

        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');
            SnappTripApiQuota::registerProvider429(
                $this->provider,
                is_numeric($retryAfter) ? (int) $retryAfter : null,
            );
        }

        if (in_array($response->status(), $acceptedErrorStatuses, true)) {
            return [];
        }

        $response->throw();

        if ($response->status() === 204 || trim($response->body()) === '') {
            return [];
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    private function http(string $baseUrl, string $apiKey): PendingRequest
    {
        return Http::withHeaders([
            'api-key' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withAttributes([
            'domestic_provider' => [
                'id' => (int) $this->provider->id,
                'code' => (string) $this->provider->code,
                'log' => $this->requestLogMetadata(),
            ],
        ])->baseUrl(rtrim($baseUrl, '/'))->timeout(30);
    }

    private function requestLogMetadata(): array
    {
        $context = $this->requestLogContext ?? [];
        $reservationId = isset($context['reservation_id']) ? (int) $context['reservation_id'] : null;

        return [
            'enabled' => ($context['force'] ?? false) === true || $reservationId !== null,
            'reservation_id' => $reservationId,
            'handler_class' => $context['handler_class'] ?? static::class,
            'handler_method' => $context['handler_method'] ?? null,
            'attempt' => max(1, (int) ($context['attempt'] ?? 1)),
            'started_at' => now()->toISOString(),
            'started_microtime' => microtime(true),
        ];
    }

    private function idList(array $ids, int $max): string
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $ids,
        ), static fn (string $id): bool => $id !== '')));

        if ($normalized === [] || count($normalized) > $max) {
            throw new InvalidArgumentException("SnappTrip request requires between 1 and {$max} IDs.");
        }

        return implode(',', $normalized);
    }
}
