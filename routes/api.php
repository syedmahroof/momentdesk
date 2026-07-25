<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FlyerController;
use App\Http\Controllers\Api\FlyerTemplateController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\WishController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'ensure-tenant'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

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
});
