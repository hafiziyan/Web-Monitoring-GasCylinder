<?php

use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CylinderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GasTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Area admin: hanya role admin (User Management, Settings).
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});

// Data Tabung: admin (kelola) & operator (lihat). Aksi tulis dilindungi CylinderPolicy.
Route::resource('cylinders', CylinderController::class)->middleware(['auth']);

// Master Data: admin (kelola) & operator (lihat). Aksi tulis dilindungi policy masing-masing.
Route::resource('gas-types', GasTypeController::class)->middleware(['auth']);
Route::resource('suppliers', SupplierController::class)->middleware(['auth']);
Route::resource('locations', LocationController::class)->middleware(['auth']);

// QR Code: hanya admin (generate/cetak administratif).
Route::prefix('qr-codes')->name('qr-codes.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [QrCodeController::class, 'index'])->name('index');
    Route::get('/print', [QrCodeController::class, 'printSelected'])->name('print-selected');
    Route::get('/{cylinder}/preview', [QrCodeController::class, 'preview'])->name('preview');
    Route::get('/{cylinder}/print', [QrCodeController::class, 'print'])->name('print');
    Route::post('/{cylinder}/generate', [QrCodeController::class, 'generate'])->name('generate');
});

// Transaksi: scan QR untuk identifikasi tabung (read-only). Aksi tulis menyusul tahap berikutnya.
Route::prefix('transactions')->name('transactions.')->middleware(['auth', 'role:admin,operator'])->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('index');
    Route::get('/scan', [TransactionController::class, 'scan'])->name('scan');
    Route::get('/cylinders/search', [TransactionController::class, 'searchCylinders'])->name('cylinders.search');
    Route::get('/in', [TransactionController::class, 'createIn'])->name('in.create');
    Route::post('/in', [TransactionController::class, 'storeIn'])->name('in.store');
    Route::get('/out', [TransactionController::class, 'createOut'])->name('out.create');
    Route::post('/out', [TransactionController::class, 'storeOut'])->name('out.store');
    Route::get('/return', [TransactionController::class, 'createReturn'])->name('return.create');
    Route::post('/return', [TransactionController::class, 'storeReturn'])->name('return.store');
    Route::get('/transfer', [TransactionController::class, 'createTransfer'])->name('transfer.create');
    Route::post('/transfer', [TransactionController::class, 'storeTransfer'])->name('transfer.store');
    Route::get('/use-start', [TransactionController::class, 'createUseStart'])->name('use-start.create');
    Route::post('/use-start', [TransactionController::class, 'storeUseStart'])->name('use-start.store');
    Route::get('/use-end', [TransactionController::class, 'createUseEnd'])->name('use-end.create');
    Route::post('/use-end', [TransactionController::class, 'storeUseEnd'])->name('use-end.store');
    Route::get('/maintenance', [TransactionController::class, 'createMaintenance'])->name('maintenance.create');
    Route::post('/maintenance', [TransactionController::class, 'storeMaintenance'])->name('maintenance.store');
    Route::get('/maintenance/complete', [TransactionController::class, 'createMaintenanceComplete'])->name('maintenance.complete');
    Route::post('/maintenance/complete', [TransactionController::class, 'storeMaintenanceComplete'])->name('maintenance.complete.store');
    Route::get('/scan/{qr_token}', [TransactionController::class, 'scanResult'])->name('scan-result');
    Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
});

// Laporan: admin & operator (read-only + export mengikuti filter aktif).
Route::prefix('reports')->name('reports.')->middleware(['auth', 'role:admin,operator'])->group(function () {
    Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/excel', [ReportController::class, 'transactionsExcel'])->name('transactions.excel');
    Route::get('/transactions/pdf', [ReportController::class, 'transactionsPdf'])->name('transactions.pdf');
    Route::get('/cylinders', [ReportController::class, 'cylinders'])->name('cylinders');
    Route::get('/cylinders/excel', [ReportController::class, 'cylindersExcel'])->name('cylinders.excel');
    Route::get('/cylinders/pdf', [ReportController::class, 'cylindersPdf'])->name('cylinders.pdf');
});

// Audit Log: read-only, hanya admin.
Route::prefix('audit-logs')->name('audit-logs.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
    Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show');
});

require __DIR__.'/auth.php';
