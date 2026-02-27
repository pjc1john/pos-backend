<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PingController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\CashReconciliationController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\DtrController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\StaffController;
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

        // Sales
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sale', [SaleController::class, 'store']);

        // Branches
        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branch', [BranchController::class, 'store']);
        Route::put('/branch/{syncId}', [BranchController::class, 'update']);

        // Staff
        Route::post('/staff', [StaffController::class, 'store']);
        Route::put('/staff/{syncId}', [StaffController::class, 'update']);

        // Discounts
        Route::get('/discounts', [DiscountController::class, 'index']);
        Route::post('/discount', [DiscountController::class, 'store']);
        Route::put('/discount/{syncId}', [DiscountController::class, 'update']);

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expense', [ExpenseController::class, 'store']);
        Route::put('/expense/{syncId}', [ExpenseController::class, 'update']);
        Route::delete('/expense/{syncId}', [ExpenseController::class, 'destroy']);

        // Cash Reconciliations
        Route::get('/cash-reconciliations', [CashReconciliationController::class, 'index']);
        Route::post('/cash-reconciliation', [CashReconciliationController::class, 'store']);
        Route::put('/cash-reconciliation/{syncId}', [CashReconciliationController::class, 'update']);

        // Inventory items
        Route::get('/inventory-items', [InventoryItemController::class, 'index']);
        Route::post('/inventory-items', [InventoryItemController::class, 'store']);
        Route::put('/inventory-items/{syncId}', [InventoryItemController::class, 'update']);
        Route::delete('/inventory-items/{syncId}', [InventoryItemController::class, 'destroy']);

        // DTR (Daily Time Record)
        Route::get('/dtr', [DtrController::class, 'index']);
        Route::post('/dtr', [DtrController::class, 'store']);
        Route::put('/dtr/{syncId}', [DtrController::class, 'update']);
        Route::delete('/dtr/{syncId}', [DtrController::class, 'destroy']);
    });
});
