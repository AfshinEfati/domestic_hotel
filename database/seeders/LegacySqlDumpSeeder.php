<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacySqlDumpSeeder extends Seeder
{
    private const ALLOWED_TABLES = [
        'countries',
        'states',
        'cities',
        'accommodation_types',
        'accommodations',
    ];

    public function run(): void
    {
        DB::disableQueryLog();

        $files = [
            'countries.sql',
            'states.sql',
            ...array_map(
                static fn (int $part): string => sprintf('cities-%02d.sql', $part),
                range(1, 4),
            ),
            'accommodation_types.sql',
            ...array_map(
                static fn (int $part): string => sprintf('accommodations-%02d.sql', $part),
                range(1, 17),
            ),
        ];

        foreach ($files as $file) {
            $this->runInsertStatements(database_path('seeders/data/legacy/'.$file));
        }
    }

    private function runInsertStatements(string $path): void
    {
        if (! is_file($path)) {
            throw new RuntimeException("Legacy SQL seed file not found: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open legacy SQL seed file: {$path}");
        }

        try {
            $statement = '';
            $collecting = false;

            while (($line = fgets($handle)) !== false) {
                if (! $collecting) {
                    $trimmed = ltrim($line);

                    if (! str_starts_with($trimmed, 'INSERT INTO `')) {
                        continue;
                    }

                    if (! preg_match('/^INSERT INTO `([^`]+)`/', $trimmed, $matches)) {
                        continue;
                    }

                    if (! in_array($matches[1], self::ALLOWED_TABLES, true)) {
                        continue;
                    }

                    $collecting = true;
                    $statement = $line;
                } else {
                    $statement .= $line;
                }

                if ($collecting && str_ends_with(rtrim($line), ';')) {
                    DB::unprepared($statement);
                    $statement = '';
                    $collecting = false;
                }
            }

            if ($collecting) {
                throw new RuntimeException("Unterminated INSERT statement in legacy SQL seed file: {$path}");
            }
        } finally {
            fclose($handle);
        }
    }
}
