<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\FlightController;
use Illuminate\Support\Facades\Route;

Route::prefix('flight')->group(function () {

    Route::get('cities', [CityController::class, 'getCities']);
    Route::prefix('offers')->controller(FlightController::class)->group(function () {
        Route::post('/', 'getOffers');
        Route::post('validate-price/{offer}', 'validatePrice');
        Route::post('book', 'bookFlight')->middleware(['auth:sanctum']);
        Route::get('book/{flight}', 'viewFlight'); //->middleware(['auth:sanctum']);
        Route::get('airlines', 'getAirlines');
    });
});
