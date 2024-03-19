<?php

use App\Http\Controllers\AppUpdateController;
use App\Http\Controllers\FaqCategoryController;
use App\Http\Controllers\FaqController;
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
});
