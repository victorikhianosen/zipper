<?php

use App\Http\Controllers\AppUpdateController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    // Route::prefix('auth')->group(function () {
    //     Route::post('login');
    // });

    Route::prefix('updates')->controller(AppUpdateController::class)->group(function () {
        Route::post('create', 'create');
        Route::put('edit', 'edit');
        Route::get('all', 'all');
    });

    Route::prefix('settings')->controller(SettingController::class)->group(function () {
        Route::post('create', 'create');
        Route::put('edit', 'edit');
        Route::get('all', 'all');
    });
});
