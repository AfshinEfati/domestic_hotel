<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCatalogRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncHotelDetailsJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use Illuminate\Console\Command;

final class SyncHotelDetailsCommand extends Command
{
    protected $signature = 'snapptrip:sync-details {hotel? : Optional SnappTrip hotel ID or comma-separated IDs}';
    protected $description = 'Queue SnappTrip static details, facilities, gallery, reviews and rooms synchronization.';

    public function handle(ProviderOutboundGuard $guard, SnappTripCatalogRepository $catalog): int
    {
        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $this->warn('SnappTrip provider outbound operations are disabled.');
            return self::FAILURE;
        }

        $provider = $guard->provider(SnappTripSettings::PROVIDER_CODE);
        if ($provider === null) {
            return self::FAILURE;
        }

        $argument = trim((string) ($this->argument('hotel') ?? ''));
        if ($argument !== '') {
            $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $argument)))));
            foreach (array_chunk($ids, 10) as $chunk) {
                SyncHotelDetailsJob::dispatch($chunk)->onQueue('snapptrip-static');
            }
            $this->info(count($ids).' SnappTrip hotel ID(s) queued for details synchronization.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($catalog->mappedHotels($provider)->chunk(10) as $chunk) {
            $ids = $chunk->pluck('provider_property_id')->map(fn ($id): string => (string) $id)->values()->all();
            if ($ids !== []) {
                SyncHotelDetailsJob::dispatch($ids)->onQueue('snapptrip-static');
                $count += count($ids);
            }
        }

        $this->info($count.' mapped SnappTrip hotel(s) queued for details synchronization.');

        return self::SUCCESS;
    }
}
