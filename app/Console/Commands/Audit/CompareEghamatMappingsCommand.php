<?php

namespace App\Console\Commands\Audit;

use App\Models\AccommodationProviderMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompareEghamatMappingsCommand extends Command
{
    protected $signature = 'audit:eghamat-mappings';

    protected $description = 'Compare Eghamat properties with provider mappings';

    public function handle(): int
    {
        $files = collect(
            Storage::disk('local')
                ->files('audits/eghamat')
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
            $this->error(
                'No Eghamat snapshot found.'
            );

            return self::FAILURE;
        }

        $latest = $files->last();

        $content = json_decode(
            Storage::disk('local')->get($latest),
            true
        );

        $properties = $content['data']['data']
            ?? $content['data']['properties']
            ?? [];

        $mapped = AccommodationProviderMap::query()
            ->where('provider_id', 1)
            ->pluck('accommodation_id', 'provider_property_id')
            ->toArray();


        $missing = [];
        $remoteIds = [];

        foreach ($properties as $property) {

            $id = (string) (
                $property['id']
                ?? $property['property_id']
                ?? ''
            );

            if ($id === '') {
                continue;
            }

            $remoteIds[] = $id;

            if (!isset($mapped[$id])) {

                $missing[] = [
                    'provider_property_id' => $id,
                    'name' => $property['name']
                        ?? $property['title']
                            ?? '',
                    'city' => $property['city']['name']
                        ?? '',
                ];
            }
        }


        $extra = [];

        foreach ($mapped as $providerId => $localId) {

            if (!in_array(
                (string)$providerId,
                $remoteIds,
                true
            )) {

                $extra[] = [
                    'provider_property_id' => $providerId,
                    'accommodation_id' => $localId,
                ];
            }
        }


        $dir = 'audits/eghamat';

        Storage::disk('local')->put(
            $dir.'/missing-mappings.csv',
            $this->csv($missing)
        );

        Storage::disk('local')->put(
            $dir.'/extra-mappings.csv',
            $this->csv($extra)
        );


        Storage::disk('local')->put(
            $dir.'/summary.json',
            json_encode(
                [
                    'eghamat_total' => count($properties),
                    'mapped_total' => count($mapped),
                    'missing_total' => count($missing),
                    'extra_total' => count($extra),
                    'generated_at' => now()->toDateTimeString(),
                ],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );


        $this->info(
            "Missing: ".count($missing)
        );

        $this->info(
            "Extra: ".count($extra)
        );

        return self::SUCCESS;
    }


    private function csv(array $rows): string
    {
        if (!$rows) {
            return '';
        }

        $handle = fopen('php://temp', 'r+');

        fputcsv(
            $handle,
            array_keys($rows[0])
        );

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return stream_get_contents($handle);
    }
}
