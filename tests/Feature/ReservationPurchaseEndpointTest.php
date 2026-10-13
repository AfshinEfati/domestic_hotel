<?php

namespace Tests\Feature;

use App\Models\ReservationPurchase;
use App\Models\ReservationRoom;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use App\Modules\HotelProviders\V2\Shared\Jobs\ExecuteOnlineReservationPurchaseJob;
use App\Modules\HotelProviders\V2\Shared\Jobs\PreflightOnlineReservationPurchasesJob;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchaseExecutionResult;
use App\Support\Provider\ProviderType;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationHotelType;
use App\Support\Reservation\ReservationRoomType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReservationPurchaseEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->app->instance(
            FeaturePurchaseOnlineHandler::class,
            new FeaturePurchaseOnlineHandler(),
        );

        $this->app
            ->make(HotelProviderRegistry::class)
            ->registerOnlinePurchaseHandler(
                'feature-purchase',
                FeaturePurchaseOnlineHandler::class,
            );
    }

    public function test_purchase_endpoint_persists_online_plan_and_queues_only_preflight(): void
    {
        [$reservationId, $providerId] = $this->readyReservation();

        $response = $this->postJson(
            "/api/v1/front/reservations/{$reservationId}/purchase"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'purchase request accepted')
            ->assertJsonPath('data.id', $reservationId)
            ->assertJsonPath('data.status', ReservationStatus::BOOK_REQUESTED)
            ->assertJsonPath('data.status_detail.name', 'book_requested')
            ->assertJsonPath('data.purchase_status', 'pending')
            ->assertJsonCount(2, 'data.purchases');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationStatus::BOOK_REQUESTED,
        ]);

        $this->assertDatabaseCount('reservation_purchases', 2);
        $this->assertDatabaseCount('reservation_purchase_segments', 2);

        $amounts = DB::table('reservation_purchases')
            ->where('reservation_hotel_id', function ($query) use ($reservationId): void {
                $query->select('id')
                    ->from('reservation_hotels')
                    ->where('reservation_id', $reservationId)
                    ->limit(1);
            })
            ->orderBy('provider_quoted_amount')
            ->pluck('provider_quoted_amount')
            ->map(fn ($amount): int => (int) $amount)
            ->all();

        $this->assertSame([100_000, 150_000], $amounts);

        DB::table('reservation_purchases')
            ->orderBy('id')
            ->get()
            ->each(function (object $purchase) use ($providerId): void {
                $this->assertSame($providerId, (int) $purchase->provider_id);
                $this->assertSame(PurchaseMethod::ONLINE, (int) $purchase->purchase_mode);
                $this->assertSame(ReservationStatus::BOOK_REQUESTED, (int) $purchase->status);
                $this->assertNull($purchase->manual_reason);
                $this->assertNull($purchase->manual_rule_id);
            });

        Queue::assertPushed(
            PreflightOnlineReservationPurchasesJob::class,
            fn (PreflightOnlineReservationPurchasesJob $job): bool =>
                $job->reservationId === $reservationId
                && $job->providerId === $providerId
                && $job->queue === 'feature-purchase',
        );

        Queue::assertNotPushed(ExecuteOnlineReservationPurchaseJob::class);
    }

    public function test_repeated_purchase_callback_is_idempotent_for_database_plan(): void
    {
        [$reservationId, $providerId] = $this->readyReservation();

        $first = $this->postJson(
            "/api/v1/front/reservations/{$reservationId}/purchase"
        );
        $first->assertOk();

        $purchaseIds = DB::table('reservation_purchases')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $second = $this->postJson(
            "/api/v1/front/reservations/{$reservationId}/purchase"
        );

        $second
            ->assertOk()
            ->assertJsonPath('data.status', ReservationStatus::BOOK_REQUESTED)
            ->assertJsonPath('data.purchase_status', 'pending')
            ->assertJsonCount(2, 'data.purchases');

        $this->assertDatabaseCount('reservation_purchases', 2);
        $this->assertDatabaseCount('reservation_purchase_segments', 2);

        $this->assertSame(
            $purchaseIds,
            DB::table('reservation_purchases')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
        );

        Queue::assertPushed(
            PreflightOnlineReservationPurchasesJob::class,
            fn (PreflightOnlineReservationPurchasesJob $job): bool =>
                $job->reservationId === $reservationId
                && $job->providerId === $providerId,
        );
        Queue::assertNotPushed(ExecuteOnlineReservationPurchaseJob::class);
    }

    /** @return array{0:int,1:int} */
    private function readyReservation(): array
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
            'fa_name' => 'هتل تست خرید',
            'en_name' => 'Purchase Test Hotel',
            'accommodation_type_id' => $accommodationTypeId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $providerId = DB::table('providers')->insertGetId([
            'fa_name' => 'تامین کننده تست خرید',
            'en_name' => 'Feature Purchase Provider',
            'class' => null,
            'code' => 'feature-purchase',
            'config' => json_encode([
                'purchase' => [
                    'online_enabled' => true,
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
            'provider_property_id' => 'feature-hotel-1',
            'is_disabled' => false,
            'fa_name' => 'هتل تست خرید',
            'en_name' => 'Purchase Test Hotel',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('provider_credit_balances')->insert([
            'provider_id' => $providerId,
            'balance' => 10_000_000,
            'low_balance_threshold' => null,
            'low_balance_notified_at' => null,
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reservationId = DB::table('reservations')->insertGetId([
            'reservation_number' => 'feature-purchase-reservation',
            'agency_id' => 7,
            'status' => ReservationStatus::READY_FOR_PAYMENT,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'sale_amount' => 250_000,
            'initial_sale_amount' => 250_000,
            'validated_sale_amount' => 250_000,
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

        foreach ([100_000, 150_000] as $index => $amount) {
            DB::table('reservation_rooms')->insert([
                'reservation_hotel_id' => $reservationHotelId,
                'room_number' => $index + 1,
                'type' => ReservationRoomType::REQUESTED,
                'is_final' => true,
                'room_type_id' => null,
                'rate_plan_id' => null,
                'room_name' => 'Feature Room '.($index + 1),
                'room_calendar_id' => null,
                'provider_id' => $providerId,
                'initial_price' => $amount,
                'validated_price' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return [$reservationId, $providerId];
    }
}

final class FeaturePurchaseOnlineHandler implements OnlinePurchaseHandler
{
    public function groupingKey(ReservationRoom $room): string
    {
        return 'reservation-room-'.$room->id;
    }

    public function queue(): string
    {
        return 'feature-purchase';
    }

    public function execute(
        ReservationPurchase $purchase,
        int $attempt,
    ): OnlinePurchaseExecutionResult {
        return OnlinePurchaseExecutionResult::done();
    }

    public function prepareRetry(ReservationPurchase $purchase): array
    {
        return ['status' => ReservationStatus::BOOK_REQUESTED];
    }
}
