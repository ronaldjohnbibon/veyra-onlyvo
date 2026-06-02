<?php

use App\Admin\Auth\Http\Controllers\AuthController;
use App\Admin\DesignRequests\Http\Controllers\AdminDesignRequestController;
use App\Admin\Sidebar\Http\Controllers\AdminSidebarController;
use App\Admin\SystemSettings\Http\Controllers\AdminSystemSettingController;
use App\Admin\Templates\Http\Controllers\AdminTemplateCatalogController;
use App\Admin\Templates\Http\Controllers\AdminWebsiteTypeController;
use App\Admin\Tenants\Http\Controllers\AdminTenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('api.auth')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('design-requests', [AdminDesignRequestController::class, 'index'])->name('design-requests.index');
        Route::get('design-requests/{designRequest}', [AdminDesignRequestController::class, 'show'])->name('design-requests.show');
        Route::put('design-requests/{designRequest}', [AdminDesignRequestController::class, 'update'])->name('design-requests.update');

        Route::apiResource('sidebars', AdminSidebarController::class);
        Route::put('system-settings', [AdminSystemSettingController::class, 'updateBulk'])->name('system-settings.update-bulk');
        Route::post('system-settings/images', [AdminSystemSettingController::class, 'uploadImage'])->name('system-settings.images.store');
        Route::apiResource('system-settings', AdminSystemSettingController::class)
            ->parameters(['system-settings' => 'systemSetting']);

        Route::apiResource('template-catalog-items', AdminTemplateCatalogController::class)
            ->parameters(['template-catalog-items' => 'templateCatalogItem']);
        Route::apiResource('website-types', AdminWebsiteTypeController::class)
            ->parameters(['website-types' => 'websiteType'])
            ->except(['show']);

        Route::post('tenants/{tenant}/deactivate', [AdminTenantController::class, 'deactivate'])->name('tenants.deactivate');
        Route::post('tenants/{tenant}/reactivate', [AdminTenantController::class, 'reactivate'])->name('tenants.reactivate');
        Route::apiResource('tenants', AdminTenantController::class);
    });
});
