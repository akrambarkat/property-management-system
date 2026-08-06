<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SmsController;
use App\Http\Controllers\Api\V1\SmsJobController;
use App\Http\Controllers\Api\V1\SmsLogController;
use App\Http\Controllers\Api\V1\SmsProviderController;
use App\Http\Controllers\Api\V1\SmsStatisticController;
use App\Http\Controllers\Api\V1\SmsTemplateController;
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

        // Notifications
        Route::get('notifications/latest', [NotificationController::class, 'latest']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('notifications/bulk', [NotificationController::class, 'bulkAction']);
        Route::post('notifications/check', [NotificationController::class, 'check']);
        Route::get('notifications/settings', [NotificationController::class, 'getSettings']);
        Route::put('notifications/settings/{type}', [NotificationController::class, 'updateSetting']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('notifications/{notification}/unread', [NotificationController::class, 'markAsUnread']);
        Route::patch('notifications/{notification}/archive', [NotificationController::class, 'archive']);
        Route::apiResource('notifications', NotificationController::class)->except(['store', 'show']);

        // Reports
        Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('reports/income', [ReportController::class, 'income']);
        Route::get('reports/expenses', [ReportController::class, 'expenses']);
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);
        Route::get('reports/export', [ReportController::class, 'exportList']);
        Route::get('reports/tenant-statement/{tenant}', [ReportController::class, 'tenantStatement']);

        // Users (Super Admin only)
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::apiResource('users', UserController::class);

        // Settings
        Route::get('settings', [SettingController::class, 'index']);
        Route::put('settings', [SettingController::class, 'update'])->middleware('permission:edit-settings');
        Route::get('currencies', [SettingController::class, 'listCurrencies']);
        Route::put('currencies/{currency}', [SettingController::class, 'updateCurrency']);
        Route::patch('currencies/{currency}/default', [SettingController::class, 'setDefaultCurrency']);

        // Activity Logs
        Route::middleware('permission:view-settings')->group(function () {
            Route::get('activity-logs', [ActivityLogController::class, 'index']);
            Route::get('activity-logs/actions', [ActivityLogController::class, 'actions']);
            Route::delete('activity-logs/clear', [ActivityLogController::class, 'clear'])->middleware('permission:edit-settings');
        });

        // SMS
        Route::middleware('permission:view-sms')->group(function () {
            Route::get('sms/providers', [SmsProviderController::class, 'index']);
            Route::get('sms/providers/{provider}', [SmsProviderController::class, 'show']);
            Route::put('sms/providers/{provider}', [SmsProviderController::class, 'update'])->middleware('permission:manage-providers');
            Route::post('sms/providers/{provider}/test', [SmsProviderController::class, 'testConnection'])->middleware('permission:edit-sms-settings');
            Route::post('sms/providers/{provider}/test-send', [SmsProviderController::class, 'sendTestSms'])->middleware('permission:send-sms');

            Route::get('sms/templates', [SmsTemplateController::class, 'index']);
            Route::post('sms/templates', [SmsTemplateController::class, 'store']);
            Route::get('sms/templates/{template}', [SmsTemplateController::class, 'show']);
            Route::put('sms/templates/{template}', [SmsTemplateController::class, 'update']);
            Route::delete('sms/templates/{template}', [SmsTemplateController::class, 'destroy']);
            Route::patch('sms/templates/{template}/toggle', [SmsTemplateController::class, 'toggle']);
            Route::post('sms/templates/preview', [SmsTemplateController::class, 'preview']);

            Route::get('sms/logs', [SmsLogController::class, 'index'])->middleware('permission:view-sms-logs');
            Route::get('sms/logs/export', [SmsLogController::class, 'export'])->middleware('permission:export-logs');
            Route::get('sms/logs/{log}', [SmsLogController::class, 'show'])->middleware('permission:view-sms-logs');
            Route::post('sms/logs/{log}/retry', [SmsLogController::class, 'retry']);

            Route::get('sms/recipients', [SmsController::class, 'recipients']);
            Route::post('sms/send', [SmsController::class, 'send']);
            Route::post('sms/bulk', [SmsController::class, 'bulk']);

            Route::get('sms/jobs', [SmsJobController::class, 'index']);
            Route::post('sms/jobs', [SmsJobController::class, 'store']);
            Route::put('sms/jobs/{job}', [SmsJobController::class, 'update']);
            Route::delete('sms/jobs/{job}', [SmsJobController::class, 'destroy']);
            Route::patch('sms/jobs/{job}/toggle', [SmsJobController::class, 'toggle']);

            Route::get('sms/statistics/overview', [SmsStatisticController::class, 'overview']);
            Route::get('sms/statistics/daily', [SmsStatisticController::class, 'daily']);
            Route::get('sms/statistics/monthly', [SmsStatisticController::class, 'monthly']);
            Route::get('sms/statistics/failures', [SmsStatisticController::class, 'failureReasons']);
            Route::get('sms/statistics/providers', [SmsStatisticController::class, 'providerComparison']);
        });
    });
});
