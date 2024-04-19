<?php

use App\Hooks\PaystackHook;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('paystack/hook', [PaystackHook::class, 'processHook']);

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login/{provider}', 'login')->whereIn('provider', ['password', 'google', 'apple']);
    Route::post('send-password-reset-email', 'sendPasswordResetEmail')->middleware('throttle:3,1');
    Route::post('verify-password-reset-token', 'verifyPasswordResetToken');
    Route::post('reset-password', 'resetPassword');
    Route::post('resend-2fa-email', 'resend2faEmail')->middleware('throttle:3,1');
    Route::post('verify-2fa-token', 'verify2faToken');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('user', 'getAuthUser');
        Route::post('logout', 'logout');
        Route::post('resend-verification-email', 'resendVerificationEmail')->middleware('throttle:3,1');
        Route::post('verify-email', 'verifyEmail');
        Route::post('toggle-2fa', 'toggle2fa');
        Route::post('biometrics/{type}', 'toggleBiometrics')->whereIn('type', ['login', 'transaction']);
        Route::delete('delete-account', 'deleteAccount');
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::controller(NotificationController::class)->group(function () {
        Route::prefix('notifications')->group(function () {
            Route::get('{type?}', 'getUserNotifications');
            Route::get('{notification}/details', 'getNotification');
            Route::put('{notification}/read', 'getNotification');
            Route::put('{notification}/unread', 'markNotificationAsUnread');
            Route::put('read-all', 'markAllNotificationAsRead');
            Route::post('trigger', 'triggerTestNotification');
        });

        Route::prefix('push-notification')->group(function () {
            Route::post('{action}', 'togglePushNotification')->whereIn('action', ['on', 'off']);
            Route::get('status', 'getPushNotificationStatus');
        });
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::post('update', 'updateUserProfile');
        Route::post('update-avatar', 'updateUserProfilePhoto');
        Route::post('update-password', 'updateUserPassword');
    });

    Route::controller(UserController::class)->prefix('user')->group(function () {
        Route::prefix('all')->group(function () {
            Route::get('flights', 'getFlightBookings');
        });
    });

});

Route::prefix('user')->middleware('auth:sanctum')->group(function () {

    Route::controller(UserController::class)->group(function () {
        Route::get('flight-bookings', 'myFlightBookings');
        Route::get('transactions', 'myTransactions');
        Route::get('single-transaction/{transaction}', 'singleTransaction');
    });
});
