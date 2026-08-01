<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlyerController;
use App\Http\Controllers\FlyerTemplateController;
use App\Http\Controllers\GoldPosterController;
use App\Http\Controllers\GoldRateController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PosterTemplateController;
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

    Route::get('gold-poster', [GoldPosterController::class, 'index'])->name('gold-poster.index');
    Route::get('gold-poster/update', [GoldPosterController::class, 'daily'])->name('gold-poster.update');
    Route::get('gold-poster/templates', [GoldPosterController::class, 'templatesPage'])->name('gold-poster.templates');
    Route::post('gold-poster/templates', [PosterTemplateController::class, 'store'])->name('poster-templates.store');
    Route::get('gold-poster/templates/{posterTemplate}', [PosterTemplateController::class, 'show'])->name('poster-templates.show');
    Route::put('gold-poster/templates/{posterTemplate}', [PosterTemplateController::class, 'update'])->name('poster-templates.update');
    Route::delete('gold-poster/templates/{posterTemplate}', [PosterTemplateController::class, 'destroy'])->name('poster-templates.destroy');
    Route::get('gold-poster/rates/latest', [GoldRateController::class, 'latest'])->name('gold-rates.latest');
    Route::get('gold-poster/rates', [GoldRateController::class, 'index'])->name('gold-rates.index');
    Route::get('gold-poster/rate-history', [GoldRateController::class, 'historyPage'])->name('gold-rates.history');
    Route::post('gold-poster/rates', [GoldRateController::class, 'store'])->name('gold-rates.store');

    Route::get('customers/{customer}/details', [CustomerController::class, 'details'])->name('customers.details');
    Route::get('customers/{customer}/poster', [CustomerController::class, 'poster'])->name('customers.poster');
    Route::resource('customers', CustomerController::class);

    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::resource('leads', LeadController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('flyer-templates', FlyerTemplateController::class)->except(['show']);
    Route::resource('flyers', FlyerController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('preview-print', [FlyerController::class, 'previewPrint'])->name('flyers.preview-print');

    Route::prefix('customers/{customer}/dates/{date}')->name('wishes.')->group(function () {
        Route::get('send', [WishController::class, 'send'])->name('send');
        Route::post('send', [WishController::class, 'store'])->name('store');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('generate-wish', [AIController::class, 'generateWish'])->name('generate-wish');
        Route::post('improve-template', [AIController::class, 'improveTemplate'])->name('improve-template');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
