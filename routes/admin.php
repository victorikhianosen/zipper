<?php

use Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(function(){
    
    Route::prefix('auth')->group(function(){
        Route::post('login');
    });
});
