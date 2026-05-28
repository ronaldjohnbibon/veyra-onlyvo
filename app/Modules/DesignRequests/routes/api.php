<?php

use App\Modules\DesignRequests\Http\Controllers\AdminDesignRequestController;
use App\Modules\DesignRequests\Http\Controllers\DesignRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('api.auth')->group(function (): void {
    Route::get('design-requests', [AdminDesignRequestController::class, 'index'])->name('design-requests.index');
    Route::get('design-requests/{designRequest}', [AdminDesignRequestController::class, 'show'])->name('design-requests.show');
    Route::put('design-requests/{designRequest}', [AdminDesignRequestController::class, 'update'])->name('design-requests.update');
});

Route::prefix('app')->name('app.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::middleware('api.auth')->group(function (): void {
            Route::get('design-requests', [DesignRequestController::class, 'index'])->name('design-requests.index');
            Route::post('design-requests', [DesignRequestController::class, 'store'])->name('design-requests.store');
            Route::get('design-requests/{designRequest}', [DesignRequestController::class, 'show'])->name('design-requests.show');
        });
    });
});
