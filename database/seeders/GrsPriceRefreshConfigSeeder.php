<?php

namespace Database\Seeders;

use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Provider;
use Illuminate\Database\Seeder;

/** Backfill GRS refresh keys only; never seed or change any other supplier. */
class GrsPriceRefreshConfigSeeder extends Seeder
{
    public function run(): void
    {
        $provider = Provider::query()->where('code', 'grs')->firstOrFail();
        $current = $provider->config ?? [];
        $updated = array_replace_recursive(
            ['price_refresh' => GrsRefreshSettings::defaults()],
            is_array($current) ? $current : []
        );

        if ($updated !== $current) {
            $provider->forceFill(['config' => $updated])->save();
        }
    }
}
