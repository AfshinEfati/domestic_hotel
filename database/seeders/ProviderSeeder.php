<?php

namespace Database\Seeders;

use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\SnappTripAdapter;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $grs = Provider::query()->firstOrCreate(
            ['code' => 'grs'],
            [
                'fa_name' => 'اقامت ۲۴',
                'en_name' => 'GRS Channel',
                'class' => GRSAdapter::class,
                'config' => [
                    'base_url' => 'https://api.grschannel.com/',
                    'token' => 'https://api.grschannel.com-$2y$10$/iQviVsfD1mKLS58OYdNve9',
                    'availability_rate_limit' => [
                        'max_requests' => 10,
                        'window_minutes' => 1,
                    ],
                ],
                'is_online' => true,
            ]
        );

        $grs->forceFill([
            'is_online' => true,
            'config' => array_replace_recursive(
                [
                    'base_url' => 'https://api.grschannel.com/',
                    'token' => 'https://api.grschannel.com-$2y$10$/iQviVsfD1mKLS58OYdNve9',
                    'availability_rate_limit' => [
                        'max_requests' => 10,
                        'window_minutes' => 1,
                    ],
                ],
                $grs->config ?? []
            ),
        ])->save();

        $parto = Provider::query()->firstOrCreate(
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
            ]
        );
        $parto->forceFill(['is_online' => false])->save();

        $iho = Provider::query()->firstOrCreate(
            ['code' => 'iho'],
            [
                'fa_name' => 'ایران هتل آنلاین',
                'en_name' => 'Iran Hotel Online',
                'class' => IHOAdapter::class,
                'config' => [
                    'base_url' => 'https://www.iranhotelonline.com:443',
                    'version' => 1,
                ],
            ]
        );
        $iho->forceFill(['is_online' => false])->save();

        $snap = Provider::query()->firstOrCreate(
            ['code' => 'snap'],
            [
                'fa_name' => 'اسنپ تریپ',
                'en_name' => 'SnappTrip',
                'class' => SnappTripAdapter::class,
                'config' => [
                    'base_url' => 'https://b2bapiv2.snapptrip.com/',
                    'token' => '9EcxDBS7gmfvh5HaHDtjjxQhEVRHaPJP6hegUJ5FBerz8Cam3Xt6X97k8rf5GDGL',
                ],
            ]
        );
        $snap->forceFill(['is_online' => false])->save();
    }
}
