<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Console;

use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\SnappTripGatewayFactory;
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
        {--days=90 : Calendar horizon in days}';

    protected $description = 'Probe SnappTrip hotel calendar for one mapped hotel without persistence or shared schedule changes.';

    public function handle(
        ProviderOutboundGuard $guard,
        AccommodationProviderMapRepositoryInterface $maps,
        SnappTripGatewayFactory $gateways,
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
            $gateway = $gateways->make($provider)->withProviderAlertContext(
                $accommodationId,
                (string) $map->provider_property_id,
            );

            $this->info('Local hotel: '.$accommodationId);
            $this->info('SnappTrip hotel: '.(string) $map->provider_property_id);
            $this->info("Range: {$from} -> {$to} ({$days} days)");
            $this->warn('Probe only: no calendar rows are persisted and shared schedule state is not changed.');

            $rows = [];
            $failed = false;
            foreach ([false, true] as $foreigner) {
                $scope = $foreigner ? 'foreign' : 'domestic';

                try {
                    $result = $gateway->hotelCalendar(
                        (string) $map->provider_property_id,
                        $from,
                        $to,
                        $foreigner,
                    );

                    $rows[] = [
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
                        $scope,
                        $status === null ? '-' : (string) $status,
                        '-',
                        '-',
                        $this->compact($body !== '' ? $body : $exception->getMessage()),
                    ];
                } catch (Throwable $exception) {
                    $failed = true;
                    $rows[] = [
                        $scope,
                        '-',
                        '-',
                        '-',
                        $this->compact(get_class($exception).': '.$exception->getMessage()),
                    ];
                }
            }

            $this->table(['scope', 'HTTP', 'rows', 'packages', 'error/response'], $rows);

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
