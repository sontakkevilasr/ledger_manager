<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;

// ── Public routes ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',         [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',        [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Authenticated routes ──────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Customers ─────────────────────────────────────────────
    Route::resource('customers', CustomerController::class);
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])
        ->name('customers.toggle-status');
    Route::delete('customers/{customer}/purge',
        [CustomerController::class, 'purge']
    )->name('customers.purge');	

    // ── Transactions ──────────────────────────────────────────
    Route::resource('transactions', TransactionController::class)->except(['show']);
    Route::get('transactions/{transaction}',        [TransactionController::class, 'show'])
        ->name('transactions.show');
    Route::get('transactions/export/{format}',      [TransactionController::class, 'export'])
        ->name('transactions.export')
        ->where('format', 'pdf|excel');
    Route::get('api/customers/{customer}/balance',  [TransactionController::class, 'getBalance'])
        ->name('api.customer.balance');

    // ── Reports ───────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('balance-summary',        [ReportController::class, 'balanceSummary'])->name('balance-summary');
        Route::get('balance-summary/export', [ReportController::class, 'exportBalanceSummary'])->name('balance-summary.export');
        Route::get('date-range',             [ReportController::class, 'dateRange'])->name('date-range');
        Route::get('customer-ledger',        [ReportController::class, 'customerLedger'])->name('customer-ledger');
        Route::get('city-wise',              [ReportController::class, 'cityWise'])->name('city-wise');
        Route::get('agent-wise',             [ReportController::class, 'agentWise'])->name('agent-wise');
        Route::get('activity-logs',          [ReportController::class, 'activityLogs'])->name('activity-logs');
    });

    // ── Masters ───────────────────────────────────────────────
    Route::resource('agents', AgentController::class)->except(['show']);
    Route::patch('agents/{agent}/toggle',
        [AgentController::class, 'toggleStatus']
    )->name('agents.toggle');

    // ── Users (super_admin only) ──────────────────────────────
    Route::resource('users', UserController::class)->except(['show']);

    // ── Settings (super_admin only) ───────────────────────────
    Route::get('settings',  [SettingsController::class, 'index'])->name('settings');
    Route::put('settings',  [SettingsController::class, 'update'])->name('settings.update');

    // ── Profile ───────────────────────────────────────────────
    Route::get('profile',                  [ProfileController::class, 'show'])->name('profile');
    Route::put('profile',                  [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/change-password',  [ProfileController::class, 'changePassword'])->name('profile.password');

});
