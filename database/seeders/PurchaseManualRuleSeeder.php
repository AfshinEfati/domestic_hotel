<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\PurchaseManualRule;
use Illuminate\Database\Seeder;

class PurchaseManualRuleSeeder extends Seeder
{
    public function run(): void
    {
        Provider::query()->each(function (Provider $provider) {
            PurchaseManualRule::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                ],
                [
                    'name' => $provider->fa_name . ' default manual rule',
                    'is_active' => true,
                ]
            );
        });
    }
}
