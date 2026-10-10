<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCalendarWindowRepository;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarWindows;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Throwable;

final class ProbeCalendarCommand extends Command
{
    protected $signature = 'snapptrip:probe-calendar
        {hotel : Local accommodation ID}
        {--days=90 : Calendar horizon in days}
        {--raw : Send the whole range in one provider request per guest scope instead of production chunking}';

    protected $description = 'Probe SnappTrip hotel calendar without persistence or shared schedule changes.';

    public function handle(
        ProviderOutboundGuard $guard,
        AccommodationProviderMapRepositoryInterface $maps,
        SnappTripGatewayFactory $gateways,
        SnappTripCalendarWindowRepository $calendarWindows,
    ): int {
        $accommodationId = (int) $this->argument('hotel');
        $days = (int) $this->option('days');

        if ($accommodationId < 1) {
            $this->error('The hotel argument must be a positive local accommodation ID.');
            return self::INVALID;
        }
        if ($days < 1 || $days > 3650) {
            $this->error('The --days value must be between 1 and 3650.');
            return self::INVALID;
        }

        try {
            $provider = $guard->assertAllowed(SnappTripSettings::PROVIDER_CODE);
            $map = $maps->findForAccommodationAndProvider($accommodationId, (int) $provider->id);
            if ($map === null || $map->is_disabled || trim((string) $map->provider_property_id) === '') {
                $this->error("Hotel {$accommodationId} has no usable SnappTrip provider map.");
                return self::FAILURE;
            }

            $from = CarbonImmutable::now('Asia/Tehran')->toDateString();
            $to = CarbonImmutable::now('Asia/Tehran')->addDays($days)->toDateString();
            $raw = (bool) $this->option('raw');
            $windowDays = $calendarWindows->windowDays($map);
            $override = $calendarWindows->overrideDays($map);
            $windows = $raw
                ? [['from' => $from, 'to' => $to]]
                : SnappTripCalendarWindows::split($from, $to, $windowDays);
            $gateway = $gateways->make($provider)->withProviderAlertContext(
                $accommodationId,
                (string) $map->provider_property_id,
            );

            $this->info('Local hotel: '.$accommodationId);
            $this->info('SnappTrip hotel: '.(string) $map->provider_property_id);
            $this->info("Range: {$from} -> {$to} ({$days} days)");
            if (!$raw) {
                $source = $override === null ? 'default' : 'hotel override';
                $this->info("Calendar request window: {$windowDays} days ({$source})");
            }
            $this->info('Calendar windows: '.count($windows));
            $this->warn($raw
                ? 'RAW probe: the provider may reject large date ranges. No data is persisted.'
                : "Production-style probe: ranges use this hotel's {$windowDays}-day request window. No data is persisted.");

            $rows = [];
            $failed = false;
            foreach ($windows as $index => $window) {
                foreach ([false, true] as $foreigner) {
                    $scope = $foreigner ? 'foreign' : 'domestic';

                    try {
                        $result = $gateway->hotelCalendar(
                            (string) $map->provider_property_id,
                            $window['from'],
                            $window['to'],
                            $foreigner,
                        );

                        $rows[] = [
                            $index + 1,
                            $window['from'].' -> '.$window['to'],
                            $scope,
                            '200',
                            count(is_array($result['rows'] ?? null) ? $result['rows'] : []),
                            count(is_array($result['packages'] ?? null) ? $result['packages'] : []),
                            '-',
                        ];
                    } catch (RequestException $exception) {
                        $failed = true;
                        $status = $exception->response?->status();
                        $body = trim((string) $exception->response?->body());
                        $rows[] = [
                            $index + 1,
                            $window['from'].' -> '.$window['to'],
                            $scope,
                            $status === null ? '-' : (string) $status,
                            '-',
                            '-',
                            $this->compact($body !== '' ? $body : $exception->getMessage()),
                        ];
                    } catch (Throwable $exception) {
                        $failed = true;
                        $rows[] = [
                            $index + 1,
                            $window['from'].' -> '.$window['to'],
                            $scope,
                            '-',
                            '-',
                            '-',
                            $this->compact(get_class($exception).': '.$exception->getMessage()),
                        ];
                    }
                }
            }

            $this->table(['window', 'range', 'scope', 'HTTP', 'rows', 'packages', 'error/response'], $rows);

            return $failed ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function compact(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        if (mb_strlen($value) <= 1200) {
            return $value;
        }

        return mb_substr($value, 0, 1200).'...';
    }
}
