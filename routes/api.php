<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FlyerController;
use App\Http\Controllers\Api\FlyerTemplateController;
use App\Http\Controllers\Api\GoldPosterController;
use App\Http\Controllers\Api\GoldRateController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PosterTemplateController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TenantProfileController;
use App\Http\Controllers\Api\WishController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->middleware('throttle:6,1');

Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

// Opened in a system browser tab by the app, so these stay outside the token
// guard; the callback hands back a single-use code over the app's URL scheme.
Route::prefix('auth/{provider}')->group(function () {
    Route::get('redirect', [SocialAuthController::class, 'redirect'])->name('api.social.redirect');
    Route::get('callback', [SocialAuthController::class, 'callback'])->name('api.social.callback');
});
Route::post('auth/social/exchange', [SocialAuthController::class, 'exchange'])->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'ensure-tenant'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1');

    Route::patch('profile', [ProfileController::class, 'update']);
    Route::delete('profile', [ProfileController::class, 'destroy']);
    Route::put('password', [PasswordController::class, 'update'])->middleware('throttle:6,1');

    Route::get('tenant-profile', [TenantProfileController::class, 'show']);
    // POST, not PATCH: the logos are file uploads and PHP only parses
    // multipart bodies on POST.
    Route::post('tenant-profile', [TenantProfileController::class, 'update']);

    Route::apiResource('customers', CustomerController::class);

    Route::get('flyer-templates', [FlyerTemplateController::class, 'index']);
    Route::apiResource('flyers', FlyerController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::apiResource('templates', TemplateController::class)->except(['show']);

    Route::post('wishes', [WishController::class, 'store']);
    Route::post('wishes/bulk-today', [WishController::class, 'bulkSendToday']);

    Route::prefix('ai')->group(function () {
        Route::post('generate-wish', [AIController::class, 'generateWish']);
        Route::post('improve-template', [AIController::class, 'improveTemplate']);
    });

    Route::get('gold-poster/daily', [GoldPosterController::class, 'daily']);
    Route::get('gold-poster/templates', [PosterTemplateController::class, 'index']);
    Route::get('gold-poster/templates/{posterTemplate}', [PosterTemplateController::class, 'show']);

    Route::get('gold-rates', [GoldRateController::class, 'index']);
    Route::get('gold-rates/latest', [GoldRateController::class, 'latest']);
    Route::post('gold-rates', [GoldRateController::class, 'store']);
});
