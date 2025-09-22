<?php

use App\Http\Controllers\Api\V1\Admin\AccommodationController;
use App\Http\Controllers\Api\V1\Admin\AccommodationTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/admin'], function () {
    Route::apiResource('accommodation-types', AccommodationTypeController::class);
    Route::apiResource('accommodations', AccommodationController::class);
});
