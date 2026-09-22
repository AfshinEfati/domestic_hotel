<?php

namespace App\Jobs\Hotel\V2;

use App\Models\Facility;
use App\Models\FacilityGroup;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncGrsHotelCatalogJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Never retry a 429 automatically.
    public int $timeout = 80; // Must remain below the queue connection's retry_after.
    public int $uniqueFor = 3600;

    private const API_COUNT = 10000000;
    private const BATCH_SIZE = 25;

    public function uniqueId(): string
    {
        return 'grs-hotel-catalog';
    }

    public function handle(): void
    {
        $provider = Provider::query()->where('code', 'grs')->firstOrFail();
        if (!$provider->is_active || !$provider->is_online) {
            Log::warning('GRS hotel catalog sync skipped: provider is inactive or offline');
            return;
        }

        $baseUrl = rtrim((string) data_get($provider->config, 'base_url', ''), '/');
        $token = (string) data_get($provider->config, 'token', '');
        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('GRS hotel catalog requires a configured base_url and Client-Token.');
        }

        // One request for the complete catalog, never one request per city/property.
        // HTTP errors (including 429) fail this job without automatic retry.
        $response = Http::withAttributes(['domestic_provider' => ['code' => 'grs']])
            ->withHeaders(['Client-Token' => $token, 'Content-Type' => 'application/json'])
            ->baseUrl($baseUrl)
            ->timeout(55)
            ->get('/v1/properties', ['page' => 1, 'count' => self::API_COUNT]);
        $response->throw();

        $body = $response->json();
        $properties = data_get($body, 'value.properties');
        if ((int) data_get($body, 'code') !== 200 || !is_array($properties)) {
            throw new RuntimeException('Invalid GRS properties response; no hotels were imported.');
        }
        if ($properties === []) {
            throw new RuntimeException('GRS returned an empty properties list; refusing an incomplete sync.');
        }

        // Only fields required for mapping/facilities enter the queue. Discard photos
        // and descriptions here; they belong to a separate hotel-media workflow.
        $fields = array_fill_keys([
            'id', 'city_id', 'name', 'name_en', 'type', 'star', 'grade',
            'address', 'latitude', 'longitude', 'facilities',
        ], true);
        $domestic = array_values(array_map(
            static fn (array $property) => array_intersect_key($property, $fields),
            array_filter($properties, static fn ($property) =>
                is_array($property) && (string) ($property['country_id'] ?? '') === '222'
            )
        ));
        if ($domestic === []) {
            throw new RuntimeException('GRS returned no domestic hotels; refusing an empty import.');
        }

        // Initialize the shared facility dictionary sequentially, before parallel database-only batches.
        // This avoids duplicate facility/group creation when several queue workers are running.
        $this->prepareFacilities($domestic);

        foreach (array_chunk($domestic, self::BATCH_SIZE) as $batch) {
            ProcessGrsHotelBatchJob::dispatch($provider->id, $batch)->onQueue('grs-hotels');
        }

        Log::info('GRS hotel catalog queued for mapping', [
            'received' => count($properties),
            'domestic' => count($domestic),
            'batches' => (int) ceil(count($domestic) / self::BATCH_SIZE),
        ]);
    }

    private function prepareFacilities(array $properties): void
    {
        $groups = [];
        $facilities = [];
        foreach ($properties as $property) {
            foreach (is_array($property['facilities'] ?? null) ? $property['facilities'] : [] as $facility) {
                if (!is_array($facility)) {
                    continue;
                }
                $groupName = trim((string) ($facility['group_name'] ?? 'سایر')) ?: 'سایر';
                $name = trim((string) ($facility['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $groups[$groupName] = true;
                $facilities[$groupName."\0".$name] = [$groupName, $name];
            }
        }

        $groupIds = [];
        foreach (array_keys($groups) as $name) {
            $groupIds[$name] = FacilityGroup::query()->firstOrCreate(['fa_name' => $name])->id;
        }
        foreach ($facilities as [$groupName, $name]) {
            Facility::query()->firstOrCreate([
                'facility_group_id' => $groupIds[$groupName],
                'fa_name' => $name,
            ]);
        }
    }
}
