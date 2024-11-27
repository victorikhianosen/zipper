<?php

use App\Http\Controllers\Admin\AdminController as AdminsController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\AppUpdateController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\FaqCategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightSettingController;
use App\Http\Controllers\ImageKitSignatureController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Route::get('img-sig', [ImageKitSignatureController::class, 'system']);
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
        Route::get('img-sig', [ImageKitSignatureController::class, 'system']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('updates')->controller(AppUpdateController::class)->group(function () {
        Route::post('create', 'create');
        Route::put('edit', 'edit');
        Route::get('all', 'all');
    });

    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminsController::class, 'index'])->middleware('permission:View Admins');
        Route::post('/', [AdminsController::class, 'store'])->middleware('permission:Add Admin');
        Route::get('{admin}', [AdminsController::class, 'show'])->middleware('permission:View Admins');
        Route::put('{admin}', [AdminsController::class, 'update'])->middleware('permission:Toggle Admin Status');
        Route::put('{admin}/assign-role/{role}', [AdminsController::class, 'changeRole'])->middleware('permission:Change Admin Role');
    });

    Route::prefix('alerts')->controller(AlertController::class)->group(function () {
        Route::get('', 'index')->middleware('permission:View Alerts');
        Route::post('', 'store')->middleware('permission:Add Alert');
        Route::get('{alert}', 'show')->middleware('permission:View Alerts');
        Route::post('{alert}', 'update')->middleware('permission:Edit Alert');
        Route::delete('{alert}', 'destroy')->middleware('permission:Delete Alert');
        Route::patch('{alert}/restore', 'restore')->middleware('permission:Restore Alert');
        Route::post('{alert}/dispatch', 'dispatch')->middleware('permission:Dispatch Alert');
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
            Route::get('all-deals', 'getFlightDeals');
            Route::post('create-deals', 'createFlightDeals');
            Route::delete('delete-deals/{offer}', 'deleteFlightDeal');
            Route::get('single-deal/{offer}', 'singleFlightDeal');
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
        Route::get('{user}/details', 'getUserDetails');
        Route::put('{user}/toggle-status', 'toggleUserStatus');
        Route::get('{user}/flight-bookings', 'getUserFlightBookings');
        Route::put('make-reseller/{user}', 'makeReseller');
    });

    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('index', 'dashboardIndex');
        Route::get('flight-bookings', 'getFlightBookings');
        Route::get('single-flight-booking/{flight}', 'getSingleFlightBooking');
        Route::get('transactions', 'getTransactions');
    });

    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:View Roles');
        Route::post('/', 'store')->middleware('permission:Add Role');
        Route::get('{role}', 'show')->middleware('permission:View Roles');
        Route::post('{role}', 'update')->middleware('permission:Edit Role');
        Route::delete('{role}', 'delete')->middleware('permission:Delete Role');
    });
    Route::get('permissions', [RoleController::class, 'permissions'])->middleware('permission:View Roles');

});
