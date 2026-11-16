<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/q/{token}', QrController::class)->name('qr.scan');
    Route::get('/labels', LabelController::class)->name('labels');
    Route::resource('assets', AssetController::class)->except('destroy');
    Route::post('/assets/{asset}/condition', [MaintenanceController::class, 'condition'])->name('assets.condition');
    Route::post('/assets/{asset}/status', [MaintenanceController::class, 'status'])->name('assets.status');
    Route::post('/assets/{asset}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::resource('procurements', ProcurementController::class)->only('index', 'create', 'store', 'show');
    Route::resource('transfers', TransferController::class)->only('index', 'create', 'store', 'show');
    foreach (['submit', 'approve', 'reject', 'cancel'] as $action) {
        Route::post('/transfers/{transfer}/'.$action, [TransferController::class, $action])->name('transfers.'.$action);
    }
    Route::resource('maintenances', MaintenanceController::class)->only('index', 'create', 'store');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/masters', [MasterController::class, 'index'])->name('masters.index');
    Route::put('/school', [MasterController::class, 'school'])->name('school.update');
    Route::get('/masters/{type}', [MasterController::class, 'index'])->name('masters.type');
    Route::post('/masters/{type}', [MasterController::class, 'store'])->name('masters.store');
    Route::put('/masters/{type}/{id}', [MasterController::class, 'update'])->name('masters.update');
    Route::resource('users', UserController::class)->only('index', 'store', 'update');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::put('/permissions/{role}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::get('/audit', function () {
        Gate::authorize('audit.view');

        return view('audit', ['logs' => AuditLog::latest('id')->paginate(30)]);
    })->name('audit');
});
