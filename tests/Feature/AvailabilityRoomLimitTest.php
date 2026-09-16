<?php

namespace Tests\Feature;

use App\Http\Requests\Front\AvailabilityRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AvailabilityRoomLimitTest extends TestCase
{
    private function payload(int $roomCount): array
    {
        return [
            'state' => 'tehran',
            'city' => 'tehran',
            'check_in' => now()->addDays(7)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'rooms' => array_fill(0, $roomCount, [
                'passengers' => [
                    ['type' => 'adl', 'age' => 30],
                ],
            ]),
        ];
    }

    public function test_five_rooms_pass_availability_request_validation(): void
    {
        $validation = Validator::make(
            $this->payload(5),
            (new AvailabilityRequest())->rules()
        );

        $this->assertTrue($validation->passes(), $validation->errors()->first());
    }

    public function test_six_rooms_fail_availability_request_validation(): void
    {
        $validation = Validator::make(
            $this->payload(6),
            (new AvailabilityRequest())->rules()
        );

        $this->assertTrue($validation->fails());
        $this->assertArrayHasKey('rooms', $validation->errors()->toArray());
    }

    public function test_api_rejects_six_rooms_with_validation_error(): void
    {
        $this->postJson('/api/v1/front/accommodations/availability', $this->payload(6))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rooms']);
    }
}
