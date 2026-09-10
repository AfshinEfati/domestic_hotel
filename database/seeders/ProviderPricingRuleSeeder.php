<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderPricingRule;
use Illuminate\Database\Seeder;

class ProviderPricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        Provider::query()
            ->select('id')
            ->orderBy('id')
            ->each(function (Provider $provider): void {
                ProviderPricingRule::query()->firstOrCreate(
                    ['provider_id' => $provider->id],
                    [
                        'percentage' => 5,
                        'fixed_amount' => 0,
                        'is_active' => true,
                    ]
                );
            });
    }
}
