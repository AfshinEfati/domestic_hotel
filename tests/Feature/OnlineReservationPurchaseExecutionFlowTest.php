<?php

namespace Tests\Feature;

use App\Models\ReservationPurchase;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchasePreflightHandler;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\Jobs\ExecuteOnlineReservationPurchaseJob;
use App\Modules\HotelProviders\V2\Shared\Jobs\PreflightOnlineReservationPurchasesJob;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchasePreflightResult;
use App\Modules\HotelProviders\V2\SnappTrip\Application\SnappTripOnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\SyncBalanceJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Support\Provider\ProviderType;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationGuestType;
use App\Support\Reservation\ReservationHotelType;
use App\Support\Reservation\ReservationRoomType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OnlineReservationPurchaseExecutionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $registry = $this->app->make(HotelProviderRegistry::class);
        $registry->registerOnlinePurchaseHandler(
            SnappTripSettings::PROVIDER_CODE,
            SnappTripOnlinePurchaseHandler::class,
        );
        $registry->registerOnlinePurchasePreflightHandler(
            SnappTripSettings::PROVIDER_CODE,
            FeatureAllowedSnappTripPreflightHandler::class,
        );

        $this->app->instance(
            FeatureAllowedSnappTripPreflightHandler::class,
            new FeatureAllowedSnappTripPreflightHandler(),
        );
    }

    public function test_preflight_execution_and_reconciliation_complete_two_purchase_reservation(): void
    {
        [$reservationId, $providerId, $purchaseIds] = $this->reservationWithOnlinePurchases([
            ['code' => 'FLOW-1', 'amount' => 100_000],
            ['code' => 'FLOW-2', 'amount' => 150_000],
        ]);

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_ends_with($url, '/booking/FLOW-1')) {
                return Http::response([
                    'reservation_code' => 'FLOW-1',
                    'state' => 'confirmed',
                    'price' => 10_000,
                ]);
            }

            if (str_ends_with($url, '/booking/FLOW-2')) {
                return Http::response([
                    'reservation_code' => 'FLOW-2',
                    'state' => 'confirmed',
                    'price' => 15_000,
                ]);
            }

            return Http::response([], 404);
        });

        $this->app->call([
            new PreflightOnlineReservationPurchasesJob($reservationId, $providerId),
            'handle',
        ]);

        Queue::assertPushed(ExecuteOnlineReservationPurchaseJob::class, 2);
        Queue::assertPushed(
            ExecuteOnlineReservationPurchaseJob::class,
            fn (ExecuteOnlineReservationPurchaseJob $job): bool =>
                in_array($job->purchaseId, $purchaseIds, true)
                && $job->queue === 'snapptrip-operations',
        );

        $this->app->call([
            new ExecuteOnlineReservationPurchaseJob($purchaseIds[0]),
            'handle',
        ]);

        $this->assertDatabaseHas('reservation_purchases', [
            'id' => $purchaseIds[0],
            'status' => ReservationStatus::ISSUE_SUCCESS,
            'purchase_amount' => 100_000,
            'provider_status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationStatus::BOOK_REQUESTED,
        ]);

        $this->app->call([
            new ExecuteOnlineReservationPurchaseJob($purchaseIds[1]),
            'handle',
        ]);

        foreach ($purchaseIds as $purchaseId) {
            $this->assertDatabaseHas('reservation_purchases', [
                'id' => $purchaseId,
                'status' => ReservationStatus::ISSUE_SUCCESS,
                'provider_status' => 'confirmed',
            ]);
        }

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationStatus::ISSUE_SUCCESS,
        ]);

        Queue::assertPushed(SyncBalanceJob::class, 1);
        Http::assertSentCount(2);

        $this->app->call([
            new ExecuteOnlineReservationPurchaseJob($purchaseIds[0]),
            'handle',
        ]);

        Http::assertSentCount(2);
        Queue::assertPushed(SyncBalanceJob::class, 1);
    }

    public function test_terminal_provider_failure_moves_purchase_and_reservation_under_review(): void
    {
        [$reservationId, $providerId, $purchaseIds] = $this->reservationWithOnlinePurchases([
            ['code' => 'FLOW-FAILED', 'amount' => 100_000],
        ]);

        Http::fake([
            '*' => Http::response([
                'reservation_code' => 'FLOW-FAILED',
                'state' => 'overbook',
                'price' => 10_000,
            ]),
        ]);

        $this->app->call([
            new PreflightOnlineReservationPurchasesJob($reservationId, $providerId),
            'handle',
        ]);

        Queue::assertPushed(ExecuteOnlineReservationPurchaseJob::class, 1);

        $this->app->call([
            new ExecuteOnlineReservationPurchaseJob($purchaseIds[0]),
            'handle',
        ]);

        $this->assertDatabaseHas('reservation_purchases', [
            'id' => $purchaseIds[0],
            'status' => ReservationStatus::UNDER_REVIEW,
            'provider_status' => 'overbook',
            'purchase_amount' => 100_000,
        ]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationStatus::UNDER_REVIEW,
        ]);
        Queue::assertNotPushed(SyncBalanceJob::class);
        Http::assertSentCount(1);
    }

    public function test_new_purchase_creates_locks_confirms_and_reconciles(): void
    {
        [$reservationId, $providerId, $purchaseId] = $this->newOnlinePurchaseFixture();
        $sequence = [];

        Http::fake(function (Request $request) use (&$sequence) {
            $method = $request->method();
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sequence[] = $method.' '.$path;

            if ($method === 'POST' && $path === '/booking/create') {
                $payload = $request->data();

                $this->assertSame('2026-11-10', $payload['checkin'] ?? null);
                $this->assertSame('2026-11-12', $payload['checkout'] ?? null);
                $this->assertSame(1001, $payload['hotel_id'] ?? null);
                $this->assertSame('09120000000', $payload['phone'] ?? null);
                $this->assertSame('ali@example.test', $payload['email'] ?? null);
                $this->assertSame(501, data_get($payload, 'rooms.0.room_id'));
                $this->assertSame(0, data_get($payload, 'rooms.0.extra_beds'));
                $this->assertSame(0, data_get($payload, 'rooms.0.children'));
                $this->assertSame(0, data_get($payload, 'rooms.0.infants'));
                $this->assertSame('Reza', data_get($payload, 'rooms.0.guests.0.first_name'));
                $this->assertSame('Test', data_get($payload, 'rooms.0.guests.0.last_name'));
                $this->assertFalse(data_get($payload, 'rooms.0.guests.0.foreigner'));

                return Http::response([
                    'reservation_code' => 'NEW-FLOW-1',
                    'state' => 'draft',
                    'price' => 10_000,
                ]);
            }

            if ($method === 'POST' && $path === '/booking/NEW-FLOW-1/lock') {
                return Http::response('', 204);
            }

            if ($method === 'POST' && $path === '/booking/NEW-FLOW-1/confirm') {
                return Http::response('', 204);
            }

            if ($method === 'GET' && $path === '/booking/NEW-FLOW-1') {
                return Http::response([
                    'reservation_code' => 'NEW-FLOW-1',
                    'state' => 'confirmed',
                    'price' => 10_000,
                ]);
            }

            return Http::response([], 404);
        });

        $this->app->call([
            new PreflightOnlineReservationPurchasesJob($reservationId, $providerId),
            'handle',
        ]);

        Queue::assertPushed(
            ExecuteOnlineReservationPurchaseJob::class,
            fn (ExecuteOnlineReservationPurchaseJob $job): bool => $job->purchaseId === $purchaseId,
        );

        $this->app->call([
            new ExecuteOnlineReservationPurchaseJob($purchaseId),
            'handle',
        ]);

        $this->assertDatabaseHas('reservation_purchases', [
            'id' => $purchaseId,
            'confirmation_code' => 'NEW-FLOW-1',
            'status' => ReservationStatus::ISSUE_SUCCESS,
            'purchase_amount' => 100_000,
            'provider_status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationStatus::ISSUE_SUCCESS,
        ]);
        $this->assertNotNull(DB::table('reservation_purchases')->where('id', $purchaseId)->value('issued_at'));
        $this->assertSame([
            'POST /booking/create',
            'POST /booking/NEW-FLOW-1/lock',
            'POST /booking/NEW-FLOW-1/confirm',
            'GET /booking/NEW-FLOW-1',
        ], $sequence);
        Queue::assertPushed(SyncBalanceJob::class, 1);
        Http::assertSentCount(4);
    }

    /**
     * @param array<int,array{code:string,amount:int}> $purchaseSpecs
     * @return array{0:int,1:int,2:array<int,int>}
     */
    private function reservationWithOnlinePurchases(array $purchaseSpecs): array
    {
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
            'fa_name' => 'هتل تست جریان خرید',
            'en_name' => 'Purchase Flow Test Hotel',
            'accommodation_type_id' => $accommodationTypeId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $providerId = DB::table('providers')->insertGetId([
            'fa_name' => 'اسنپ‌تریپ',
            'en_name' => 'SnappTrip',
            'class' => null,
            'code' => SnappTripSettings::PROVIDER_CODE,
            'config' => json_encode([
                'base_url' => 'https://snapptrip.test',
                'api_key' => 'feature-test-key',
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

        DB::table('accommodation_provider_maps')->insert([
            'accommodation_id' => $accommodationId,
            'provider_id' => $providerId,
            'provider_property_id' => '1001',
            'is_disabled' => false,
            'fa_name' => 'هتل تست جریان خرید',
            'en_name' => 'Purchase Flow Test Hotel',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reservationId = DB::table('reservations')->insertGetId([
            'reservation_number' => 'purchase-flow-'.uniqid('', true),
            'agency_id' => 7,
            'status' => ReservationStatus::BOOK_REQUESTED,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'sale_amount' => array_sum(array_column($purchaseSpecs, 'amount')),
            'initial_sale_amount' => array_sum(array_column($purchaseSpecs, 'amount')),
            'validated_sale_amount' => array_sum(array_column($purchaseSpecs, 'amount')),
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

        $purchaseIds = [];
        foreach ($purchaseSpecs as $spec) {
            $purchaseIds[] = DB::table('reservation_purchases')->insertGetId([
                'reservation_hotel_id' => $reservationHotelId,
                'provider_id' => $providerId,
                'status' => ReservationStatus::BOOK_REQUESTED,
                'quoted_provider_id' => $providerId,
                'provider_quoted_amount' => $spec['amount'],
                'purchase_amount' => null,
                'confirmation_code' => $spec['code'],
                'provider_status' => 'availability_pending',
                'expires_at' => null,
                'issued_at' => null,
                'purchase_mode' => PurchaseMethod::ONLINE,
                'manual_reason' => null,
                'manual_rule_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return [$reservationId, $providerId, $purchaseIds];
    }

    /** @return array{0:int,1:int,2:int} */
    private function newOnlinePurchaseFixture(): array
    {
        [$reservationId, $providerId, $purchaseIds] = $this->reservationWithOnlinePurchases([
            ['code' => 'placeholder', 'amount' => 100_000],
        ]);
        $purchaseId = $purchaseIds[0];
        $now = now();

        DB::table('reservation_purchases')->where('id', $purchaseId)->update([
            'confirmation_code' => null,
            'provider_status' => null,
            'updated_at' => $now,
        ]);

        $providerConfig = json_decode(
            (string) DB::table('providers')->where('id', $providerId)->value('config'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        data_set($providerConfig, 'purchase.lock_enabled', true);
        DB::table('providers')->where('id', $providerId)->update([
            'config' => json_encode($providerConfig, JSON_THROW_ON_ERROR),
            'updated_at' => $now,
        ]);

        $reservationHotel = DB::table('reservation_hotels')
            ->where('reservation_id', $reservationId)
            ->first();
        $accommodationMap = DB::table('accommodation_provider_maps')
            ->where('accommodation_id', $reservationHotel->accommodation_id)
            ->where('provider_id', $providerId)
            ->first();
        $countryId = (int) DB::table('countries')->where('iso2', 'IR')->value('id');

        $roomTypeId = DB::table('room_types')->insertGetId([
            'accommodation_id' => $reservationHotel->accommodation_id,
            'fa_name' => 'اتاق تست خرید جدید',
            'en_name' => 'Fresh Purchase Test Room',
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
            'accommodation_provider_map_id' => $accommodationMap->id,
            'provider_room_type_id' => '501',
            'fa_name' => 'اتاق تست خرید جدید',
            'en_name' => 'Fresh Purchase Test Room',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reservationRoomId = DB::table('reservation_rooms')->insertGetId([
            'reservation_hotel_id' => $reservationHotel->id,
            'room_number' => 1,
            'type' => ReservationRoomType::REQUESTED,
            'is_final' => true,
            'room_type_id' => $roomTypeId,
            'rate_plan_id' => null,
            'room_name' => 'Fresh Purchase Test Room',
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

        return [$reservationId, $providerId, $purchaseId];
    }
}

final class FeatureAllowedSnappTripPreflightHandler implements OnlinePurchasePreflightHandler
{
    /** @param Collection<int, ReservationPurchase> $purchases */
    public function check(Collection $purchases): OnlinePurchasePreflightResult
    {
        return OnlinePurchasePreflightResult::allowed();
    }
}
