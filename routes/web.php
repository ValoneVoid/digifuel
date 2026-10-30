<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FuelRequestController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\AdminMasterController;
use App\Http\Controllers\DashboardController;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Protected Application Routes (Requires Authentication)
Route::middleware(['auth'])->group(function () {
    // Root & Session Actions
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::post('/switch-user', [AuthController::class, 'switchUser'])->name('switch.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/notifications/{id}/read', [AuthController::class, 'readNotification'])->name('notifications.read');

    // Common Authenticated Modules (All Active Roles, Scoped by Role/Policy inside Controller)
    Route::middleware(['role:admin,driver,spv,depthead,vendor,fuelman'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // History & Excel (.xlsx) Export
        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');

        // Printable Fuel Request Document & Modal Preview (Protected by FuelRequestPolicy)
        Route::get('/fuel/{id}/document', [FuelRequestController::class, 'document'])->name('fuel.document');
        Route::get('/fuel/{id}/document-partial', [FuelRequestController::class, 'documentPartial'])->name('fuel.document.partial');

        // Profile & Private Signature Storage
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile/signature', [ProfileController::class, 'uploadSignature'])->name('profile.signature');
        Route::post('/profile/signature/{id}/deactivate', [ProfileController::class, 'deactivateSignature'])->name('profile.signature.deactivate');
        Route::get('/signatures/file/{filename}', [ProfileController::class, 'serveSignature'])->name('signature.serve');
    });

    // Fuel Request Submission, Draft, Cancellation & Dependent Dropdown AJAX (Driver & Admin)
    Route::middleware(['role:driver,admin'])->group(function () {
        Route::get('/fuel', [FuelRequestController::class, 'create'])->name('fuel.create');
        Route::post('/fuel', [FuelRequestController::class, 'store'])->name('fuel.store');
        Route::post('/fuel/{id}/submit-draft', [FuelRequestController::class, 'submitDraft'])->name('fuel.submit.draft');
        Route::post('/fuel/{id}/cancel', [FuelRequestController::class, 'cancel'])->name('fuel.cancel');
        Route::get('/api/area-approvers', [FuelRequestController::class, 'areaApprovers'])->name('api.area.approvers');
        Route::get('/api/vehicle-lookup', [FuelRequestController::class, 'vehicleLookup'])->name('api.vehicle.lookup');
    });

    // Sequential Approval (SPV, Dept Head/Manager, Vendor) & Fuel Man Module
    Route::middleware(['role:spv,depthead,vendor,fuelman,admin'])->group(function () {
        Route::get('/approval', [ApprovalController::class, 'index'])->name('approval.index');
        Route::get('/approval/{id}', [ApprovalController::class, 'show'])->name('approval.show');
        Route::post('/approval/{id}/approve', [ApprovalController::class, 'approve'])->name('approval.approve');
        Route::post('/approval/{id}/reject', [ApprovalController::class, 'reject'])->name('approval.reject');
    });

    // Realisasi Pengisian BBM (Fuel Man & Admin)
    Route::post('/approval/{id}/realization', [ApprovalController::class, 'recordRealization'])
        ->middleware(['role:fuelman,admin'])
        ->name('approval.realization');

    // Evaluasi Konsumsi BBM & Laporan Kerugian (Admin)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation.index');
        Route::post('/evaluation/calculate', [EvaluationController::class, 'calculate'])->name('evaluation.calculate');
        Route::get('/evaluation/export', [EvaluationController::class, 'export'])->name('evaluation.export');
        Route::get('/evaluation/{id}', [EvaluationController::class, 'show'])->name('evaluation.show');

        // Master Data & Audit Trail Management (Admin)
        Route::get('/admin/master', [AdminMasterController::class, 'index'])->name('admin.master');
        Route::post('/admin/master/employee', [AdminMasterController::class, 'storeEmployee'])->name('admin.master.employee');
        Route::post('/admin/master/vehicle', [AdminMasterController::class, 'storeVehicle'])->name('admin.master.vehicle');
        Route::post('/admin/master/area', [AdminMasterController::class, 'storeArea'])->name('admin.master.area');
        Route::post('/admin/master/vendor', [AdminMasterController::class, 'storeVendor'])->name('admin.master.vendor');
        Route::post('/admin/master/user', [AdminMasterController::class, 'storeUser'])->name('admin.master.user');
        Route::post('/admin/master/area-approver', [AdminMasterController::class, 'storeAreaApprover'])->name('admin.master.approver');
        Route::post('/admin/master/fuel-standard', [AdminMasterController::class, 'storeFuelStandard'])->name('admin.master.standard');
        Route::post('/admin/master/fuel-price', [AdminMasterController::class, 'storeFuelPrice'])->name('admin.master.price');
        Route::post('/admin/master/background', [AdminMasterController::class, 'storeBackground'])->name('admin.master.background');
        Route::post('/admin/master/background/{id}/delete', [AdminMasterController::class, 'deleteBackground'])->name('admin.master.background.delete');
        Route::post('/admin/master/toggle-status', [AdminMasterController::class, 'toggleStatus'])->name('admin.master.toggle');
    });
});
