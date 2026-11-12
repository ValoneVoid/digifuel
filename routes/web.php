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
use App\Http\Controllers\FuelRequestPdfController;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Application Routes (Requires Authentication)
Route::middleware(['auth'])->group(function () {
    // Root & Session Actions
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::post('/switch-user', [AuthController::class, 'switchUser'])->name('switch.user');
    Route::get('/notifications/{id}/read', [AuthController::class, 'readNotification'])->name('notifications.read');

    // Common Authenticated Modules (All Active Roles, Scoped by Role/Policy inside Controller)
    Route::middleware(['role:admin,driver,spv,depthead,vendor,fuelman'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/charts', [DashboardController::class, 'charts'])->name('dashboard.charts');

        // Panduan & Tutorial Penggunaan Sistem
        Route::get('/panduan', function () {
            return view('panduan');
        })->name('panduan');

        // History & Excel (.xlsx) Export
        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');

        // Printable Fuel Request Document & Modal Preview (Protected by FuelRequestPolicy)
        Route::get('/fuel/{id}/document', [FuelRequestController::class, 'document'])->name('fuel.document');
        Route::get('/fuel/{id}/document-partial', [FuelRequestController::class, 'documentPartial'])->name('fuel.document.partial');
        Route::get('/fuel/{id}/pdf', [FuelRequestPdfController::class, 'download'])->name('fuel.pdf');
        Route::get('/fuel/{id}/pdf/stream', [FuelRequestPdfController::class, 'stream'])->name('fuel.pdf.stream');

        // Profile, Profile Photo CRUD & Private Signature Storage
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::match(['post', 'put', 'patch'], '/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo.upload');
        Route::match(['delete', 'post'], '/profile/photo/delete', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
        Route::get('/profile/photo/{filename}', [ProfileController::class, 'servePhoto'])->name('profile.photo.serve');
        Route::post('/profile/signature', [ProfileController::class, 'uploadSignature'])->name('profile.signature');
        Route::post('/profile/signature/{id}/deactivate', [ProfileController::class, 'deactivateSignature'])->name('profile.signature.deactivate');
        Route::match(['delete', 'post'], '/profile/signature/{id}/delete', [ProfileController::class, 'deleteSignature'])->name('profile.signature.delete');
        Route::get('/signatures/file/{filename}', [ProfileController::class, 'serveSignature'])->name('signature.serve');
    });

    // Fuel Request Submission, Draft, Cancellation & Dependent Dropdown AJAX (Driver, SPV & Admin)
    Route::middleware(['role:driver,spv,admin'])->group(function () {
        Route::get('/fuel', [FuelRequestController::class, 'create'])->name('fuel.create');
        Route::post('/fuel', [FuelRequestController::class, 'store'])->name('fuel.store');
        Route::post('/fuel/{id}/submit-draft', [FuelRequestController::class, 'submitDraft'])->name('fuel.submit.draft');
        Route::post('/fuel/{id}/cancel', [FuelRequestController::class, 'cancel'])->name('fuel.cancel');
        Route::match(['delete', 'post'], '/fuel/{id}/destroy', [FuelRequestController::class, 'destroy'])->name('fuel.destroy');
        Route::get('/api/area-approvers', [FuelRequestController::class, 'areaApprovers'])->name('api.area.approvers');
        Route::get('/api/vehicle-lookup', [FuelRequestController::class, 'vehicleLookup'])->name('api.vehicle.lookup');
    });

    // Detail Pengajuan Fuel Request (Dapat diakses oleh Driver pemilik pengajuan, SPV, Dept Head, Vendor, Fuel Man, Admin sesuai Policy)
    Route::get('/approval/{id}', [ApprovalController::class, 'show'])->name('approval.show');

    // Sequential Approval (SPV, Dept Head/Manager, Vendor) & Fuel Man Module
    Route::middleware(['role:spv,depthead,vendor,fuelman,admin'])->group(function () {
        Route::get('/approval', [ApprovalController::class, 'index'])->name('approval.index');
        Route::post('/approval/{id}/approve', [ApprovalController::class, 'approve'])->name('approval.approve');
        Route::post('/approval/{id}/reject', [ApprovalController::class, 'reject'])->name('approval.reject');
        Route::post('/approval/{id}/update-liters', [ApprovalController::class, 'updateLiters'])->name('approval.updateLiters');
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

        // Legacy Form Action Routes (Backward Compatible with admin/master.blade.php)
        Route::post('/admin/master/employee', [AdminMasterController::class, 'storeEmployee'])->name('admin.master.employee');
        Route::post('/admin/master/vehicle', [AdminMasterController::class, 'storeVehicle'])->name('admin.master.vehicle');
        Route::post('/admin/master/area', [AdminMasterController::class, 'storeArea'])->name('admin.master.area');
        Route::post('/admin/master/unit-vendor', [AdminMasterController::class, 'storeUnitVendor'])->name('admin.master.unit-vendor');
        Route::post('/admin/master/vendor', [AdminMasterController::class, 'storeVendor'])->name('admin.master.vendor');
        Route::post('/admin/master/user', [AdminMasterController::class, 'storeUser'])->name('admin.master.user');
        Route::post('/admin/master/area-approver', [AdminMasterController::class, 'storeAreaApprover'])->name('admin.master.approver');
        Route::post('/admin/master/fuel-standard', [AdminMasterController::class, 'storeFuelStandard'])->name('admin.master.standard');
        Route::post('/admin/master/fuel-price', [AdminMasterController::class, 'storeFuelPrice'])->name('admin.master.price');
        Route::post('/admin/master/background', [AdminMasterController::class, 'storeBackground'])->name('admin.master.background');
        Route::post('/admin/master/background/opacity', [AdminMasterController::class, 'updateBackgroundOpacity'])->name('admin.master.background.opacity');
        Route::post('/admin/master/background/{id}/delete', [AdminMasterController::class, 'deleteBackground'])->name('admin.master.background.delete');
        Route::post('/admin/master/toggle-status', [AdminMasterController::class, 'toggleStatus'])->name('admin.master.toggle');
        Route::patch('/admin/master/toggle-status', [AdminMasterController::class, 'toggleStatus']);

        // 1. Employee ID-based CRUD Routes
        Route::get('/admin/master/employees/{id}/edit', [AdminMasterController::class, 'editEmployee'])->name('admin.master.employees.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/employees/{id}', [AdminMasterController::class, 'updateEmployee'])->name('admin.master.employees.update');
        Route::match(['patch', 'post'], '/admin/master/employees/{id}/toggle-active', [AdminMasterController::class, 'toggleEmployee'])->name('admin.master.employees.toggle');
        Route::delete('/admin/master/employees/{id}', [AdminMasterController::class, 'destroyEmployee'])->name('admin.master.employees.destroy');

        // 2. Unit / Vehicle ID-based CRUD Routes (supports both /units/{id} and /vehicles/{id})
        Route::get('/admin/master/units/{id}/edit', [AdminMasterController::class, 'editVehicle'])->name('admin.master.units.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/units/{id}', [AdminMasterController::class, 'updateVehicle'])->name('admin.master.units.update');
        Route::match(['patch', 'post'], '/admin/master/units/{id}/toggle-active', [AdminMasterController::class, 'toggleVehicle'])->name('admin.master.units.toggle');
        Route::delete('/admin/master/units/{id}', [AdminMasterController::class, 'destroyVehicle'])->name('admin.master.units.destroy');

        Route::get('/admin/master/vehicles/{id}/edit', [AdminMasterController::class, 'editVehicle'])->name('admin.master.vehicles.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/vehicles/{id}', [AdminMasterController::class, 'updateVehicle'])->name('admin.master.vehicles.update');
        Route::match(['patch', 'post'], '/admin/master/vehicles/{id}/toggle-active', [AdminMasterController::class, 'toggleVehicle'])->name('admin.master.vehicles.toggle');
        Route::delete('/admin/master/vehicles/{id}', [AdminMasterController::class, 'destroyVehicle'])->name('admin.master.vehicles.destroy');

        // 3. Area ID-based CRUD Routes
        Route::get('/admin/master/areas/{id}/edit', [AdminMasterController::class, 'editArea'])->name('admin.master.areas.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/areas/{id}', [AdminMasterController::class, 'updateArea'])->name('admin.master.areas.update');
        Route::match(['patch', 'post'], '/admin/master/areas/{id}/toggle-active', [AdminMasterController::class, 'toggleArea'])->name('admin.master.areas.toggle');
        Route::delete('/admin/master/areas/{id}', [AdminMasterController::class, 'destroyArea'])->name('admin.master.areas.destroy');

        // 4. Vendor BBM ID-based CRUD Routes
        Route::get('/admin/master/vendors/{id}/edit', [AdminMasterController::class, 'editVendor'])->name('admin.master.vendors.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/vendors/{id}', [AdminMasterController::class, 'updateVendor'])->name('admin.master.vendors.update');
        Route::match(['patch', 'post'], '/admin/master/vendors/{id}/toggle-active', [AdminMasterController::class, 'toggleVendor'])->name('admin.master.vendors.toggle');
        Route::delete('/admin/master/vendors/{id}', [AdminMasterController::class, 'destroyVendor'])->name('admin.master.vendors.destroy');

        // 4b. Vendor Unit ID-based CRUD Routes
        Route::get('/admin/master/unit-vendors/{id}/edit', [AdminMasterController::class, 'editUnitVendor'])->name('admin.master.unit-vendors.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/unit-vendors/{id}', [AdminMasterController::class, 'updateUnitVendor'])->name('admin.master.unit-vendors.update');
        Route::match(['patch', 'post'], '/admin/master/unit-vendors/{id}/toggle-active', [AdminMasterController::class, 'toggleUnitVendor'])->name('admin.master.unit-vendors.toggle');
        Route::delete('/admin/master/unit-vendors/{id}', [AdminMasterController::class, 'destroyUnitVendor'])->name('admin.master.unit-vendors.destroy');

        // 5. User ID-based CRUD Routes
        Route::get('/admin/master/users/{id}/edit', [AdminMasterController::class, 'editUser'])->name('admin.master.users.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/users/{id}', [AdminMasterController::class, 'updateUser'])->name('admin.master.users.update');
        Route::match(['patch', 'post'], '/admin/master/users/{id}/toggle-active', [AdminMasterController::class, 'toggleUser'])->name('admin.master.users.toggle');
        Route::delete('/admin/master/users/{id}', [AdminMasterController::class, 'destroyUser'])->name('admin.master.users.destroy');

        // 6. Area Approver ID-based CRUD Routes
        Route::get('/admin/master/area-approvers/{id}/edit', [AdminMasterController::class, 'editAreaApprover'])->name('admin.master.approvers.edit');
        Route::match(['put', 'patch', 'post'], '/admin/master/area-approvers/{id}', [AdminMasterController::class, 'updateAreaApprover'])->name('admin.master.approvers.update');
        Route::match(['patch', 'post'], '/admin/master/area-approvers/{id}/toggle-active', [AdminMasterController::class, 'toggleAreaApprover'])->name('admin.master.approvers.toggle');
        Route::delete('/admin/master/area-approvers/{id}', [AdminMasterController::class, 'destroyAreaApprover'])->name('admin.master.approvers.destroy');

        // 7. Fuel Standard & Fuel Price Delete Routes
        Route::delete('/admin/master/fuel-standards/{id}', [AdminMasterController::class, 'destroyFuelStandard'])->name('admin.master.standards.destroy');
        Route::delete('/admin/master/fuel-prices/{id}', [AdminMasterController::class, 'destroyFuelPrice'])->name('admin.master.prices.destroy');

        // 8. Audit Trail Export & Retention Purge Routes
        Route::get('/admin/master/audit/export', [AdminMasterController::class, 'exportAudit'])->name('admin.master.audit.export');
        Route::post('/admin/master/audit/purge', [AdminMasterController::class, 'purgeAudit'])->name('admin.master.audit.purge');
    });
});
