<?php

namespace Tests\Feature;

use App\Http\Requests\Reservation\StoreReservationRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationCreateRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The tests work with both an empty SQLite connection and migrated test databases.
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table): void {
                $table->id();
                $table->string('fa_name', 120);
                $table->string('iso2', 2)->nullable();
            });
        }
        DB::table('countries')->updateOrInsert(['id' => 990001], [
            'fa_name' => 'ایران', 'iso2' => 'IR',
        ]);
        DB::table('countries')->updateOrInsert(['id' => 990002], [
            'fa_name' => 'ترکیه', 'iso2' => 'TR',
        ]);

        Route::post('/__test/reservation-create-request', static function (StoreReservationRequest $request) {
            return response()->json($request->validated());
        });
    }

    private function payload(array $guest): array
    {
        return [
            'agency_id' => 1,
            'check_in' => '2027-03-04',
            'check_out' => '2027-03-05',
            'first_name' => 'Afshin',
            'last_name' => 'Efati',
            'mobile' => '09120000000',
            'hotel' => [
                'rooms' => [[
                    'room_calendar_id' => 5480,
                    'price' => 25000000,
                    'guests' => [array_merge([
                        'type' => 1,
                        'first_name' => 'Ali',
                        'last_name' => 'Ahmadi',
                    ], $guest)],
                ]],
            ],
        ];
    }

    public function test_iranian_guest_needs_only_calendar_id_and_national_id(): void
    {
        $response = $this->postJson('/__test/reservation-create-request', $this->payload([
            'country_id' => 990001,
            'national_id' => '0012345678',
        ]));
        $response->assertOk()->assertJsonPath('hotel.rooms.0.room_calendar_id', 5480);
        $this->assertArrayNotHasKey('accommodation_id', $response->json('hotel'));
    }

    public function test_nationality_is_required_for_every_guest(): void
    {
        $this->postJson('/__test/reservation-create-request', $this->payload([
            'national_id' => '0012345678',
        ]))->assertUnprocessable()->assertJsonValidationErrors('hotel.rooms.0.guests.0.country_id');
    }

    public function test_iranian_guest_cannot_omit_or_blank_national_id(): void
    {
        foreach ([[], ['national_id' => '   '], ['passport_number' => 'P123456']] as $identity) {
            $this->postJson('/__test/reservation-create-request', $this->payload(array_merge([
                'country_id' => 990001,
            ], $identity)))->assertUnprocessable()
                ->assertJsonValidationErrors('hotel.rooms.0.guests.0.national_id');
        }
    }

    public function test_non_iranian_guest_requires_passport_not_just_national_id(): void
    {
        $this->postJson('/__test/reservation-create-request', $this->payload([
            'country_id' => 990002,
            'national_id' => '1234567890',
        ]))->assertUnprocessable()->assertJsonValidationErrors('hotel.rooms.0.guests.0.passport_number');
    }

    public function test_foreign_guest_with_passport_and_issuer_passes(): void
    {
        $this->postJson('/__test/reservation-create-request', $this->payload([
            'country_id' => 990002,
            'passport_number' => 'P12345678',
            'passport_issuer_country_id' => 990002,
            'passport_expiry_date' => '2035-01-01',
        ]))->assertOk()->assertJsonPath('hotel.rooms.0.guests.0.country_id', 990002);
    }
}
