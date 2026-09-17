<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccommodationTypeSeeder::class,
            CsvHotelDataSeeder::class,
            ProviderSeeder::class,
            SystemSettingSeeder::class,
            ProviderPricingRuleSeeder::class,
        ]);
    }
}
