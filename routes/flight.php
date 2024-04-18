<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('flight')->group(function () {

    Route::get('cities', [CityController::class, 'getCities']);
    Route::prefix('offers')->controller(FlightController::class)->group(function () {
        Route::get('airlines', 'getAirlines');
        Route::post('/', 'getOffers');
        Route::get('/{offer}', 'getOffer');
        Route::post('validate-price/{offer}', 'validatePrice');
        Route::prefix('book')->middleware('auth:sanctum')->group(function () {
            Route::post('/', 'bookFlight');
            Route::get('/{flight}', 'viewFlight');
            Route::post('retry-for-failed', 'retryBookingForFailedPayment');
            Route::post('retry-for-pending', 'retryBookingForPendingPayment');
        });

    });

    Route::prefix('pay')->middleware('auth:sanctum')->controller(PaymentController::class)->group(function () {
        Route::post('initiate', 'makePayment');
    });
});
