<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->name('app.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);

    Route::tenanted(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('api.auth')->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('login', [AuthController::class, 'adminLogin']);

    Route::middleware('api.auth')->group(function (): void {
        Route::get('me', [AuthController::class, 'adminMe']);
        Route::post('logout', [AuthController::class, 'adminLogout']);
    });
});
