<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\MerchantDashboardController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\InvoiceController;

Route::middleware(['throttle:api'])->group(function () {
    Route::post('/usage', [UsageController::class, 'store'])->middleware('throttle:usage');
    Route::get('/merchants/{merchant}/dashboard', [MerchantDashboardController::class, 'show']);
    Route::apiResource('/merchants/{merchant}/plans', PlanController::class)->shallow();
    Route::apiResource('/merchants/{merchant}/subscriptions', SubscriptionController::class)->shallow();
    Route::post('/subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::post('/subscriptions/{subscription}/invoice', [InvoiceController::class, 'generate']);
    Route::get('/subscriptions/{subscription}/invoices', [InvoiceController::class, 'index']);
});
