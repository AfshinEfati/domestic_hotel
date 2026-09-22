<?php

namespace App\Console\Commands\Audit;

use App\Models\AccommodationProviderMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompareEghamatMappingsCommand extends Command
{
    protected $signature = 'audit:eghamat-mappings';

    protected $description = 'Compare Eghamat24 properties with provider mappings';

    public function handle(): int
    {
        $files = collect(
            Storage::disk('local')->files('audits/eghamat')
        )
            ->filter(
                fn ($file) =>
                    str_contains($file, 'properties-')
                    &&
                    str_ends_with($file, '.json')
            )
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->error('No Eghamat snapshot found.');

            return self::FAILURE;
        }

        $latest = $files->last();

        $this->info("Using snapshot: {$latest}");

        $content = json_decode(
            Storage::disk('local')->get($latest),
            true
        );

        $properties = $content['data']['value']['properties'] ?? [];

        if (!is_array($properties)) {
            $this->error('Invalid properties format.');

            return self::FAILURE;
        }

        $this->info(
            'Eghamat properties: ' . count($properties)
        );

        $mapped = AccommodationProviderMap::query()
            ->where('provider_id', 1)
            ->pluck(
                'accommodation_id',
                'provider_property_id'
            )
            ->mapWithKeys(
                fn ($value, $key) => [
                    (string) $key => $value
                ]
            )
            ->toArray();

        $this->info(
            'Local mappings: ' . count($mapped)
        );

        $missing = [];
        $foreign = [];
        $domestic = [];

        $remoteIds = [];


        foreach ($properties as $property) {

            $id = (string) (
                $property['id'] ?? ''
            );

            if ($id === '') {
                continue;
            }

            $remoteIds[] = $id;


            if (isset($mapped[$id])) {
                continue;
            }


            $country = $property['country'] ?? null;


            $row = [
                'provider_property_id' => $id,

                'name' => $property['name'] ?? '',

                'name_en' => $property['name_en'] ?? '',

                'type' => $property['type'] ?? '',

                'star' => $property['star'] ?? '',

                'grade' => $property['grade'] ?? '',

                'country' => is_array($country)
                    ? ($country['name'] ?? '')
                    : ($country ?? ''),

                'country_id' => is_array($country)
                    ? ($country['id'] ?? '')
                    : '',

                'province' => $this->extractLocationName(
                    $property['province'] ?? null
                ),

                'city' => $this->extractLocationName(
                    $property['city'] ?? null
                ),

                'address' => $property['address'] ?? '',

                'latitude' => $property['latitude'] ?? '',

                'longitude' => $property['longitude'] ?? '',
            ];


            $missing[] = $row;


            if ((int) $row['country_id'] === 1) {
                $domestic[] = $row;
            } else {
                $foreign[] = $row;
            }
        }


        $extra = [];


        foreach ($mapped as $providerId => $localId) {

            if (!in_array(
                (string) $providerId,
                $remoteIds,
                true
            )) {

                $extra[] = [
                    'provider_property_id' => $providerId,
                    'accommodation_id' => $localId,
                ];
            }
        }


        $directory = 'audits/eghamat';


        Storage::disk('local')->put(
            $directory . '/missing-mappings.csv',
            $this->makeCsv($missing)
        );


        Storage::disk('local')->put(
            $directory . '/missing-foreign-properties.csv',
            $this->makeCsv($foreign)
        );


        Storage::disk('local')->put(
            $directory . '/missing-domestic-properties.csv',
            $this->makeCsv($domestic)
        );


        Storage::disk('local')->put(
            $directory . '/extra-mappings.csv',
            $this->makeCsv($extra)
        );


        Storage::disk('local')->put(
            $directory . '/summary.json',
            json_encode(
                [
                    'eghamat_total' => count($properties),
                    'mapped_total' => count($mapped),
                    'missing_total' => count($missing),
                    'missing_foreign' => count($foreign),
                    'missing_domestic' => count($domestic),
                    'extra_total' => count($extra),
                    'generated_at' => now()->toDateTimeString(),
                ],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );


        $this->newLine();

        $this->info(
            'Missing: ' . count($missing)
        );

        $this->info(
            'Foreign missing: ' . count($foreign)
        );

        $this->info(
            'Domestic missing: ' . count($domestic)
        );

        $this->info(
            'Extra: ' . count($extra)
        );


        return self::SUCCESS;
    }


    private function extractLocationName($value): string
    {
        if (is_array($value)) {
            return $value['name'] ?? '';
        }

        return (string) $value;
    }


    private function makeCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }


        $handle = fopen(
            'php://temp',
            'r+'
        );


        fputcsv(
            $handle,
            array_keys($rows[0])
        );


        foreach ($rows as $row) {
            fputcsv(
                $handle,
                $row
            );
        }


        rewind($handle);


        return stream_get_contents($handle);
    }
}
