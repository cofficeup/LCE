<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SchedulingController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Orders (PPO)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
    Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/subscriptions/{id}/pause', [SubscriptionController::class, 'pause']);
    Route::post('/subscriptions/{id}/resume', [SubscriptionController::class, 'resume']);
    Route::post('/subscriptions/{id}/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('/subscriptions/{id}/downgrade', [SubscriptionController::class, 'downgrade']);

    // Scheduling
    Route::post('/scheduling/asap', [SchedulingController::class, 'asap']);
    Route::post('/scheduling/future', [SchedulingController::class, 'future']);
    Route::post('/scheduling/recurring', [SchedulingController::class, 'recurring']);
    Route::get('/scheduling/available-dates', [SchedulingController::class, 'availableDates']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

    // Credits
    Route::get('/credits', [CreditController::class, 'index']);
    Route::get('/credits/history', [CreditController::class, 'history']);

    // Admin routes (requires admin role)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/customers', [AdminController::class, 'searchCustomers']);
        Route::get('/customers/{id}', [AdminController::class, 'getCustomer']);
        Route::get('/customers/{id}/subscriptions', [AdminController::class, 'getCustomerSubscriptions']);
        Route::post('/customers/{id}/credits', [AdminController::class, 'addCredit']);
        Route::post('/subscriptions/{id}/cancel', [AdminController::class, 'cancelSubscription']);
        Route::get('/plans', [AdminController::class, 'getPlans']);
    });
});
