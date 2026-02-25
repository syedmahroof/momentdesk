<?php

use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// ── Social OAuth ──────────────────────────────────────────────────────────────
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified', 'ensure-tenant'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('customers', CustomerController::class);

    Route::prefix('customers/{customer}/dates/{date}')->name('wishes.')->group(function () {
        Route::get('send', [WishController::class, 'send'])->name('send');
        Route::post('send', [WishController::class, 'store'])->name('store');
    });

    Route::post('wishes/bulk-today', [WishController::class, 'bulkSendToday'])->name('wishes.bulk-today');

    Route::resource('templates', TemplateController::class)->except(['show']);

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('generate-wish', [AIController::class, 'generateWish'])->name('generate-wish');
        Route::post('improve-template', [AIController::class, 'improveTemplate'])->name('improve-template');
    });
});

Route::middleware(['auth', 'verified', 'can:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('tenants', TenantController::class);
});

require __DIR__ . '/settings.php';
