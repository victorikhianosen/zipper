<?php

use App\Http\Controllers\Admin\AppUpdateController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\FaqCategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightSettingController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login')->whereIn('provider', ['password', 'google', 'apple']);
    Route::post('send-password-reset-email', 'sendPasswordResetEmail')->middleware('throttle:3,1');
    Route::post('verify-password-reset-token', 'verifyPasswordResetToken');
    Route::post('reset-password', 'resetPassword');
    Route::post('resend-2fa-email', 'resend2faEmail')->middleware('throttle:3,1');
    Route::post('verify-2fa-token', 'verify2faToken');

    Route::group(['middleware' => 'auth:admin'], function () {
        Route::get('user', 'getAuthUser');
        Route::post('logout', 'logout');
        Route::post('toggle-2fa', 'toggle2fa');
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('updates')->controller(AppUpdateController::class)->group(function () {
        Route::post('create', 'create');
        Route::put('edit', 'edit');
        Route::get('all', 'all');
    });

    Route::prefix('settings')->group(function () {
        // general
        Route::controller(SettingController::class)->group(function () {
            Route::post('create', 'create');
            Route::put('edit', 'edit');
            Route::get('all', 'all');
        });

        // flight-specific

        Route::prefix('flight')->controller(FlightSettingController::class)->group(function () {
            Route::post('create', 'create');
            Route::put('edit', 'edit');
            Route::get('all', 'all');
        });
    });

    // FAQS Category
    Route::prefix('faq/category')->controller(FaqCategoryController::class)->group(function () {
        Route::get('all', 'all');
        Route::post('create', 'create');
        Route::put('edit/{faqCategory}', 'editCategory');
        Route::delete('delete/{faqCategory}', 'deleteCategory');
    });

    // Frequently asked questions
    Route::prefix('faq')->controller(FaqController::class)->group(function () {
        Route::get('all-faqs', 'getAllFaqs');
        Route::post('create-faq', 'createFaq');
        Route::get('single-faq/{faq}', 'singleFaq');
        Route::put('edit-faq/{faq}', 'editFaq');
        Route::delete('delete-faq/{faq}', 'deleteFaq');
    });

    Route::prefix('users')->controller(AdminController::class)->group(function () {
        Route::get('all', 'getUsers');
        Route::get('{user}/flight-bookings', 'getUserFlightBookings');
    });

    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('index', 'dashboardIndex');
        Route::get('flight-bookings', 'getFlightBookings');
        Route::get('single-flight-booking/{flight}', 'getSingleFlightBooking');
        Route::get('transactions', 'getTransactions');
    });
});
