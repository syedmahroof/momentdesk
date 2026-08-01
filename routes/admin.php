<?php

use App\Http\Controllers\Admin\BackgroundCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\PosterBackgroundController;
use App\Http\Controllers\Admin\PosterCategoryController;
use App\Http\Controllers\Admin\PosterTemplateController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Middleware\UseAdminGuard;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Super Admin Panel
|--------------------------------------------------------------------------
|
| Authenticates on the `admin` guard against the `admins` table. Entirely
| independent of the tenant application: no shared identity, no shared
| session guard, and no links between the two panels.
|
*/

Route::prefix('admin')->name('admin.')->middleware(UseAdminGuard::class)->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:admin-login')
            ->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('tenants', TenantController::class)->only(['index', 'update', 'destroy']);
        Route::resource('poster-templates', PosterTemplateController::class)->except(['create', 'edit']);

        // Poster categories, background images and their categories share one screen.
        Route::get('library', LibraryController::class)->name('library');
        Route::resource('poster-categories', PosterCategoryController::class)->only(['store', 'update', 'destroy']);
        Route::post('poster-categories/{posterCategory}/reorder', [PosterCategoryController::class, 'reorder'])->name('poster-categories.reorder');
        Route::resource('background-categories', BackgroundCategoryController::class)->only(['store', 'update', 'destroy']);
        Route::resource('poster-backgrounds', PosterBackgroundController::class)->only(['store', 'update', 'destroy']);
    });
});
