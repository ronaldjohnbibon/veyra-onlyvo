<?php

use App\Admin\AuditLogs\Http\Controllers\AdminAuditLogController;
use App\Admin\Auth\Http\Controllers\AuthController;
use App\Admin\Dashboard\Http\Controllers\AdminDashboardController;
use App\Admin\DesignRequests\Http\Controllers\AdminDesignRequestController;
use App\Admin\Leads\Http\Controllers\AdminLeadController;
use App\Admin\Operations\Http\Controllers\AdminOperationsController;
use App\Admin\Sidebar\Http\Controllers\AdminSidebarController;
use App\Admin\SystemSettings\Http\Controllers\AdminSystemSettingController;
use App\Admin\Templates\Http\Controllers\AdminTemplateCatalogController;
use App\Admin\Templates\Http\Controllers\AdminWebsiteTypeController;
use App\Admin\Tenants\Http\Controllers\AdminTenantController;
use App\Admin\Users\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:admin-login')
        ->name('login');

    Route::middleware(['api.auth', 'token.name:admin_token', 'admin.ip'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('logs/export', [AdminAuditLogController::class, 'export'])->name('logs.export');
        Route::get('logs/{log}', [AdminAuditLogController::class, 'show'])->name('logs.show');
        Route::get('logs', [AdminAuditLogController::class, 'index'])->name('logs.index');
        Route::get(
            'audit-logs/export',
            fn () => redirect()->route('admin.logs.export', request()->query()),
        )->name('audit-logs.export');
        Route::get(
            'audit-logs/{log}',
            fn (string $log) => redirect()->route('admin.logs.show', ['log' => $log] + request()->query()),
        )->name('audit-logs.show');
        Route::get(
            'audit-logs',
            fn () => redirect()->route('admin.logs.index', request()->query()),
        )->name('audit-logs.index');
        Route::get('operations', [AdminOperationsController::class, 'index'])->name('operations.index');

        Route::post('admin-users/{adminUser}/deactivate', [AdminUserController::class, 'deactivate'])->name('admin-users.deactivate');
        Route::post('admin-users/{adminUser}/reactivate', [AdminUserController::class, 'reactivate'])->name('admin-users.reactivate');
        Route::post('admin-users/{adminUser}/password-reset', [AdminUserController::class, 'passwordReset'])->name('admin-users.password-reset');
        Route::apiResource('admin-users', AdminUserController::class)
            ->parameters(['admin-users' => 'adminUser']);

        Route::get('design-requests/board', [AdminDesignRequestController::class, 'board'])->name('design-requests.board');
        Route::get('design-requests/workload', [AdminDesignRequestController::class, 'workload'])->name('design-requests.workload');
        Route::get('design-requests', [AdminDesignRequestController::class, 'index'])->name('design-requests.index');
        Route::get('design-requests/{designRequest}', [AdminDesignRequestController::class, 'show'])->name('design-requests.show');
        Route::put('design-requests/{designRequest}', [AdminDesignRequestController::class, 'update'])->name('design-requests.update');
        Route::post('design-requests/{designRequest}/comments', [AdminDesignRequestController::class, 'comment'])->name('design-requests.comments.store');
        Route::post('design-requests/{designRequest}/convert-template-improvement', [AdminDesignRequestController::class, 'convertTemplateImprovement'])->name('design-requests.convert-template-improvement');
        Route::post('design-requests/{designRequest}/convert-catalog-change', [AdminDesignRequestController::class, 'convertCatalogChange'])->name('design-requests.convert-catalog-change');
        Route::post('design-requests/{designRequest}/link-completed-work', [AdminDesignRequestController::class, 'linkCompletedWork'])->name('design-requests.link-completed-work');
        Route::post('design-requests/{designRequest}/notify', [AdminDesignRequestController::class, 'notifyTenant'])->name('design-requests.notify');

        Route::get('leads/export', [AdminLeadController::class, 'export'])->name('leads.export');
        Route::get('leads', [AdminLeadController::class, 'index'])->name('leads.index');
        Route::put('leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.status.update');

        Route::apiResource('sidebars', AdminSidebarController::class)->except('destroy');
        Route::get('system-settings/history', [AdminSystemSettingController::class, 'history'])->name('system-settings.history');
        Route::post('system-settings/history/{history}/restore', [AdminSystemSettingController::class, 'restoreHistory'])->name('system-settings.history.restore');
        Route::get('system-settings/export', [AdminSystemSettingController::class, 'export'])->name('system-settings.export');
        Route::get('system-settings/backup', [AdminSystemSettingController::class, 'backup'])->name('system-settings.backup');
        Route::post('system-settings/test-smtp', [AdminSystemSettingController::class, 'testSmtp'])->name('system-settings.test-smtp');
        Route::post('system-settings/test-email', [AdminSystemSettingController::class, 'testEmail'])->name('system-settings.test-email');
        Route::post('system-settings/maintenance-preview', [AdminSystemSettingController::class, 'maintenancePreview'])->name('system-settings.maintenance-preview');
        Route::put('system-settings', [AdminSystemSettingController::class, 'updateBulk'])->name('system-settings.update-bulk');
        Route::post('system-settings/images', [AdminSystemSettingController::class, 'uploadImage'])->name('system-settings.images.store');
        Route::apiResource('system-settings', AdminSystemSettingController::class)
            ->parameters(['system-settings' => 'systemSetting']);

        Route::post('template-catalog-items/preview-images', [AdminTemplateCatalogController::class, 'uploadPreviewImage'])->name('template-catalog-items.preview-images.store');
        Route::post('template-catalog-items/{templateCatalogItem}/clone', [AdminTemplateCatalogController::class, 'cloneItem'])->name('template-catalog-items.clone');
        Route::post('template-catalog-items/{templateCatalogItem}/publish', [AdminTemplateCatalogController::class, 'publish'])->name('template-catalog-items.publish');
        Route::post('template-catalog-items/{templateCatalogItem}/unpublish', [AdminTemplateCatalogController::class, 'unpublish'])->name('template-catalog-items.unpublish');
        Route::get('template-catalog-items/{templateCatalogItem}/versions', [AdminTemplateCatalogController::class, 'versions'])->name('template-catalog-items.versions');
        Route::post('template-catalog-items/{templateCatalogItem}/versions/{version}/rollback', [AdminTemplateCatalogController::class, 'rollback'])->name('template-catalog-items.rollback');
        Route::get('template-catalog-items/{templateCatalogItem}/export-schema', [AdminTemplateCatalogController::class, 'exportSchema'])->name('template-catalog-items.export-schema');
        Route::post('template-catalog-items/{templateCatalogItem}/import-schema', [AdminTemplateCatalogController::class, 'importSchema'])->name('template-catalog-items.import-schema');
        Route::get('template-catalog-items/{templateCatalogItem}/validate', [AdminTemplateCatalogController::class, 'validateTemplate'])->name('template-catalog-items.validate');
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
