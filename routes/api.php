<?php

use App\Http\Controllers\Api\V1\Admin\AccommodationController;
use App\Http\Controllers\Api\V1\Admin\AccommodationProviderMapController;
use App\Http\Controllers\Api\V1\Admin\AccommodationTypeController;
use App\Http\Controllers\Api\V1\Admin\CityController;
use App\Http\Controllers\Api\V1\Admin\CountryController;
use App\Http\Controllers\Api\V1\Admin\FacilityController;
use App\Http\Controllers\Api\V1\Admin\FacilityGroupController;
use App\Http\Controllers\Api\V1\Admin\ManualReservationPurchaseController;
use App\Http\Controllers\Api\V1\Admin\ProviderCityMapController;
use App\Http\Controllers\Api\V1\Admin\ProviderController;
use App\Http\Controllers\Api\V1\Admin\ProviderPricingRuleController;
use App\Http\Controllers\Api\V1\Admin\PurchaseManualRuleController;
use App\Http\Controllers\Api\V1\Admin\RatePlanController;
use App\Http\Controllers\Api\V1\Admin\RatePlanProviderMapController;
use App\Http\Controllers\Api\V1\Admin\RoomCalendarController;
use App\Http\Controllers\Api\V1\Admin\RoomCalendarSnapshotController;
use App\Http\Controllers\Api\V1\Admin\RoomTypeController;
use App\Http\Controllers\Api\V1\Admin\RoomTypeNameController;
use App\Http\Controllers\Api\V1\Admin\RoomTypeProviderMapController;
use App\Http\Controllers\Api\V1\Admin\RuleController;
use App\Http\Controllers\Api\V1\Admin\StateController;
use App\Http\Controllers\Api\V1\Admin\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::apiResource('accommodation-types', AccommodationTypeController::class);
        Route::apiResource('accommodations', AccommodationController::class);
        Route::apiResource('countries', CountryController::class);
        Route::apiResource('states', StateController::class);
        Route::apiResource('cities', CityController::class);
        Route::post('providers/offline', [ProviderController::class, 'storeOffline']);
        Route::apiResource('providers', ProviderController::class);
        Route::apiResource('provider-pricing-rules', ProviderPricingRuleController::class);
        Route::apiResource('purchase-manual-rules', PurchaseManualRuleController::class);

        Route::get('reservations/{reservation_id}', [ManualReservationPurchaseController::class, 'show']);
        Route::patch('reservations/{reservation_id}/manual-purchase', [ManualReservationPurchaseController::class, 'update']);
        Route::post('reservations/{reservation_id}/hotels', [ManualReservationPurchaseController::class, 'storeHotel']);
        Route::post('reservations/{reservation_id}/hotels/{reservation_hotel}/select', [ManualReservationPurchaseController::class, 'selectHotel']);
        Route::post('reservations/{reservation_id}/rooms', [ManualReservationPurchaseController::class, 'storeRoom']);
        Route::post('reservations/{reservation_id}/rooms/{reservation_room}/select', [ManualReservationPurchaseController::class, 'selectRoom']);
        Route::put('reservations/{reservation_id}/guest-assignments', [ManualReservationPurchaseController::class, 'assignGuests']);
        Route::patch('reservations/{reservation_id}/purchases/{reservation_purchase}', [ManualReservationPurchaseController::class, 'updatePurchase']);
        Route::post('reservations/{reservation_id}/purchases/{reservation_purchase}/payments', [ManualReservationPurchaseController::class, 'storePayment']);
        Route::post('reservations/{reservation_id}/confirm', [ManualReservationPurchaseController::class, 'confirm']);
        Route::post('reservations/{reservation_id}/reject', [ManualReservationPurchaseController::class, 'reject']);
        Route::apiResource('system-settings', SystemSettingController::class);
        Route::apiResource('facility-groups', FacilityGroupController::class);
        Route::apiResource('facilities', FacilityController::class);
        Route::apiResource('rate-plans', RatePlanController::class);
        Route::apiResource('room-types', RoomTypeController::class);
        Route::apiResource('room-type-names', RoomTypeNameController::class);
        Route::apiResource('room-calendars', RoomCalendarController::class);
        Route::apiResource('room-calendar-snapshots', RoomCalendarSnapshotController::class);
        Route::apiResource('provider-city-maps', ProviderCityMapController::class);
        Route::apiResource('accommodation-provider-maps', AccommodationProviderMapController::class);
        Route::apiResource('room-type-provider-maps', RoomTypeProviderMapController::class);
        Route::apiResource('rate-plan-provider-maps', RatePlanProviderMapController::class);
        Route::apiResource('rules', RuleController::class);
    });

    require_once 'Front.php';
});
