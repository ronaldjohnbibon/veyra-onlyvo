<?php

use App\Modules\Sidebar\Http\Controllers\AdminSidebarController;
use App\Modules\Sidebar\Http\Controllers\TenantSidebarController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('api.auth')->group(function (): void {
    Route::apiResource('sidebars', AdminSidebarController::class);
});

Route::prefix('app')->name('app.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::middleware('api.auth')->group(function (): void {
            Route::apiResource('sidebars', TenantSidebarController::class);
        });
    });
});
