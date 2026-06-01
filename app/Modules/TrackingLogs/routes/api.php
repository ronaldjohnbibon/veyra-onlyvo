<?php

use App\Modules\TrackingLogs\Http\Controllers\TrackingLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->name('app.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::middleware('api.auth')->group(function (): void {
            Route::get('tracking-logs', [TrackingLogController::class, 'index'])->name('tracking-logs.index');
        });
    });
});
