<?php

namespace Database\Seeders;

use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\SnappTripAdapter;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
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

        $snappDefaults = SnappTripSettings::defaults();
        $snapp = Provider::query()->firstOrCreate(
            ['code' => SnappTripSettings::PROVIDER_CODE],
            [
                'fa_name' => 'اسنپ تریپ',
                'en_name' => 'SnappTrip',
                'class' => SnappTripAdapter::class,
                'config' => $snappDefaults,
                'is_online' => false,
            ]
        );

        // Existing installations may already have a real SnappTrip key stored under
        // the legacy token key. Move it to api_key in the database without ever
        // reintroducing the credential into source control. Fresh installs receive
        // only the explicit api_key_snapptrip placeholder.
        $snappConfig = is_array($snapp->config) ? $snapp->config : [];
        if (
            (!isset($snappConfig['api_key']) || trim((string) $snappConfig['api_key']) === '')
            && isset($snappConfig['token'])
            && trim((string) $snappConfig['token']) !== ''
        ) {
            $snappConfig['api_key'] = $snappConfig['token'];
        }
        unset($snappConfig['token']);

        $snappMerged = array_replace_recursive($snappDefaults, $snappConfig);
        if ($snappMerged !== ($snapp->config ?? [])) {
            $snapp->forceFill([
                'class' => SnappTripAdapter::class,
                'config' => $snappMerged,
            ])->save();
        }
    }
}
