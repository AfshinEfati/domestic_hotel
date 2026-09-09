<?php

namespace Database\Seeders;

use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Providers\IHOAdapter;
use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\SnappTripAdapter;
use Illuminate\Database\Seeder;
use App\Models\Provider;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        // GRS (Aghamat24)
        Provider::updateOrCreate(
            ['code' => 'grs'],
            [
                'fa_name' => 'اقامت ۲۴',
                'en_name' => 'GRS Channel',
                'class'   => GRSAdapter::class,
                'config'  => [
                    'base_url' => 'https://api.grschannel.com/',
                    'token'    => 'https://api.grschannel.com-$2y$10$/iQviVsfD1mKLS58OYdNve9',
                ],
            ]
        );

        // Parto
        Provider::updateOrCreate(
            ['code' => 'parto'],
            [
                'fa_name' => 'پرتو CRS',
                'en_name' => 'Parto CRS',
                'class'   => PartoAdapter::class,
                'config'  => [
                    'base_url' => 'https://apidemo.partocrs.com/api/',
                    'auth_token'   => null,
                    'expire_at'    => null,
                    'access_key'   => 'CRS001539',
                    'secret_key'   => ',sXL059?mZN3',
                ],
            ]
        );

        // IranHotelOnline
        Provider::updateOrCreate(
            ['code' => 'iho'],
            [
                'fa_name' => 'ایران هتل آنلاین',
                'en_name' => 'Iran Hotel Online',
                'class'   => IHOAdapter::class,
                'config'  => [
                    'base_url' => 'https://www.iranhotelonline.com:443',
                    'version'  => 1,
                ],
            ]
        );

        // SnappTrip
        Provider::updateOrCreate(
            ['code' => 'snap'],
            [
                'fa_name' => 'اسنپ تریپ',
                'en_name' => 'SnappTrip',
                'class'   => SnappTripAdapter::class,
                'config'  => [
                    'base_url' => 'https://b2bapiv2.snapptrip.com/', // Placeholder
                    'token'    => '9EcxDBS7gmfvh5HaHDtjjxQhEVRHaPJP6hegUJ5FBerz8Cam3Xt6X97k8rf5GDGL',
                ],
            ]
        );
    }
}
