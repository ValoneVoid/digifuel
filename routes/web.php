<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FuelRequestController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\AdminMasterController;

// Root redirects dynamically based on active user role
Route::get('/', function () {
    $user = auth()->user() ?? \App\Models\User::where('username', 'adji')->first();
    if ($user) {
        if ($user->isAdmin()) {
            return redirect()->route('evaluation.index');
        }
        if ($user->isApprover()) {
            return redirect()->route('approval.index');
        }
    }
    return redirect()->route('fuel.create');
});

// Authentication & Login
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/switch-user', [AuthController::class, 'switchUser'])->name('switch.user');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/notifications/{id}/read', [AuthController::class, 'readNotification'])->name('notifications.read');

// Fuel Request & Dependent Dropdown AJAX Endpoints
Route::get('/fuel', [FuelRequestController::class, 'create'])->name('fuel.create');
Route::post('/fuel', [FuelRequestController::class, 'store'])->name('fuel.store');
Route::post('/fuel/{id}/submit-draft', [FuelRequestController::class, 'submitDraft'])->name('fuel.submit.draft');
Route::post('/fuel/{id}/cancel', [FuelRequestController::class, 'cancel'])->name('fuel.cancel');
Route::get('/fuel/{id}/document', [FuelRequestController::class, 'document'])->name('fuel.document');
Route::get('/fuel/{id}/document-partial', [FuelRequestController::class, 'documentPartial'])->name('fuel.document.partial');
Route::get('/api/area-approvers', [FuelRequestController::class, 'areaApprovers'])->name('api.area.approvers');
Route::get('/api/vehicle-lookup', [FuelRequestController::class, 'vehicleLookup'])->name('api.vehicle.lookup');

// History & Excel (.xlsx) Export
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');

// Sequential Approval (SPV, Dept Head/Manager, Vendor) & Realisasi Pengisian BBM (Fuel Man)
Route::get('/approval', [ApprovalController::class, 'index'])->name('approval.index');
Route::get('/approval/{id}', [ApprovalController::class, 'show'])->name('approval.show');
Route::post('/approval/{id}/approve', [ApprovalController::class, 'approve'])->name('approval.approve');
Route::post('/approval/{id}/reject', [ApprovalController::class, 'reject'])->name('approval.reject');
Route::post('/approval/{id}/realization', [ApprovalController::class, 'recordRealization'])->name('approval.realization');

// Profile & Private Signature Storage
Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Route::post('/profile/signature', [ProfileController::class, 'uploadSignature'])->name('profile.signature');
Route::post('/profile/signature/{id}/deactivate', [ProfileController::class, 'deactivateSignature'])->name('profile.signature.deactivate');
Route::get('/signatures/file/{filename}', [ProfileController::class, 'serveSignature'])->name('signature.serve');

// Evaluasi Konsumsi BBM & Laporan Kerugian (Admin)
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
