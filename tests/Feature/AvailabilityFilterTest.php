<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\City;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * تست فیلتری تمام هتل‌ها و اتاق‌های موجود با شرایط مناسب
     */
    public function test_get_availability_returns_only_suitable_hotels_and_rooms()
    {
        // تهیه داده‌ها
        $state = State::factory()->create(['fa_name' => 'تهران']);
        $city = City::factory()->create(['state_id' => $state->id, 'fa_name' => 'تهران']);

        // ایجاد هتلی با سیاست کودک
        $accommodation = Accommodation::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        HotelChildPolicy::create([
            'accommodation_id' => $accommodation->id,
            'max_infant_age' => 2,
            'max_child_age' => 12,
            'max_children_covered' => 2,
            'max_infants_covered' => 1,
            'status' => true,
        ]);

        // ایجاد انواع اتاق
        $roomType = RoomType::factory()->create([
            'accommodation_id' => $accommodation->id,
            'capacity' => 2,
            'extra_capacity' => 1,
            'out_of_service' => false,
        ]);

        // ایجاد تقویم اتاق (موجودی)
        $checkIn = Carbon::now()->addDay();
        $checkOut = $checkIn->copy()->addDays(2);

        for ($date = $checkIn->copy(); $date < $checkOut; $date->addDay()) {
            RoomCalendar::create([
                'accommodation_id' => $accommodation->id,
                'room_type_id' => $roomType->id,
                'day' => $date->toDateString(),
                'inventory' => 5,
                'closed' => false,
            ]);
        }

        // درخواست در دسترس بودن
        $response = $this->postJson('/api/v1/availability', [
            'state' => 'تهران',
            'city' => 'تهران',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'rooms' => [
                [
                    'passengers' => [
                        ['type' => 'adult'],
                        ['type' => 'child', 'age' => 5],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // بررسی اینکه هتل برگردانده شده است
        $this->assertNotEmpty($data);
        $this->assertEquals($accommodation->id, $data[0]['id']);

        // بررسی اینکه اتاق مناسب برگردانده شده است
        $this->assertNotEmpty($data[0]['available_rooms']);
        $this->assertEquals($roomType->id, $data[0]['available_rooms'][0]['id']);

        // بررسی اینکه قیمت‌ها موجود هستند
        $availableRoom = $data[0]['available_rooms'][0];
        $this->assertNotNull($availableRoom['pricing']);
        $this->assertNotNull($availableRoom['total_price']);
        $this->assertNotEmpty($availableRoom['nightly_prices']);
        
        // بررسی قیمت‌های شبانه
        $this->assertCount(1, $availableRoom['nightly_prices']); // 1 شب (2 روز - 1)
        $this->assertArrayHasKey('adult', $availableRoom['nightly_prices'][0]);
        $this->assertArrayHasKey('child', $availableRoom['nightly_prices'][0]);
        
        // بررسی قیمت بزرگسالان و کودکان
        $this->assertTrue($availableRoom['pricing']['adult'] > 0);
        $this->assertTrue($availableRoom['pricing']['child'] > 0);
    }

    /**
     * تست عدم برگرداندن هتل‌هایی که سیاست کودک را ندارند
     */
    public function test_accommodation_without_child_policy_rejects_children()
    {
        $state = State::factory()->create(['fa_name' => 'تهران']);
        $city = City::factory()->create(['state_id' => $state->id, 'fa_name' => 'تهران']);

        // هتلی بدون سیاست کودک
        $accommodation = Accommodation::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $roomType = RoomType::factory()->create([
            'accommodation_id' => $accommodation->id,
            'capacity' => 2,
            'out_of_service' => false,
        ]);

        $checkIn = Carbon::now()->addDay();
        $checkOut = $checkIn->copy()->addDays(2);

        for ($date = $checkIn->copy(); $date < $checkOut; $date->addDay()) {
            RoomCalendar::create([
                'accommodation_id' => $accommodation->id,
                'room_type_id' => $roomType->id,
                'day' => $date->toDateString(),
                'inventory' => 5,
                'closed' => false,
            ]);
        }

        // درخواست با کودک - باید رد شود
        $response = $this->postJson('/api/v1/availability', [
            'state' => 'تهران',
            'city' => 'تهران',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'rooms' => [
                [
                    'passengers' => [
                        ['type' => 'adult'],
                        ['type' => 'child', 'age' => 5],  // کودک
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // بررسی اینکه هتل برگردانده نشده است
        $this->assertEmpty($data);
    }

    /**
     * تست عدم برگرداندن اتاق‌های پر
     */
    public function test_unavailable_rooms_are_not_returned()
    {
        $state = State::factory()->create(['fa_name' => 'تهران']);
        $city = City::factory()->create(['state_id' => $state->id, 'fa_name' => 'تهران']);

        $accommodation = Accommodation::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $roomType = RoomType::factory()->create([
            'accommodation_id' => $accommodation->id,
            'capacity' => 2,
            'out_of_service' => false,
        ]);

        $checkIn = Carbon::now()->addDay();
        $checkOut = $checkIn->copy()->addDays(2);

        // اولین روز موجود نیست
        RoomCalendar::create([
            'accommodation_id' => $accommodation->id,
            'room_type_id' => $roomType->id,
            'day' => $checkIn->toDateString(),
            'inventory' => 0,  // دسترس ندارد
            'closed' => false,
        ]);

        RoomCalendar::create([
            'accommodation_id' => $accommodation->id,
            'room_type_id' => $roomType->id,
            'day' => $checkIn->copy()->addDay()->toDateString(),
            'inventory' => 5,
            'closed' => false,
        ]);

        $response = $this->postJson('/api/v1/availability', [
            'state' => 'تهران',
            'city' => 'تهران',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'rooms' => [
                [
                    'passengers' => [
                        ['type' => 'adult'],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // بررسی اینکه هتل برگردانده نشده است
        $this->assertEmpty($data);
    }
}
