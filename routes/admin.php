<?php

use App\Admin\Auth\Http\Controllers\AuthController;
use App\Admin\Dashboard\Http\Controllers\AdminDashboardController;
use App\Admin\DesignRequests\Http\Controllers\AdminDesignRequestController;
use App\Admin\Sidebar\Http\Controllers\AdminSidebarController;
use App\Admin\SystemSettings\Http\Controllers\AdminSystemSettingController;
use App\Admin\Templates\Http\Controllers\AdminTemplateCatalogController;
use App\Admin\Templates\Http\Controllers\AdminWebsiteTypeController;
use App\Admin\Tenants\Http\Controllers\AdminTenantController;
use App\Admin\Users\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware(['api.auth', 'admin.ip'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard.index');

        Route::post('admin-users/{adminUser}/deactivate', [AdminUserController::class, 'deactivate'])->name('admin-users.deactivate');
        Route::post('admin-users/{adminUser}/reactivate', [AdminUserController::class, 'reactivate'])->name('admin-users.reactivate');
        Route::post('admin-users/{adminUser}/password-reset', [AdminUserController::class, 'passwordReset'])->name('admin-users.password-reset');
        Route::apiResource('admin-users', AdminUserController::class)
            ->parameters(['admin-users' => 'adminUser'])
            ->except(['destroy']);

        Route::get('design-requests', [AdminDesignRequestController::class, 'index'])->name('design-requests.index');
        Route::get('design-requests/{designRequest}', [AdminDesignRequestController::class, 'show'])->name('design-requests.show');
        Route::put('design-requests/{designRequest}', [AdminDesignRequestController::class, 'update'])->name('design-requests.update');
        Route::post('design-requests/{designRequest}/comments', [AdminDesignRequestController::class, 'comment'])->name('design-requests.comments.store');

        Route::apiResource('sidebars', AdminSidebarController::class);
        Route::get('system-settings/history', [AdminSystemSettingController::class, 'history'])->name('system-settings.history');
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
        Route::get('tenants/{tenant}/workspace', [AdminTenantController::class, 'workspace'])->name('tenants.workspace');
        Route::apiResource('tenants', AdminTenantController::class);
    });
});
