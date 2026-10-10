<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Models\AccommodationProviderDetail;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripRefreshHorizonRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use Illuminate\Console\Command;

final class RefreshHorizonCommand extends Command
{
    protected $signature = 'snapptrip:refresh-horizon
        {hotel? : Optional local accommodation id; omit to list all overrides}
        {--days= : Maximum forward refresh horizon in days for this hotel}
        {--clear : Remove the hotel-specific override and return to the provider default}';

    protected $description = 'List, show, set or clear per-hotel SnappTrip refresh horizon overrides.';

    public function handle(
        ProviderOutboundGuard $guard,
        AccommodationProviderMapRepositoryInterface $maps,
        SnappTripRefreshHorizonRepository $horizons,
    ): int {
        $provider = $guard->provider(SnappTripSettings::PROVIDER_CODE);
        if ($provider === null) {
            $this->error('SnappTrip provider is not configured.');
            return self::FAILURE;
        }

        $defaultDays = (int) data_get(SnappTripSettings::from($provider), 'price_refresh.default_days', 90);
        $hotelArgument = $this->argument('hotel');
        $daysOption = $this->option('days');
        $clear = (bool) $this->option('clear');

        if ($hotelArgument === null || $hotelArgument === '') {
            if ($clear || ($daysOption !== null && $daysOption !== '')) {
                $this->error('A hotel id is required when using --days or --clear.');
                return self::INVALID;
            }

            $rows = AccommodationProviderDetail::query()
                ->join('accommodation_provider_maps as maps', 'maps.id', '=', 'accommodation_provider_details.accommodation_provider_map_id')
                ->where('maps.provider_id', (int) $provider->id)
                ->whereNotNull('accommodation_provider_details.refresh_horizon_days')
                ->orderBy('maps.accommodation_id')
                ->get([
                    'maps.accommodation_id',
                    'maps.provider_property_id',
                    'maps.id as map_id',
                    'accommodation_provider_details.refresh_horizon_days',
                ]);

            $this->table(
                ['hotel', 'SnappTrip hotel', 'map', 'refresh horizon days'],
                $rows->map(fn ($row): array => [
                    (int) $row->accommodation_id,
                    (string) $row->provider_property_id,
                    (int) $row->map_id,
                    (int) $row->refresh_horizon_days,
                ])->all(),
            );
            $this->info("Overrides: {$rows->count()}. Provider default horizon: {$defaultDays} days.");

            return self::SUCCESS;
        }

        if (!ctype_digit((string) $hotelArgument) || (int) $hotelArgument < 1) {
            $this->error('The hotel id must be a positive accommodation id.');
            return self::INVALID;
        }
        $hotel = (int) $hotelArgument;

        $map = $maps->findForAccommodationAndProvider($hotel, (int) $provider->id);
        if ($map === null || trim((string) $map->provider_property_id) === '') {
            $this->error("Hotel [{$hotel}] has no usable SnappTrip provider map.");
            return self::FAILURE;
        }

        if ($clear && $daysOption !== null && $daysOption !== '') {
            $this->error('Use either --days or --clear, not both.');
            return self::INVALID;
        }

        if ($clear) {
            $horizons->clear($map);
            $this->info("Cleared SnappTrip refresh horizon override for hotel [{$hotel}]. Effective horizon: {$defaultDays} days.");
            return self::SUCCESS;
        }

        if ($daysOption !== null && $daysOption !== '') {
            if (!ctype_digit((string) $daysOption)) {
                $this->error('--days must be a positive integer.');
                return self::INVALID;
            }

            $days = (int) $daysOption;
            if ($days < 1 || $days > 3650) {
                $this->error('--days must be between 1 and 3650.');
                return self::INVALID;
            }

            $horizons->set($map, $days);
            $this->info("SnappTrip refresh horizon for hotel [{$hotel}] set to {$days} days.");
            return self::SUCCESS;
        }

        $override = $horizons->overrideDays($map);
        $effective = $override === null ? $defaultDays : min($defaultDays, $override);
        $source = $override === null ? 'provider default' : 'hotel override';

        $this->line("Hotel: {$hotel}");
        $this->line('SnappTrip hotel: '.trim((string) $map->provider_property_id));
        $this->line("Refresh horizon: {$effective} days ({$source})");

        return self::SUCCESS;
    }
}
