<?php

use App\Modules\Templates\Http\Controllers\AdminTemplateCatalogController;
use App\Modules\Templates\Http\Controllers\AdminWebsiteTypeController;
use App\Modules\Templates\Http\Controllers\PublicTemplateController;
use App\Modules\Templates\Http\Controllers\TemplateCtaSubmissionController;
use App\Modules\Templates\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('api.auth')->group(function (): void {
    Route::apiResource('template-catalog-items', AdminTemplateCatalogController::class)
        ->parameters(['template-catalog-items' => 'templateCatalogItem']);
    Route::apiResource('website-types', AdminWebsiteTypeController::class)
        ->parameters(['website-types' => 'websiteType'])
        ->except(['show']);
});

Route::prefix('public')->name('public.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::get('sites/default', [PublicTemplateController::class, 'defaultSite'])->name('sites.default');
        Route::get('sites/{slug}', [PublicTemplateController::class, 'show'])->name('sites.show');
        Route::post('template-cta-submissions', [TemplateCtaSubmissionController::class, 'store'])->name('template-cta-submissions.store');
    });
});

Route::prefix('app')->name('app.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::middleware('api.auth')->group(function (): void {
            Route::get('templates/website-types', [TemplateController::class, 'websiteTypes'])->name('templates.website-types');
            Route::get('templates/website-types/{websiteType}/templates', [TemplateController::class, 'websiteTypeTemplates'])->name('templates.website-types.templates');
            Route::get('templates/{template}/published', [TemplateController::class, 'published'])->name('templates.published');
            Route::apiResource('templates', TemplateController::class);
        });
    });
});
