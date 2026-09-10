<?php

namespace Database\Seeders;

use App\Models\HotelSetting;
use App\Support\Hotel\HotelSettingKey;
use App\Support\Hotel\HotelSettingValueType;
use Illuminate\Database\Seeder;

class HotelSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => HotelSettingKey::PRICING_DEFAULT_PERCENTAGE,
                'group' => 'pricing',
                'value' => '5',
                'value_type' => HotelSettingValueType::FLOAT,
                'is_active' => true,
            ],
            [
                'key' => HotelSettingKey::PRICING_DEFAULT_FIXED_AMOUNT,
                'group' => 'pricing',
                'value' => '0',
                'value_type' => HotelSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => HotelSettingKey::RESERVATION_REFERENCE_PREFIX,
                'group' => 'reservation',
                'value' => 'DH',
                'value_type' => HotelSettingValueType::STRING,
                'is_active' => true,
            ],
            [
                'key' => HotelSettingKey::RESERVATION_REFERENCE_RANDOM_LENGTH,
                'group' => 'reservation',
                'value' => '10',
                'value_type' => HotelSettingValueType::INTEGER,
                'is_active' => true,
            ],
            [
                'key' => HotelSettingKey::RESERVATION_REFERENCE_MAX_ATTEMPTS,
                'group' => 'reservation',
                'value' => '10',
                'value_type' => HotelSettingValueType::INTEGER,
                'is_active' => true,
            ],
        ];

        foreach ($settings as $setting) {
            HotelSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
