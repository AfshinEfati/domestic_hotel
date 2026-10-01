<?php

namespace App\Jobs\Hotel\V2;

use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncGrsHotelCatalogJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 80;
    public int $uniqueFor = 3600;

    private const API_COUNT = 10000000;
    private const BATCH_SIZE = 25;

    public function uniqueId(): string
    {
        return 'grs-hotel-catalog';
    }

    public function handle(): void
    {
        $provider = Provider::query()
            ->where('code', 'grs')
            ->firstOrFail();

        if (!$provider->is_active || !$provider->is_online) {
            return;
        }

        $baseUrl = rtrim(
            (string) data_get($provider->config, 'base_url', ''),
            '/'
        );

        $token = (string) data_get($provider->config, 'token', '');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException(
                'GRS hotel catalog requires a configured base_url and Client-Token.'
            );
        }

        // One catalog request. This workflow is mapping-only: it never creates
        // or updates canonical Accommodation rows.
        $response = Http::withAttributes([
            'domestic_provider' => [
                'id' => (int) $provider->id,
                'code' => 'grs',
                'log' => [
                    'enabled' => false,
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
            ->baseUrl($baseUrl)
            ->timeout(55)
            ->get('/v1/properties', [
                'page' => 1,
                'count' => self::API_COUNT,
            ]);

        $response->throw();

        $body = $response->json();
        $properties = data_get($body, 'value.properties');

        if (
            (int) data_get($body, 'code') !== 200
            || !is_array($properties)
        ) {
            throw new RuntimeException(
                'Invalid GRS properties response; no mappings were changed.'
            );
        }

        if ($properties === []) {
            throw new RuntimeException(
                'GRS returned an empty properties list; refusing an incomplete mapping sync.'
            );
        }

        // Only fields required to safely match an existing local hotel enter the
        // queue. Facilities/type/grade are deliberately excluded so this catalog
        // refresh cannot create or mutate hotel business data.
        $fields = array_fill_keys([
            'id',
            'city_id',
            'name',
            'name_en',
            'star',
            'address',
            'latitude',
            'longitude',
        ], true);

        $domestic = array_values(
            array_map(
                static fn (array $property): array =>
                    array_intersect_key($property, $fields),
                array_filter(
                    $properties,
                    static fn ($property): bool =>
                        is_array($property)
                        && (string) ($property['country_id'] ?? '') === '222'
                )
            )
        );

        if ($domestic === []) {
            throw new RuntimeException(
                'GRS returned no domestic hotels; refusing an empty mapping sync.'
            );
        }

        foreach (array_chunk($domestic, self::BATCH_SIZE) as $batch) {
            ProcessGrsHotelBatchJob::dispatch(
                (int) $provider->id,
                $batch
            )->onQueue('grs-hotels');
        }
    }
}
