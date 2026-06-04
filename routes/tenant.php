<?php

use App\Tenant\Auth\Http\Controllers\AuthController;
use App\Tenant\Dashboard\Http\Controllers\AnalyticsController;
use App\Tenant\Dashboard\Http\Controllers\PublicCtaTrackingController;
use App\Tenant\Dashboard\Http\Controllers\PublicVisitorTrackingController;
use App\Tenant\Dashboard\Http\Controllers\TenantDashboardController;
use App\Tenant\DesignRequests\Http\Controllers\DesignRequestController;
use App\Tenant\Sidebar\Http\Controllers\TenantSidebarController;
use App\Tenant\SystemSettings\Http\Controllers\TenantSystemSettingController;
use App\Tenant\Templates\Http\Controllers\PublicTemplateController;
use App\Tenant\Templates\Http\Controllers\TenantLeadController;
use App\Tenant\Templates\Http\Controllers\TemplateController;
use App\Tenant\Templates\Http\Controllers\TemplateCtaSubmissionController;
use App\Tenant\Templates\Posts\Http\Controllers\PostController;
use App\Tenant\Templates\Posts\Http\Controllers\PublicPostController;
use App\Tenant\TrackingLogs\Http\Controllers\TrackingLogController;
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

            Route::get('dashboard', [TenantDashboardController::class, 'index'])->name('dashboard.index');
            Route::get('analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
            Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

            Route::get('leads/export', [TenantLeadController::class, 'export'])->name('leads.export');
            Route::get('leads', [TenantLeadController::class, 'index'])->name('leads.index');
            Route::get('leads/{lead}', [TenantLeadController::class, 'show'])->name('leads.show');
            Route::put('leads/{lead}/status', [TenantLeadController::class, 'updateStatus'])->name('leads.status.update');

            Route::get('design-requests', [DesignRequestController::class, 'index'])->name('design-requests.index');
            Route::post('design-requests', [DesignRequestController::class, 'store'])->name('design-requests.store');
            Route::get('design-requests/{designRequest}', [DesignRequestController::class, 'show'])->name('design-requests.show');
            Route::post('design-requests/{designRequest}/comments', [DesignRequestController::class, 'comment'])->name('design-requests.comments.store');
            Route::post('design-requests/{designRequest}/actions', [DesignRequestController::class, 'action'])->name('design-requests.actions.store');

            Route::apiResource('sidebars', TenantSidebarController::class);

            Route::get('system-settings/history', [TenantSystemSettingController::class, 'history'])->name('system-settings.history');
            Route::put('system-settings', [TenantSystemSettingController::class, 'updateBulk'])->name('system-settings.update-bulk');
            Route::post('system-settings/images', [TenantSystemSettingController::class, 'uploadImage'])->name('system-settings.images.store');
            Route::get('system-settings', [TenantSystemSettingController::class, 'index'])->name('system-settings.index');

            Route::get('templates/website-types', [TemplateController::class, 'websiteTypes'])->name('templates.website-types');
            Route::get('templates/website-types/{websiteType}/templates', [TemplateController::class, 'websiteTypeTemplates'])->name('templates.website-types.templates');
            Route::get('templates/{template}/published', [TemplateController::class, 'published'])->name('templates.published');
            Route::post('templates/{template}/reset-default', [TemplateController::class, 'resetDefault'])->name('templates.reset-default');
            Route::apiResource('templates', TemplateController::class);

            Route::get('templates/{template}/posts', [PostController::class, 'index'])->name('templates.posts.index');
            Route::get('templates/{template}/posts/{post}', [PostController::class, 'show'])->name('templates.posts.show');
            Route::post('templates/{template}/posts/featured-image', [PostController::class, 'uploadFeaturedImage'])->name('templates.posts.featured-image');
            Route::post('templates/{template}/posts', [PostController::class, 'store'])->name('templates.posts.store');
            Route::put('templates/{template}/posts/{post}', [PostController::class, 'update'])->name('templates.posts.update');
            Route::delete('templates/{template}/posts/{post}', [PostController::class, 'destroy'])->name('templates.posts.destroy');
            Route::post('templates/{template}/posts/{post}/publish', [PostController::class, 'publish'])->name('templates.posts.publish');
            Route::post('templates/{template}/posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('templates.posts.unpublish');

            Route::get('tracking-logs/export', [TrackingLogController::class, 'export'])->name('tracking-logs.export');
            Route::get('tracking-logs', [TrackingLogController::class, 'index'])->name('tracking-logs.index');
        });
    });
});

Route::prefix('public')->name('public.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::post('analytics/visits', [PublicVisitorTrackingController::class, 'store'])->name('analytics.visits.store');
        Route::post('analytics/cta-events', [PublicCtaTrackingController::class, 'store'])->name('analytics.cta-events.store');

        Route::get('sites/default', [PublicTemplateController::class, 'defaultSite'])->name('sites.default');
        Route::get('sites/{slug}', [PublicTemplateController::class, 'show'])->name('sites.show');
        Route::post('template-cta-submissions', [TemplateCtaSubmissionController::class, 'store'])->name('template-cta-submissions.store');

        Route::get('sites/{siteSlug}/posts', [PublicPostController::class, 'index'])->name('sites.posts.index');
        Route::get('sites/{siteSlug}/posts/{postSlug}', [PublicPostController::class, 'show'])->name('sites.posts.show');
    });
});
