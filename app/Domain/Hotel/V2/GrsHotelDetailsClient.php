<?php

namespace App\Domain\Hotel\V2;

use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** One GRS property-details request; never uses the availability/quota adapter. */
class GrsHotelDetailsClient
{
    public function fetch(Provider $provider, string $propertyId): array
    {
        if ($provider->code !== 'grs' || !$provider->is_active || !$provider->is_online) {
            throw new RuntimeException('GRS provider is missing, inactive or offline.');
        }

        $baseUrl = rtrim((string) data_get($provider->config, 'base_url', ''), '/');
        $token = (string) data_get($provider->config, 'token', '');
        if ($baseUrl === '' || $token === '' || !ctype_digit($propertyId)) {
            throw new RuntimeException('GRS details require a configured API and a numeric mapped property ID.');
        }

        $response = Http::withAttributes([
            'domestic_provider' => [
                'id' => (int) $provider->id,
                'code' => 'grs',
                'log' => [
                    'enabled' => true,
                    'reservation_id' => null,
                    'handler_class' => self::class,
                    'handler_method' => __FUNCTION__,
                    'attempt' => 1,
                    'started_at' => now()->toISOString(),
                    'started_microtime' => microtime(true),
                ],
            ],
        ])
            ->withHeaders([
                'Client-Token' => $token,
                'Content-Type' => 'application/json',
            ])
            ->baseUrl($baseUrl)->timeout(35)
            ->get('/v1/properties/'.$propertyId);
        $response->throw();

        $body = $response->json();
        $property = data_get($body, 'value.property');
        if ((int) data_get($body, 'code') !== 200 || !is_array($property)
            || (string) ($property['id'] ?? '') !== $propertyId) {
            throw new RuntimeException('GRS details returned an invalid or mismatched property.');
        }

        // An incomplete response must not mark this hotel's details as refreshed.
        if (!isset($property['room_types']) || !is_array($property['room_types']) || $property['room_types'] === []) {
            throw new RuntimeException('GRS property details contain no room types.');
        }
        foreach (['facilities', 'rules'] as $field) {
            if (!array_key_exists($field, $property) || !is_array($property[$field])) {
                throw new RuntimeException('GRS property details missing '.$field.'.');
            }
        }

        return $property;
    }
}
