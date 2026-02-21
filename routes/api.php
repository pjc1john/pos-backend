<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PingController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/ping', PingController::class);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v1')->group(function () {
        // Batch sync
        Route::post('/sync/batch', [SyncController::class, 'batch']);

        // Products (singular for store/update, plural for delete — matches Flutter app)
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/product', [ProductController::class, 'store']);
        Route::put('/product/{syncId}', [ProductController::class, 'update']);
        Route::delete('/products/{syncId}', [ProductController::class, 'destroy']);
        Route::post('/product/upload-image', [ProductController::class, 'uploadImage']);

        //product variant
        Route::post('/product-variant', [ProductVariantController::class, 'store']);
        Route::put('/product-variant/{syncId}', [ProductVariantController::class, 'update']);
        Route::delete('/product-variant/{syncId}', [ProductVariantController::class, 'destroy']);
    });
});
