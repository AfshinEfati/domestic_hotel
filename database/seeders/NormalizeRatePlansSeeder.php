<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Normalize hotel-GDS rate plans without changing their identity, Persian label,
 * meal_type, prices, or accommodation relations.
 *
 * Data source: database/seeders/data/rate_plan_name_mapping.csv
 * Safe to run repeatedly; unknown names or conflicting existing codes stop the
 * entire transaction before ANY UPDATE executes.
 */
final class NormalizeRatePlansSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('rate_plans') || ! Schema::hasColumn('rate_plans', 'is_foreign_guest')) {
            throw new RuntimeException('Missing rate_plans table or is_foreign_guest column. Run your migration first.');
        }

        $mapping = $this->loadMapping();

        $summary = DB::transaction(function () use ($mapping): array {
            $rows = DB::table('rate_plans')
                ->select(['id','fa_name', 'en_name', 'is_foreign_guest'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('rate_plans is empty: verify your GDS database connection.');
            }

            $unmapped = [];
            $conflicts = [];
            $flagConflicts = [];
            $changes = [];
            $foreignCount = 0;
            $manualCount = 0;

            foreach ($rows as $row) {
                $name = (string) $row->fa_name;
                if (! array_key_exists($name, $mapping)) {
                    $unmapped[$name] = true;
                    continue;
                }

                $rule = $mapping[$name];
                $expectedCode = $rule['en_name'];
                $expectedForeign = $rule['is_foreign_guest'];
                $oldCode = $row->en_name === null ? '' : trim((string) $row->en_name);
                $oldForeign = (int) $row->is_foreign_guest;

                if ($oldCode !== '' && $oldCode !== $expectedCode) {
                    $conflicts[] = sprintf('id=%d: %s => %s', $row->id, $oldCode, $expectedCode);
                }
                if ($oldForeign === 1 && $expectedForeign === 0) {
                    $flagConflicts[] = (int) $row->id;
                }

                $foreignCount += $expectedForeign;
                if ($rule['review_status'] !== 'safe_from_name') {
                    ++$manualCount;
                }

                if ($oldCode !== $expectedCode || $oldForeign !== $expectedForeign) {
                    $changes[$name][] = (int) $row->id;
                }
            }

            if ($unmapped !== [] || $conflicts !== [] || $flagConflicts !== []) {
                throw new RuntimeException(
                    'Normalization aborted before updates. '
                    . 'Unknown fa_name: ' . json_encode(array_keys($unmapped), JSON_UNESCAPED_UNICODE)
                    . '; conflicting en_name (first 15): ' . json_encode(array_slice($conflicts, 0, 15), JSON_UNESCAPED_UNICODE)
                    . '; unexpected foreign flags (first 15 ids): ' . json_encode(array_slice($flagConflicts, 0, 15))
                );
            }

            $updated = 0;
            $now = now();
            foreach ($changes as $name => $ids) {
                $rule = $mapping[$name];
                $updated += DB::table('rate_plans')
                    ->whereIn('id', $ids)
                    ->update([
                        'en_name' => $rule['en_name'],
                        'is_foreign_guest' => $rule['is_foreign_guest'],
                        'updated_at' => $now,
                    ]);
            }

            if ($updated !== array_sum(array_map('count', $changes))) {
                throw new RuntimeException('Not all expected rate_plan rows were updated. Transaction rolled back.');
            }

            return [
                'scanned' => $rows->count(),
                'updated' => $updated,
                'foreign' => $foreignCount,
                'review_needed' => $manualCount,
            ];
        }, 3);

        $this->command?->info(sprintf(
            'Rate plans normalized: scanned=%d; updated=%d; foreign_guest=%d; rows_with_review_notes=%d.',
            $summary['scanned'],
            $summary['updated'],
            $summary['foreign'],
            $summary['review_needed']
        ));
        $this->command?->warn(
            'Review reports/meal_type_conflicts_2026-09-22.csv and review_status in the mapping. '
            . 'meal_type and food_board_type were NOT changed.'
        );
    }

    /** @return array<string, array{en_name: string, is_foreign_guest: int, review_status: string}> */
    private function loadMapping(): array
    {
        $path = __DIR__ . '/data/rate_plan_name_mapping.csv';
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cannot read normalization CSV: ' . $path);
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw new RuntimeException('Empty normalization CSV: ' . $path);
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $expected = ['fa_name', 'en_name', 'is_foreign_guest', 'review_status', 'source_record_count', 'notes'];
            if ($header !== $expected) {
                throw new RuntimeException('Unexpected normalization CSV headers.');
            }

            $mapping = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($expected)) {
                    throw new RuntimeException('Malformed normalization CSV row.');
                }

                $row = array_combine($expected, $values);
                $name = (string) $row['fa_name'];
                $code = (string) $row['en_name'];
                $flag = (string) $row['is_foreign_guest'];

                if ($name === '' || isset($mapping[$name])
                    || ! preg_match('/^[a-z][a-z0-9_]*$/D', $code)
                    || ! in_array($flag, ['0', '1'], true)) {
                    throw new RuntimeException('Invalid or duplicate mapping entry for: ' . $name);
                }
                $isForeignCode = str_starts_with($code, 'foreign_') || str_starts_with($code, 'b2b_foreign_');
                if ((int) $flag !== (int) $isForeignCode) {
                    throw new RuntimeException('Foreign flag does not match code for: ' . $name);
                }

                $mapping[$name] = [
                    'en_name' => $code,
                    'is_foreign_guest' => (int) $flag,
                    'review_status' => (string) $row['review_status'],
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($mapping === []) {
            throw new RuntimeException('Normalization mapping CSV has no data.');
        }

        return $mapping;
    }
}
