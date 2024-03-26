<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\FlightController;
use Illuminate\Support\Facades\Route;

Route::prefix('flight')->group(function () {

    Route::get('cities', [CityController::class, 'getCities']);
    Route::post('offers', [FlightController::class, 'getOffers']);
});
