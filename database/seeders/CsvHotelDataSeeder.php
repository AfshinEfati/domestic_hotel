<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CsvHotelDataSeeder extends Seeder
{
    private const TABLE_COLUMNS = [
        'countries' => ['id', 'fa_name', 'en_name', 'iso2', 'iso3', 'created_at', 'updated_at'],
        'states' => ['id', 'country_id', 'fa_name', 'en_name', 'created_at', 'updated_at'],
        'cities' => ['id', 'country_id', 'state_id', 'fa_name', 'en_name', 'lat', 'lng', 'osm_id', 'is_popular', 'is_active', 'created_at', 'updated_at'],
        'accommodations' => ['id', 'city_id', 'fa_name', 'en_name', 'accommodation_type_id', 'star', 'grade', 'address', 'lat', 'lng', 'is_active', 'created_at', 'updated_at'],
    ];

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        DB::transaction(function (): void {
            foreach (self::TABLE_COLUMNS as $table => $columns) {
                $this->import($table, $columns);
            }
        });
    }

    private function import(string $table, array $columns): void
    {
        $path = database_path("seeders/data/{$table}.csv");
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("CSV seed file not found or unreadable: {$path}");
        }

        try {
            $header = fgetcsv($handle, 0, ',', '"', '');

            if (! is_array($header)) {
                throw new RuntimeException("Empty CSV seed file: {$path}");
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            if ($header !== $columns) {
                throw new RuntimeException("Unexpected CSV columns in: {$path}");
            }

            $batch = [];

            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($columns)) {
                    throw new RuntimeException("Invalid CSV column count in: {$path}");
                }

                $batch[] = array_combine(
                    $columns,
                    array_map(static fn (?string $value): ?string => $value === 'NULL' ? null : $value, $values),
                );

                if (count($batch) >= 200) {
                    $this->saveBatch($table, $columns, $batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $this->saveBatch($table, $columns, $batch);
            }
        } finally {
            fclose($handle);
        }
    }

    private function saveBatch(string $table, array $columns, array $batch): void
    {
        DB::table($table)->upsert($batch, ['id'], array_values(array_diff($columns, ['id'])));
    }
}
