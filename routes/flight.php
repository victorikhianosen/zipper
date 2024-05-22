<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\FlightSettingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('flight')->group(function () {

    Route::get('cities', [CityController::class, 'getCities']);
    Route::prefix('offers')->controller(FlightController::class)->group(function () {
        Route::get('airlines', 'getAirlines');
        Route::post('/', 'getOffers');
        Route::get('/{offer}', 'getOffer');
        Route::post('validate-price/{offer}', 'validatePrice');

        Route::prefix('wishlist')->middleware(['auth:sanctum'])->group(function () {
            Route::put('add/{offer}', 'addToWishlist');
            Route::delete('remove/{offer}', 'deleteFromWishlist');
            Route::get('all', 'getWishLists');
        });

        Route::prefix('book')->middleware('auth:sanctum')->group(function () {
            Route::post('/', 'bookFlight');
            Route::get('/{flight}', 'viewFlight');
            Route::post('complete-flight-booking', 'completeFlightBooking');
            Route::post('retry-for-failed', 'retryBookingForFailedPayment');
            Route::post('retry-for-pending', 'retryBookingForPendingPayment');
        });
    });

    Route::prefix('deals')->middleware('auth:sanctum')->controller(FlightSettingController::class)->group(function () {
        Route::get('all-deals', 'getFlightDeals');
        Route::get('single-deal/{offer}', 'singleFlightDeal');
    });

    Route::prefix('pay')->middleware('auth:sanctum')->controller(PaymentController::class)->group(function () {
        Route::post('initiate', 'makePayment');
    });
});
