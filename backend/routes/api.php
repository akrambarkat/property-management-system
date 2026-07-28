<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UtilityReadingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Locations
        Route::apiResource('locations', LocationController::class);

        // Buildings
        Route::apiResource('buildings', BuildingController::class);

        // Units
        Route::patch('units/{unit}/status', [UnitController::class, 'updateStatus']);
        Route::apiResource('units', UnitController::class);

        // Tenants
        Route::apiResource('tenants', TenantController::class);

        // Contracts
        Route::patch('contracts/{contract}/terminate', [ContractController::class, 'terminate']);
        Route::get('contracts/expiring', [ContractController::class, 'expiring']);
        Route::apiResource('contracts', ContractController::class);

        // Invoices
        Route::patch('invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
        Route::apiResource('invoices', InvoiceController::class);

        // Payments
        Route::apiResource('payments', PaymentController::class)->except(['update', 'edit']);

        // Utility Readings
        Route::apiResource('utility-readings', UtilityReadingController::class);

        // Expenses
        Route::apiResource('expenses', ExpenseController::class);

        // Maintenance
        Route::patch('maintenance/{maintenance}/status', [MaintenanceController::class, 'updateStatus']);
        Route::apiResource('maintenance', MaintenanceController::class);

        // Reports
        Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('reports/income', [ReportController::class, 'income']);
        Route::get('reports/expenses', [ReportController::class, 'expenses']);
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);

        // Users (Super Admin only)
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::apiResource('users', UserController::class);

        // Settings
        Route::get('settings', [SettingController::class, 'index']);
        Route::put('settings', [SettingController::class, 'update']);
        Route::get('currencies', [SettingController::class, 'currencies']);
        Route::put('currencies/{currency}', [SettingController::class, 'updateCurrency']);
        Route::patch('currencies/{currency}/default', [SettingController::class, 'setDefaultCurrency']);
    });
});
