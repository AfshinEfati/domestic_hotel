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
use App\Support\Reservation\ReservationHotelType;
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
        Queue::assertPushed(SyncBalanceJob::class, 2);
        Http::assertSentCount(2);

        // A delivered duplicate execution job must be a no-op after terminal success.
        $this->app->call([
            new ExecuteOnlineReservationPurchaseJob($purchaseIds[0]),
            'handle',
        ]);

        Http::assertSentCount(2);
        Queue::assertPushed(SyncBalanceJob::class, 2);
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

    /**
     * @param array<int,array{code:string,amount:int}> $purchaseDefinitions
     * @return array{0:int,1:int,2:array<int,int>}
     */
    private function reservationWithOnlinePurchases(array $purchaseDefinitions): array
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
            'fa_name' => 'اسنپ تریپ تست',
            'en_name' => 'SnappTrip Test',
            'class' => null,
            'code' => SnappTripSettings::PROVIDER_CODE,
            'config' => json_encode([
                'base_url' => 'https://snapptrip.test/',
                'api_key' => 'feature-test-api-key',
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

        $reservationId = DB::table('reservations')->insertGetId([
            'reservation_number' => 'execution-flow-'.uniqid('', true),
            'agency_id' => 7,
            'status' => ReservationStatus::BOOK_REQUESTED,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'sale_amount' => array_sum(array_column($purchaseDefinitions, 'amount')),
            'initial_sale_amount' => array_sum(array_column($purchaseDefinitions, 'amount')),
            'validated_sale_amount' => array_sum(array_column($purchaseDefinitions, 'amount')),
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
        foreach ($purchaseDefinitions as $definition) {
            $purchaseIds[] = DB::table('reservation_purchases')->insertGetId([
                'reservation_hotel_id' => $reservationHotelId,
                'provider_id' => $providerId,
                'status' => ReservationStatus::BOOK_REQUESTED,
                'quoted_provider_id' => $providerId,
                'provider_quoted_amount' => $definition['amount'],
                'purchase_amount' => null,
                'confirmation_code' => $definition['code'],
                'provider_status' => null,
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
}

final class FeatureAllowedSnappTripPreflightHandler implements OnlinePurchasePreflightHandler
{
    /** @param Collection<int, ReservationPurchase> $purchases */
    public function check(Collection $purchases): OnlinePurchasePreflightResult
    {
        return OnlinePurchasePreflightResult::allowed();
    }
}
