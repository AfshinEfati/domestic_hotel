<?php

namespace Database\Seeders;

use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\SnappTripAdapter;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $grsDefaults = [
            'base_url' => 'https://api.grschannel.com/',
            'token' => 'https://api.grschannel.com-$2y$10$/iQviVsfD1mKLS58OYdNve9',
            'availability_rate_limit' => [
                'max_requests' => 10,
                'window_minutes' => 1,
            ],
            'price_refresh' => GrsRefreshSettings::defaults(),
        ];

        $grs = Provider::query()->firstOrCreate(
            ['code' => 'grs'],
            [
                'fa_name' => 'اقامت ۲۴',
                'en_name' => 'GRS Channel',
                'class' => GRSAdapter::class,
                'config' => $grsDefaults,
                'is_online' => true,
            ]
        );

        // Keep admin overrides and unrelated keys; clean up the obsolete settings
        // that were introduced in the previous, unapproved implementation.
        $currentConfig = is_array($grs->config) ? $grs->config : [];
        if (isset($currentConfig['price_refresh']) && is_array($currentConfig['price_refresh'])) {
            unset(
                $currentConfig['price_refresh']['dispatch_limit'],
                $currentConfig['price_refresh']['claim_minutes'],
                $currentConfig['price_refresh']['failure_backoff_minutes']
            );
        }
        $mergedConfig = array_replace_recursive($grsDefaults, $currentConfig);
        if ($mergedConfig !== ($grs->config ?? [])) {
            $grs->forceFill(['config' => $mergedConfig])->save();
        }

        // Never reset an administrator's settings or online status for other providers.
        Provider::query()->firstOrCreate(
            ['code' => 'parto'],
            [
                'fa_name' => 'پرتو CRS',
                'en_name' => 'Parto CRS',
                'class' => PartoAdapter::class,
                'config' => [
                    'base_url' => 'https://apidemo.partocrs.com/api/',
                    'auth_token' => null,
                    'expire_at' => null,
                    'access_key' => 'CRS001539',
                    'secret_key' => ',sXL059?mZN3',
                ],
                'is_online' => false,
            ]
        );

        Provider::query()->firstOrCreate(
            ['code' => 'iho'],
            [
                'fa_name' => 'ایران هتل آنلاین',
                'en_name' => 'Iran Hotel Online',
                'class' => IHOAdapter::class,
                'config' => [
                    'base_url' => 'https://www.iranhotelonline.com:443',
                    'version' => 1,
                ],
                'is_online' => false,
            ]
        );

        Provider::query()->firstOrCreate(
            ['code' => 'snap'],
            [
                'fa_name' => 'اسنپ تریپ',
                'en_name' => 'SnappTrip',
                'class' => SnappTripAdapter::class,
                'config' => [
                    'base_url' => 'https://b2bapiv2.snapptrip.com/',
                    'token' => '9EcxDBS7gmfvh5HaHDtjjxQhEVRHaPJP6hegUJ5FBerz8Cam3Xt6X97k8rf5GDGL',
                ],
                'is_online' => false,
            ]
        );
    }
}
