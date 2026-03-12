<?php

use App\Http\Controllers\AssetCsvController;
use App\Http\Controllers\AssetLabelController;
use App\Http\Controllers\AssetPhotoController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditEvidencePackController;
use App\Http\Controllers\EmployeeCsvController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserCsvController;
use App\Http\Middleware\EnsureApplicationNotInstalled;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('/admin');
});

Route::middleware([EnsureApplicationNotInstalled::class])->group(function (): void {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
    Route::post('/setup/initialize-database', [SetupController::class, 'initializeDatabase'])
        ->name('setup.initialize-database');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/assets/{asset}/export', [AssetCsvController::class, 'export'])
        ->name('assetflow.assets.export');
    Route::get('/assets/template', [AssetCsvController::class, 'template'])
        ->name('assetflow.assets.template');
    Route::get('/users/template', [UserCsvController::class, 'template'])
        ->name('assetflow.users.template');
    Route::get('/employees/template', [EmployeeCsvController::class, 'template'])
        ->name('assetflow.employees.template');
    Route::get('/audit/evidence-pack', [AuditEvidencePackController::class, 'download'])
        ->name('assetflow.audit.evidence-pack');

    Route::get('/assets/{asset}/label', [AssetLabelController::class, 'single'])
        ->name('assetflow.labels.single');
    Route::get('/assets/labels/batch', [AssetLabelController::class, 'batch'])
        ->name('assetflow.labels.batch');
    Route::get('/assets/{asset}/receipt', [AssetLabelController::class, 'receiptSingle'])
        ->name('assetflow.receipts.single');
    Route::get('/assets/receipts/batch', [AssetLabelController::class, 'receiptBatch'])
        ->name('assetflow.receipts.batch');
    Route::get('/assets/{asset}/photo', [AssetPhotoController::class, 'show'])
        ->name('assetflow.assets.photo');

    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('assetflow.attachments.download');
});
