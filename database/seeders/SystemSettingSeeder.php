<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Support\System\SystemSettingKey;
use App\Support\System\SystemSettingValueType;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => SystemSettingKey::PRICING_DEFAULT_PERCENTAGE,
                'group' => 'pricing',
                'value' => '5',
                'value_type' => SystemSettingValueType::FLOAT,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::PRICING_DEFAULT_FIXED_AMOUNT,
                'group' => 'pricing',
                'value' => '0',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::RESERVATION_REFERENCE_PREFIX,
                'group' => 'reservation',
                'value' => 'DH',
                'value_type' => SystemSettingValueType::STRING,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::RESERVATION_REFERENCE_RANDOM_LENGTH,
                'group' => 'reservation',
                'value' => '10',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::RESERVATION_REFERENCE_MAX_ATTEMPTS,
                'group' => 'reservation',
                'value' => '10',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::GRS_AVAILABILITY_DAYS,
                'group' => 'provider.grs.availability',
                'value' => '60',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::GRS_AVAILABILITY_CHUNK_SIZE,
                'group' => 'provider.grs.availability',
                'value' => '20',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::GRS_AVAILABILITY_THROTTLE_MS,
                'group' => 'provider.grs.availability',
                'value' => '500',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => SystemSettingKey::GRS_AVAILABILITY_MAX_ATTEMPTS,
                'group' => 'provider.grs.availability',
                'value' => '1',
                'value_type' => SystemSettingValueType::INTEGER,
                'is_active' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
