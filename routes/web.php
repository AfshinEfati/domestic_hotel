<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController as L5SwaggerController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::get('documentation', [L5SwaggerController::class, 'api'])
        ->name('l5-swagger.default.api');

    Route::get('documentation.json', [L5SwaggerController::class, 'docs'])
        ->name('l5-swagger.default.docs');
});
