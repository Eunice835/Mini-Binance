<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:login');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');

Route::get('/market/orderbook', [MarketController::class, 'orderbook']);
Route::get('/market/trades', [MarketController::class, 'trades']);
Route::get('/market/ticker', [MarketController::class, 'ticker']);

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    
    Route::post('/auth/enable-2fa', [AuthController::class, 'enable2FA'])->middleware('throttle:otp');
    Route::post('/auth/verify-2fa', [AuthController::class, 'verify2FA'])->middleware('throttle:otp');
    Route::post('/auth/disable-2fa', [AuthController::class, 'disable2FA'])->middleware('throttle:otp');

    Route::get('/kyc/status', [KycController::class, 'status']);
    Route::post('/kyc/submit', [KycController::class, 'submit']);

    Route::get('/wallets', [WalletController::class, 'index']);
    Route::post('/transactions/deposit', [WalletController::class, 'deposit']);
    Route::post('/transactions/withdraw', [WalletController::class, 'withdraw'])->middleware('throttle:withdrawals');
    Route::get('/transactions', [WalletController::class, 'transactions']);

    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:orders');
    Route::delete('/orders/{id}', [OrderController::class, 'cancel']);
    Route::get('/orders/open', [OrderController::class, 'openOrders']);
    Route::get('/orders/history', [OrderController::class, 'history']);
    Route::get('/trades', [OrderController::class, 'trades']);

    Route::prefix('admin')->middleware('can:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{id}/freeze', [AdminController::class, 'freezeUser']);
        Route::post('/users/{id}/unfreeze', [AdminController::class, 'unfreezeUser']);
        
        Route::get('/kyc/pending', [AdminController::class, 'pendingKyc']);
        Route::post('/kyc/{id}/approve', [AdminController::class, 'approveKyc']);
        Route::post('/kyc/{id}/reject', [AdminController::class, 'rejectKyc']);
        
        Route::post('/credit', [AdminController::class, 'creditUser']);
        Route::post('/debit', [AdminController::class, 'debitUser']);
        
        Route::get('/transactions/pending', [AdminController::class, 'pendingTransactions']);
        Route::post('/transactions/{id}/approve', [AdminController::class, 'approveTransaction']);
        Route::post('/transactions/{id}/reject', [AdminController::class, 'rejectTransaction']);
        
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    });
});
