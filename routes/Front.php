<?php

use App\Http\Controllers\Api\V1\Front\AccommodationController;
use App\Http\Controllers\Api\V1\Reservation\ReservationController;
use App\Http\Controllers\Api\V1\Reservation\ReservationPurchaseController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'front'], function () {
    Route::group(['prefix' => 'accommodations'], function () {
        Route::post('list', [AccommodationController::class, 'list']);
        Route::post('availability', [AccommodationController::class, 'getAvailability']);
        Route::post('available-rooms', [
            AccommodationController::class,
            'getAvailableRooms'
        ]);
    });

    Route::group(['prefix' => 'reservations'], function () {
        Route::post('create', [ReservationController::class, 'store']);
        Route::post('{reservation_number}/purchase', [ReservationPurchaseController::class, 'store']);
        Route::get('{reservation_number}', [ReservationController::class,'show']);
    });

    Route::group(['prefix' => 'facility-groups'], function () {
        Route::post('list', [AccommodationController::class, 'getFacilityGroups']);
    });
    Route::group(['prefix' => 'facilities'], function () {
        Route::post('list', [AccommodationController::class, 'getFacilities']);
    });
    Route::group(['prefix' => 'room-types'], function () {
        Route::post('list', [AccommodationController::class, 'getRoomType']);
    });

    Route::group(['prefix' => 'rules'], function () {
        Route::post('list', [AccommodationController::class, 'getRules']);
        Route::post('child-policy', [AccommodationController::class, 'getChildPolicy']);
    });
});
