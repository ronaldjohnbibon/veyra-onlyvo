<?php

use App\Modules\Posts\Http\Controllers\PostController;
use App\Modules\Posts\Http\Controllers\PublicPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->name('public.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::get('sites/{siteSlug}/posts', [PublicPostController::class, 'index'])->name('sites.posts.index');
        Route::get('sites/{siteSlug}/posts/{postSlug}', [PublicPostController::class, 'show'])->name('sites.posts.show');
    });
});

Route::prefix('app')->name('app.')->group(function (): void {
    Route::tenanted(function (): void {
        Route::middleware('api.auth')->group(function (): void {
            Route::get('templates/{template}/posts', [PostController::class, 'index'])->name('templates.posts.index');
            Route::get('templates/{template}/posts/{post}', [PostController::class, 'show'])->name('templates.posts.show');
            Route::post('templates/{template}/posts', [PostController::class, 'store'])->name('templates.posts.store');
            Route::put('templates/{template}/posts/{post}', [PostController::class, 'update'])->name('templates.posts.update');
            Route::delete('templates/{template}/posts/{post}', [PostController::class, 'destroy'])->name('templates.posts.destroy');
            Route::post('templates/{template}/posts/{post}/publish', [PostController::class, 'publish'])->name('templates.posts.publish');
            Route::post('templates/{template}/posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('templates.posts.unpublish');
        });
    });
});
