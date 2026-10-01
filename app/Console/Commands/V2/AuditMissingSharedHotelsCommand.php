<?php

namespace App\Console\Commands\V2;

use App\Models\Accommodation;
use App\Models\HotelPriceRefreshSchedule;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditMissingSharedHotelsCommand extends Command
{
    protected $signature = 'grs:audit-missing-hotels
                            {--all : Check inactive shared schedules too}
                            {--no-telegram : Only print the result and do not send Telegram alerts}';

    protected $description = 'Compare shared SSP GDS hotel IDs with local accommodations without calling any provider API';

    public function handle(TelegramAlertService $alerts): int
    {
        $this->info('Checking shared SSP hotel IDs against local accommodations...');
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Read shared SSP schedules
        |--------------------------------------------------------------------------
        |
        | This query uses the shared_ssp connection through the model.
        | No GRS/provider HTTP request is made.
        |
        */
        $query = HotelPriceRefreshSchedule::query()
            ->whereNotNull('gds_id')
            ->where('gds_id', '>', 0);

        if (!$this->option('all')) {
            $query->where('is_active', true);
        }

        $schedules = $query
            ->orderBy('id')
            ->get([
                'id',
                'gds_id',
                'is_active',
                'refresh_interval_minutes',
                'next_gds_run_at',
            ]);

        if ($schedules->isEmpty()) {
            $this->warn('No shared hotel price refresh schedules found.');

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Unique GDS IDs referenced by shared SSP
        |--------------------------------------------------------------------------
        */
        $sharedHotelIds = $schedules
            ->pluck('gds_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Local accommodations
        |--------------------------------------------------------------------------
        |
        | Query is chunked so this command remains safe even if the shared list
        | becomes much larger.
        |
        */
        $existingHotelIds = collect();

        $sharedHotelIds
            ->chunk(1000)
            ->each(function (Collection $ids) use (&$existingHotelIds): void {
                $existingHotelIds = $existingHotelIds->merge(
                    Accommodation::query()
                        ->whereIn('id', $ids->all())
                        ->pluck('id')
                        ->map(fn ($id): int => (int) $id)
                );
            });

        $existingHotelIds = $existingHotelIds
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | IDs existing in shared SSP but missing from Domestic Hotel
        |--------------------------------------------------------------------------
        */
        $missingHotelIds = $sharedHotelIds
            ->diff($existingHotelIds)
            ->sort()
            ->values();

        $missingLookup = $missingHotelIds
            ->flip();

        $missingSchedules = $schedules
            ->filter(
                fn (HotelPriceRefreshSchedule $schedule): bool =>
                $missingLookup->has((int) $schedule->gds_id)
            )
            ->values();

        $duplicateScheduleIds = $schedules
            ->groupBy(fn (HotelPriceRefreshSchedule $schedule): int => (int) $schedule->gds_id)
            ->filter(fn (Collection $rows): bool => $rows->count() > 1);

        /*
        |--------------------------------------------------------------------------
        | Console summary
        |--------------------------------------------------------------------------
        */
        $this->table(
            ['مورد', 'تعداد'],
            [
                ['Schedule rows checked', $schedules->count()],
                ['Unique shared GDS IDs', $sharedHotelIds->count()],
                ['Existing local hotels', $existingHotelIds->count()],
                ['Missing local hotels', $missingHotelIds->count()],
                ['Schedule rows pointing to missing hotels', $missingSchedules->count()],
                ['GDS IDs with duplicate schedule rows', $duplicateScheduleIds->count()],
            ]
        );

        if ($missingHotelIds->isEmpty()) {
            $this->newLine();
            $this->info('OK: Every shared GDS ID exists in local accommodations.');

            if (!$this->option('no-telegram')) {
                $alerts->custom(
                    title: 'بررسی تطابق هتل‌های Shared',
                    description: 'تمام GDS IDهای فعال جدول مشترک در Domestic Hotel وجود دارند.',
                    fields: [
                        'Scheduleها' => $schedules->count(),
                        'GDS IDهای یکتا' => $sharedHotelIds->count(),
                        'هتل ناموجود' => 0,
                    ],
                    level: 'info',
                    tags: [
                        'DomesticHotel',
                        'SharedSchedule',
                        'Audit',
                    ],
                );
            }

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Print every missing ID
        |--------------------------------------------------------------------------
        */
        $this->newLine();
        $this->error(
            'Found '.$missingHotelIds->count()
            .' GDS IDs in shared SSP that do not exist in accommodations.'
        );

        $rows = $missingHotelIds
            ->map(function (int $gdsId) use ($missingSchedules): array {
                $scheduleRows = $missingSchedules
                    ->where('gds_id', $gdsId);

                return [
                    'gds_id' => $gdsId,
                    'schedule_ids' => $scheduleRows
                        ->pluck('id')
                        ->implode(', '),
                    'rows' => $scheduleRows->count(),
                    'active' => $scheduleRows
                        ->where('is_active', true)
                        ->count(),
                ];
            })
            ->all();

        $this->table(
            [
                'GDS ID',
                'Schedule IDs',
                'Rows',
                'Active',
            ],
            $rows
        );

        /*
        |--------------------------------------------------------------------------
        | Telegram
        |--------------------------------------------------------------------------
        |
        | Do NOT create one alert per hotel here.
        | This is an audit report, so IDs are grouped into reasonably small
        | messages while preserving the complete list.
        |
        */
        if (!$this->option('no-telegram')) {
            $chunks = $missingHotelIds->chunk(40);
            $totalParts = $chunks->count();

            foreach ($chunks as $index => $chunk) {
                $alerts->custom(
                    title: 'مغایرت هتل‌های Shared با Domestic Hotel',
                    description: 'این GDS IDها در جدول مشترک نرخ و ظرفیت وجود دارند اما هیچ رکوردی با این ID در accommodations وجود ندارد.',
                    fields: [
                        'تعداد کل هتل ناموجود' => $missingHotelIds->count(),
                        'Scheduleهای مشکل‌دار' => $missingSchedules->count(),
                        'بخش' => ($index + 1).' از '.$totalParts,
                        'GDS IDها' => $chunk->implode(', '),
                    ],
                    level: 'warning',
                    tags: [
                        'DomesticHotel',
                        'SharedSchedule',
                        'MissingHotel',
                        'Audit',
                    ],
                );
            }

            $this->newLine();
            $this->info(
                $totalParts.' Telegram alert(s) queued.'
            );
        }

        $this->newLine();
        $this->warn(
            'Read-only audit completed. No shared row, hotel, map or provider data was changed.'
        );

        return self::SUCCESS;
    }
}
