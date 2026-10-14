<?php

namespace Tests\Feature;

use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripOnlinePurchaseHandler;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Support\Provider\ProviderType;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationGuestType;
use App\Support\Reservation\ReservationHotelType;
use App\Support\Reservation\ReservationRoomType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SnappTripOnlinePurchaseResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_provider_429_keeps_purchase_pending_for_retry(): void
    {
        [$reservationId, $providerId, $purchaseId] = $this->purchaseFixture(
            confirmationCode: 'RATE-1',
            providerStatus: 'availability_pending',
        );

        Http::fake([
            '*' => Http::response([], 429, ['Retry-After' => '1']),
        ]);

        try {
            $result = $this->execute($purchaseId, 1);

            $this->assertTrue($result->retry);
            $this->assertSame(15, $result->retryAfterSeconds);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::BOOK_REQUESTED,
                'confirmation_code' => 'RATE-1',
            ]);
            $this->assertDatabaseHas('reservations', [
                'id' => $reservationId,
                'status' => ReservationStatus::BOOK_REQUESTED,
            ]);
            Http::assertSentCount(1);
        } finally {
            $this->clearQuota($providerId);
        }
    }

    public function test_pending_state_retries_then_moves_to_review_at_poll_limit(): void
    {
        [$reservationId, $providerId, $purchaseId] = $this->purchaseFixture(
            confirmationCode: 'PENDING-1',
            providerStatus: 'availability_pending',
        );

        Http::fake([
            '*' => Http::response([
                'reservation_code' => 'PENDING-1',
                'state' => 'availability_pending',
                'price' => 10_000,
            ]),
        ]);

        try {
            $first = $this->execute($purchaseId, 1);
            $this->assertTrue($first->retry);
            $this->assertSame(15, $first->retryAfterSeconds);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::BOOK_REQUESTED,
                'provider_status' => 'availability_pending',
                'purchase_amount' => 100_000,
            ]);

            $last = $this->execute($purchaseId, 20);
            $this->assertFalse($last->retry);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::UNDER_REVIEW,
                'provider_status' => 'availability_pending',
            ]);
            $this->assertDatabaseHas('reservations', [
                'id' => $reservationId,
                'status' => ReservationStatus::UNDER_REVIEW,
            ]);
            Http::assertSentCount(2);
        } finally {
            $this->clearQuota($providerId);
        }
    }

    public function test_existing_confirmation_recovers_after_connection_failure_without_duplicate_create(): void
    {
        [$reservationId, $providerId, $purchaseId] = $this->purchaseFixture(
            confirmationCode: 'RESUME-1',
            providerStatus: 'availability_pending',
        );
        $requests = [];
        $calls = 0;

        Http::fake(function (Request $request) use (&$requests, &$calls) {
            $requests[] = $request->method().' '.(string) parse_url($request->url(), PHP_URL_PATH);
            $calls++;

            if ($calls === 1) {
                throw new ConnectionException('simulated connection failure');
            }

            return Http::response([
                'reservation_code' => 'RESUME-1',
                'state' => 'confirmed',
                'price' => 10_000,
            ]);
        });

        try {
            $first = $this->execute($purchaseId, 1);
            $this->assertTrue($first->retry);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::BOOK_REQUESTED,
                'confirmation_code' => 'RESUME-1',
            ]);

            $second = $this->execute($purchaseId, 2);
            $this->assertFalse($second->retry);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::ISSUE_SUCCESS,
                'confirmation_code' => 'RESUME-1',
                'provider_status' => 'confirmed',
                'purchase_amount' => 100_000,
            ]);
            $this->assertDatabaseHas('reservations', [
                'id' => $reservationId,
                'status' => ReservationStatus::ISSUE_SUCCESS,
            ]);
            $this->assertSame([
                'GET /booking/RESUME-1',
                'GET /booking/RESUME-1',
            ], $requests);
        } finally {
            $this->clearQuota($providerId);
        }
    }

    public function test_price_increase_after_create_stops_before_confirm_and_marks_review(): void
    {
        [$reservationId, $providerId, $purchaseId] = $this->purchaseFixture(
            confirmationCode: null,
            providerStatus: null,
            withBookingPayload: true,
        );
        $requests = [];

        Http::fake(function (Request $request) use (&$requests) {
            $requests[] = $request->method().' '.(string) parse_url($request->url(), PHP_URL_PATH);

            return Http::response([
                'reservation_code' => 'PRICE-1',
                'state' => 'draft',
                'price' => 12_000,
            ]);
        });

        try {
            $result = $this->execute($purchaseId, 1);

            $this->assertFalse($result->retry);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::UNDER_REVIEW,
                'confirmation_code' => 'PRICE-1',
                'provider_status' => 'price_increased',
                'purchase_amount' => 120_000,
            ]);
            $this->assertDatabaseHas('reservations', [
                'id' => $reservationId,
                'status' => ReservationStatus::UNDER_REVIEW,
            ]);
            $this->assertSame(['POST /booking/create'], $requests);
        } finally {
            $this->clearQuota($providerId);
        }
    }

    public function test_create_connection_failure_is_ambiguous_and_never_blindly_retried(): void
    {
        [$reservationId, $providerId, $purchaseId] = $this->purchaseFixture(
            confirmationCode: null,
            providerStatus: null,
            withBookingPayload: true,
        );
        $requests = [];

        Http::fake(function (Request $request) use (&$requests) {
            $requests[] = $request->method().' '.(string) parse_url($request->url(), PHP_URL_PATH);
            throw new ConnectionException('simulated ambiguous create failure');
        });

        try {
            $result = $this->execute($purchaseId, 1);

            $this->assertFalse($result->retry);
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::UNDER_REVIEW,
                'confirmation_code' => null,
                'provider_status' => 'create_connection_ambiguous',
            ]);
            $this->assertDatabaseHas('reservations', [
                'id' => $reservationId,
                'status' => ReservationStatus::UNDER_REVIEW,
            ]);
            $this->assertSame(['POST /booking/create'], $requests);
        } finally {
            $this->clearQuota($providerId);
        }
    }

    private function execute(int $purchaseId, int $attempt)
    {
        $purchase = $this->app
            ->make(ReservationPurchaseRepositoryInterface::class)
            ->findForOnlineExecution($purchaseId);

        $this->assertNotNull($purchase);

        return $this->app
            ->make(SnappTripOnlinePurchaseHandler::class)
            ->execute($purchase, $attempt);
    }

    /** @return array{0:int,1:int,2:int} */
    private function purchaseFixture(
        ?string $confirmationCode,
        ?string $providerStatus,
        bool $withBookingPayload = false,
    ): array {
        $now = now();

        $countryId = DB::table('countries')->insertGetId([
            'fa_name' => 'ایران',
            'en_name' => 'Iran',
            'iso2' => 'IR',
            'iso3' => 'IRN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cityId = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'state_id' => null,
            'fa_name' => 'تهران',
            'en_name' => 'Tehran',
            'is_popular' => false,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $accommodationTypeId = DB::table('accommodation_types')->insertGetId([
            'fa_name' => 'هتل',
            'en_name' => 'Hotel',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $accommodationId = DB::table('accommodations')->insertGetId([
            'city_id' => $cityId,
            'fa_name' => 'هتل تست پایداری خرید',
            'en_name' => 'Purchase Resilience Hotel',
            'accommodation_type_id' => $accommodationTypeId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $providerId = DB::table('providers')->insertGetId([
            'fa_name' => 'اسنپ‌تریپ',
            'en_name' => 'SnappTrip',
            'class' => null,
            'code' => 'snap',
            'config' => json_encode([
                'base_url' => 'https://snapptrip.test',
                'api_key' => 'resilience-test-key',
                'purchase' => [
                    'online_enabled' => true,
                    'lock_enabled' => false,
                    'status_poll_seconds' => 15,
                    'status_poll_attempts' => 20,
                ],
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'is_online' => true,
            'provider_type' => ProviderType::INTEGRATION,
            'accommodation_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $accommodationMapId = DB::table('accommodation_provider_maps')->insertGetId([
            'accommodation_id' => $accommodationId,
            'provider_id' => $providerId,
            'provider_property_id' => '1001',
            'is_disabled' => false,
            'fa_name' => 'هتل تست پایداری خرید',
            'en_name' => 'Purchase Resilience Hotel',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reservationId = DB::table('reservations')->insertGetId([
            'reservation_number' => 'resilience-'.uniqid('', true),
            'agency_id' => 7,
            'status' => ReservationStatus::BOOK_REQUESTED,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'sale_amount' => 100_000,
            'initial_sale_amount' => 100_000,
            'validated_sale_amount' => 100_000,
            'validation_error' => null,
            'tax_amount' => 0,
            'commission_amount' => null,
            'booker_first_name' => 'Ali',
            'booker_last_name' => 'Test',
            'booker_mobile' => '09120000000',
            'booker_email' => 'ali@example.test',
            'acc_code' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reservationHotelId = DB::table('reservation_hotels')->insertGetId([
            'reservation_id' => $reservationId,
            'accommodation_id' => $accommodationId,
            'type' => ReservationHotelType::REQUESTED,
            'is_final' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $purchaseId = DB::table('reservation_purchases')->insertGetId([
            'reservation_hotel_id' => $reservationHotelId,
            'provider_id' => $providerId,
            'status' => ReservationStatus::BOOK_REQUESTED,
            'quoted_provider_id' => $providerId,
            'provider_quoted_amount' => 100_000,
            'purchase_amount' => null,
            'confirmation_code' => $confirmationCode,
            'provider_status' => $providerStatus,
            'expires_at' => null,
            'issued_at' => null,
            'purchase_mode' => PurchaseMethod::ONLINE,
            'manual_reason' => null,
            'manual_rule_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($withBookingPayload) {
            $roomTypeId = DB::table('room_types')->insertGetId([
                'accommodation_id' => $accommodationId,
                'fa_name' => 'اتاق تست پایداری',
                'en_name' => 'Resilience Room',
                'capacity' => 2,
                'extra_capacity' => 1,
                'single_bed_count' => null,
                'double_bed_count' => 1,
                'sofa_bed_count' => null,
                'out_of_service' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('room_type_provider_maps')->insert([
                'room_type_id' => $roomTypeId,
                'provider_id' => $providerId,
                'accommodation_provider_map_id' => $accommodationMapId,
                'provider_room_type_id' => '501',
                'fa_name' => 'اتاق تست پایداری',
                'en_name' => 'Resilience Room',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $reservationRoomId = DB::table('reservation_rooms')->insertGetId([
                'reservation_hotel_id' => $reservationHotelId,
                'room_number' => 1,
                'type' => ReservationRoomType::REQUESTED,
                'is_final' => true,
                'room_type_id' => $roomTypeId,
                'rate_plan_id' => null,
                'room_name' => 'Resilience Room',
                'room_calendar_id' => null,
                'provider_id' => $providerId,
                'initial_price' => 100_000,
                'validated_price' => 100_000,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('reservation_guests')->insert([
                'reservation_room_id' => $reservationRoomId,
                'type' => ReservationGuestType::ADULT,
                'service' => null,
                'first_name' => 'Reza',
                'last_name' => 'Test',
                'gender' => null,
                'birth_date' => '1990-01-01',
                'country_id' => $countryId,
                'national_id' => null,
                'passport_number' => null,
                'passport_issuer_country_id' => null,
                'passport_expiry_date' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('reservation_purchase_segments')->insert([
                'reservation_purchase_id' => $purchaseId,
                'reservation_room_id' => $reservationRoomId,
                'from_date' => '2026-11-10',
                'to_date' => '2026-11-12',
                'nightly_purchase_amount' => 50_000,
                'nightly_extra_bed_purchase_amount' => null,
                'nightly_child_purchase_amount' => null,
                'nightly_infant_purchase_amount' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return [$reservationId, $providerId, $purchaseId];
    }

    private function clearQuota(int $providerId): void
    {
        Cache::store('redis')->forget("snapptrip-v2-api-request-times:{$providerId}");
        Cache::store('redis')->forget("snapptrip-v2-api-cooldown:{$providerId}");
    }
}
