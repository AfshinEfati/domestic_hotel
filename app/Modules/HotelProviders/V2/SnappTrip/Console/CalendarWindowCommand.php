<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Models\AccommodationProviderDetail;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCalendarWindowRepository;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarWindows;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use Illuminate\Console\Command;

final class CalendarWindowCommand extends Command
{
    protected $signature = 'snapptrip:calendar-window
        {hotel? : Optional local accommodation id; omit to list all overrides}
        {--days= : Maximum days per SnappTrip calendar request for this hotel}
        {--clear : Remove the hotel-specific override and return to the default window}';

    protected $description = 'List, show, set or clear per-hotel SnappTrip calendar request windows.';

    public function handle(
        ProviderOutboundGuard $guard,
        AccommodationProviderMapRepositoryInterface $maps,
        SnappTripCalendarWindowRepository $windows,
    ): int {
        $provider = $guard->provider(SnappTripSettings::PROVIDER_CODE);
        if ($provider === null) {
            $this->error('SnappTrip provider is not configured.');
            return self::FAILURE;
        }

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
                ->whereNotNull('accommodation_provider_details.calendar_window_days')
                ->orderBy('maps.accommodation_id')
                ->get([
                    'maps.accommodation_id',
                    'maps.provider_property_id',
                    'maps.id as map_id',
                    'accommodation_provider_details.calendar_window_days',
                ]);

            $this->table(
                ['hotel', 'SnappTrip hotel', 'map', 'calendar window days'],
                $rows->map(fn ($row): array => [
                    (int) $row->accommodation_id,
                    (string) $row->provider_property_id,
                    (int) $row->map_id,
                    (int) $row->calendar_window_days,
                ])->all(),
            );
            $this->info('Overrides: '.$rows->count().'. Default request window: '.SnappTripCalendarWindows::MAX_DAYS.' days.');

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
            $windows->clear($map);
            $this->info(
                "Cleared SnappTrip calendar window override for hotel [{$hotel}]. "
                .'Effective window: '.SnappTripCalendarWindows::MAX_DAYS.' days.'
            );
            return self::SUCCESS;
        }

        if ($daysOption !== null && $daysOption !== '') {
            if (!ctype_digit((string) $daysOption)) {
                $this->error('--days must be a positive integer.');
                return self::INVALID;
            }

            $days = (int) $daysOption;
            if ($days < 1 || $days > SnappTripCalendarWindows::MAX_DAYS) {
                $this->error('--days must be between 1 and '.SnappTripCalendarWindows::MAX_DAYS.'.');
                return self::INVALID;
            }

            $windows->set($map, $days);
            $this->info("SnappTrip calendar window for hotel [{$hotel}] set to {$days} days.");
            return self::SUCCESS;
        }

        $override = $windows->overrideDays($map);
        $effective = $windows->windowDays($map);
        $source = $override === null ? 'default' : 'hotel override';

        $this->line("Hotel: {$hotel}");
        $this->line('SnappTrip hotel: '.trim((string) $map->provider_property_id));
        $this->line("Calendar window: {$effective} days ({$source})");

        return self::SUCCESS;
    }
}
