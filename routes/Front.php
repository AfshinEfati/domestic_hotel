<?php

use App\Http\Controllers\Api\V1\Front\AccommodationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'front'], function () {
    Route::group(['prefix' => 'accommodations'], function () {
       Route::post('list', [AccommodationController::class, 'list']);
    });
});
