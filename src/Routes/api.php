<?php

use Illuminate\Support\Facades\Route;
use Telebirr\LaravelTelebirr\Http\Controllers\TelebirrController;

/*
|--------------------------------------------------------------------------
| Telebirr API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the TelebirrServiceProvider within a group which
| contains the "api" middleware group. The routes are prefixed with 'telebirr'
| when loaded.
|
*/

// Payment order creation
Route::post('/order', [TelebirrController::class, 'createOrder']);

// Payment verification
Route::post('/verify', [TelebirrController::class, 'verifyPayment']);

// Order status query
Route::post('/query', [TelebirrController::class, 'queryOrder']);

// Authentication (if enabled)
if (config('telebirr.features.auth', false)) {
    Route::post('/auth', [TelebirrController::class, 'getAuthToken']);
}

// Webhook endpoint (no auth required)
Route::post('/webhook', [TelebirrController::class, 'webhook'])
    ->name('webhook')
    ->withoutMiddleware(['auth:sanctum', 'auth', 'web']); // Remove auth middleware
