<?php

use App\Modules\Tenant\Http\Controllers\AdminTenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('api.auth')->group(function (): void {
    Route::post('tenants/{tenant}/deactivate', [AdminTenantController::class, 'deactivate'])->name('tenants.deactivate');
    Route::post('tenants/{tenant}/reactivate', [AdminTenantController::class, 'reactivate'])->name('tenants.reactivate');
    Route::apiResource('tenants', AdminTenantController::class);
});
