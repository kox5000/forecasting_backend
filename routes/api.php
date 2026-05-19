<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
});
// Protected routes
Route::middleware('auth:api')->group(function () {
    // Products
    Route::apiResource('products', ProductController::class);

    // Sales
    Route::apiResource('sales', SaleController::class);

    // Dashboard
    Route::get('/dashboard/total-sales', [DashboardController::class, 'totalSales']);
    Route::get('/dashboard/sales-over-time', [DashboardController::class, 'salesOverTime']);
    Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts']);
    Route::get('/dashboard/monthly-comparison', [DashboardController::class, 'monthlyComparison']);
    Route::get('/dashboard/sales-growth', [DashboardController::class, 'salesGrowth']);
    Route::get('/dashboard/moving-average', [DashboardController::class, 'movingAverage']);
    Route::get('/dashboard/trends', [DashboardController::class, 'trends']);
    Route::get('/dashboard/anomalies', [DashboardController::class, 'anomalies']);
    Route::get('/dashboard/7-day-moving-average', [SaleController::class, 'get7DayMovingAverage']);
    // AI (rate limited)
    Route::middleware(['throttle:60,1', 'log_requests'])->group(function () {
        Route::post('/ai/forecast', [AIController::class, 'forecast']);
        Route::get('/ai/insights', [AIController::class, 'insights']);
        Route::get('/ai/recommendations', [AIController::class, 'recommendations']);
        Route::get('/ai/forecast/{id}/status', [AIController::class, 'forecastStatus']);
        Route::get('/ai/insights/{id}/status', [AIController::class, 'insightsStatus']);
        Route::get('/ai/recommendations/{id}/status', [AIController::class, 'recommendationsStatus']);
    });
});