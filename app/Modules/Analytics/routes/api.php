<?php

use App\Modules\Analytics\Http\Controllers\AnalyticsController;
use App\Modules\Analytics\Http\Controllers\PublicCtaTrackingController;
use App\Modules\Analytics\Http\Controllers\PublicVisitorTrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->name('public.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::post('analytics/visits', [PublicVisitorTrackingController::class, 'store'])->name('analytics.visits.store');
        Route::post('analytics/cta-events', [PublicCtaTrackingController::class, 'store'])->name('analytics.cta-events.store');
    });
});

Route::prefix('app')->name('app.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::middleware('api.auth')->group(function (): void {
            Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        });
    });
});
