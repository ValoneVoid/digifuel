<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Role;
use App\Models\AreaApprover;
use App\Models\FuelStandard;
use App\Models\FuelPrice;
use App\Models\FuelRequest;
use App\Models\SystemBackground;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminMasterController extends Controller
{
    protected function getActiveUser(): User
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        return $user;
    }

    protected function respondSuccess(Request $request, string $tab, string $message, $data = null, int $status = 200)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ], $status);
        }

        return redirect()->route('admin.master', ['tab' => $tab])->with('success', $message);
    }

    /**
     * Validate 2-Step Deletion Confirmation when submitted via the 2-step verification modal or API.
     * Returns true if explicit 2-step verification ('HAPUS') was completed.
     */
    protected function verifyTwoStepDeleteConfirmation(Request $request): bool
    {
        if ($request->has('confirm_keyword') || $request->has('verification_step')) {
            $keyword = strtoupper(trim((string) $request->input('confirm_keyword', '')));
            $step = (string) $request->input('verification_step', '2');

            if ($step !== '2' || $keyword !== 'HAPUS') {
                throw ValidationException::withMessages([
                    'confirm_keyword' => 'Verifikasi 2 Langkah gagal: Anda wajib menyelesaikan Langkah 2 dan mengetik kata kunci HAPUS dengan benar.',
                ]);
            }

            return true;
        }

        return false;
    }

    public function index(Request $request)
    {
        $user = $this->getActiveUser();
        $tab = $request->get('tab', 'units');
        $perPage = max(5, min(100, (int) $request->input('per_page', 10)));

        $allVehicles = Vehicle::where('is_active', true)->orderBy('unit_code')->get();
        $allAreas = Area::where('is_active', true)->orderBy('name')->get();
        $allVendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $allUsers = User::where('is_active', true)->orderBy('name')->get();
        $roles = Role::all();

        $employees = Employee::orderBy('name')->paginate($perPage)->withQueryString();
        $vehicles = Vehicle::with(['defaultArea', 'activeFuelStandard'])->orderBy('unit_code')->paginate($perPage)->withQueryString();
        $areas = Area::orderBy('name')->paginate($perPage)->withQueryString();
        $vendors = Vendor::orderBy('name')->paginate($perPage)->withQueryString();
        $users = User::with(['employee', 'vendor'])->orderBy('name')->paginate($perPage)->withQueryString();
        $areaApprovers = AreaApprover::with(['area', 'user'])->orderBy('area_id')->paginate($perPage)->withQueryString();
        $fuelStandards = FuelStandard::with('vehicle')->orderBy('vehicle_id')->orderBy('effective_start', 'desc')->paginate($perPage)->withQueryString();
        $fuelPrices = FuelPrice::orderBy('effective_start', 'desc')->paginate($perPage)->withQueryString();
        $backgrounds = Schema::hasTable('system_backgrounds')
            ? SystemBackground::with('uploader')->orderBy('is_active', 'desc')->orderBy('id', 'desc')->paginate($perPage)->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $perPage);
        $auditLogs = FuelRequestHistory::with(['user', 'fuelRequest'])->latest('created_at')->paginate($perPage)->withQueryString();

        return view('admin.master', compact(
            'user',
            'tab',
            'perPage',
            'employees',
            'vehicles',
            'areas',
            'vendors',
            'users',
            'roles',
            'areaApprovers',
            'fuelStandards',
            'fuelPrices',
            'backgrounds',
            'auditLogs',
            'allVehicles',
            'allAreas',
            'allVendors',
            'allUsers'
        ));
    }

    // =========================================================================
    // 1. EMPLOYEE CRUD
    // =========================================================================

    public function editEmployee($id)
    {
        $employee = Employee::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            return $this->updateEmployee($request, $id);
        }

        $user = $this->getActiveUser();
        $validated = $request->validate([
            'nrp' => ['required', 'string', 'max:50', Rule::unique('employees', 'nrp')],
            'name' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'company' => ['required', 'string', 'max:150'],
            'source' => ['nullable', 'in:local,digisim'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $source = $validated['source'] ?? 'local';
        $emp = Employee::create([
            'nrp' => $validated['nrp'],
            'name' => $validated['name'],
            'position' => $validated['position'] ?? 'Driver / Operator',
            'department' => $validated['department'] ?? 'Operation',
            'company' => $validated['company'],
            'source' => $source,
            'external_system' => $source === 'digisim' ? 'DIGISIM' : null,
            'external_reference' => $validated['external_reference'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menambahkan data Employee: {$emp->nrp} - {$emp->name}.");

        return $this->respondSuccess($request, 'employees', "Data Employee {$emp->name} ({$emp->nrp}) berhasil disimpan.", $emp);
    }

    public function updateEmployee(Request $request, $id)
    {
        $user = $this->getActiveUser();
        $emp = Employee::findOrFail($id);

        $validated = $request->validate([
            'nrp' => ['required', 'string', 'max:50', Rule::unique('employees', 'nrp')->ignore($emp->id)],
            'name' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'company' => ['required', 'string', 'max:150'],
            'source' => ['nullable', 'in:local,digisim'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $source = $validated['source'] ?? $emp->source ?? 'local';
        $payload = [
            'nrp' => $validated['nrp'],
            'name' => $validated['name'],
            'position' => $validated['position'] ?? $emp->position ?? 'Driver / Operator',
            'department' => $validated['department'] ?? $emp->department ?? 'Operation',
            'company' => $validated['company'],
            'source' => $source,
            'external_system' => $source === 'digisim' ? ($emp->external_system ?? 'DIGISIM') : $emp->external_system,
            'external_reference' => array_key_exists('external_reference', $validated) && $validated['external_reference'] !== null
                ? $validated['external_reference']
                : $emp->external_reference,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $emp->is_active,
        ];

        $emp->update($payload);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, $emp->is_active ? 'active' : 'inactive', "Memperbarui data Employee ID #{$emp->id}: {$emp->nrp} - {$emp->name}.");

        return $this->respondSuccess($request, 'employees', "Data Employee {$emp->name} ({$emp->nrp}) berhasil diperbarui.", $emp->fresh());
    }

    public function toggleEmployee(Request $request, $id)
    {
        return $this->toggleEntityById($request, Employee::class, $id, 'employee', 'employees');
    }

    public function destroyEmployee(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $emp = Employee::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        if ($emp->fuelRequests()->exists() && !$twoStepVerified) {
            $msg = "Data Employee {$emp->name} ({$emp->nrp}) memiliki riwayat transaksi Fuel Request. Gunakan Verifikasi 2 Langkah untuk menghapus (Soft Delete) atau nonaktifkan statusnya.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'employees'])->with('error', $msg);
        }

        $emp->update(['is_active' => false]);
        $emp->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus (Soft Delete) data Employee ID #{$emp->id}: {$emp->nrp} - {$emp->name}{$verifyNote}.");

        return $this->respondSuccess($request, 'employees', "Data Employee {$emp->name} berhasil dihapus.");
    }

    // =========================================================================
    // 2. UNIT / VEHICLE CRUD
    // =========================================================================

    public function editVehicle($id)
    {
        $vehicle = Vehicle::with(['defaultArea', 'activeFuelStandard'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vehicle,
        ]);
    }

    public function storeVehicle(Request $request)
    {
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            return $this->updateVehicle($request, $id);
        }

        $user = $this->getActiveUser();
        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'max:80', Rule::unique('vehicles', 'unit_code')],
            'police_no' => ['required', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:120'],
            'unit_type' => ['nullable', 'string', 'max:80'],
            'meter_type' => ['required', 'in:km,hm'],
            'tank_capacity' => ['nullable', 'numeric', 'min:1'],
            'default_area_id' => ['nullable', 'exists:areas,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $veh = Vehicle::create([
            'unit_code' => $validated['unit_code'],
            'police_no' => $validated['police_no'],
            'model' => $validated['model'] ?? ($validated['unit_type'] ?? 'Light Vehicle'),
            'unit_type' => $validated['unit_type'] ?? ($validated['model'] ?? 'Light Vehicle'),
            'meter_type' => $validated['meter_type'],
            'tank_capacity' => $validated['tank_capacity'] ?? 80,
            'default_area_id' => $validated['default_area_id'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menambahkan data Unit: {$veh->unit_code} ({$veh->police_no}).");

        return $this->respondSuccess($request, 'units', "Data Unit {$veh->unit_code} berhasil disimpan.", $veh);
    }

    public function updateVehicle(Request $request, $id)
    {
        $user = $this->getActiveUser();
        $veh = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'max:80', Rule::unique('vehicles', 'unit_code')->ignore($veh->id)],
            'police_no' => ['required', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:120'],
            'unit_type' => ['nullable', 'string', 'max:80'],
            'meter_type' => ['required', 'in:km,hm'],
            'tank_capacity' => ['nullable', 'numeric', 'min:1'],
            'default_area_id' => ['nullable', 'exists:areas,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'unit_code' => $validated['unit_code'],
            'police_no' => $validated['police_no'],
            'model' => $validated['model'] ?? ($validated['unit_type'] ?? $veh->model),
            'unit_type' => $validated['unit_type'] ?? ($validated['model'] ?? $veh->unit_type ?? 'Light Vehicle'),
            'meter_type' => $validated['meter_type'],
            'tank_capacity' => $validated['tank_capacity'] ?? $veh->tank_capacity ?? 80,
            'default_area_id' => array_key_exists('default_area_id', $validated) ? $validated['default_area_id'] : $veh->default_area_id,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $veh->is_active,
        ];

        $veh->update($payload);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, $veh->is_active ? 'active' : 'inactive', "Memperbarui data Unit ID #{$veh->id}: {$veh->unit_code} ({$veh->police_no}).");

        return $this->respondSuccess($request, 'units', "Data Unit {$veh->unit_code} berhasil diperbarui.", $veh->fresh());
    }

    public function toggleVehicle(Request $request, $id)
    {
        return $this->toggleEntityById($request, Vehicle::class, $id, 'unit', 'units');
    }

    public function destroyVehicle(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $veh = Vehicle::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        if ($veh->fuelRequests()->exists() && !$twoStepVerified) {
            $msg = "Data Unit {$veh->unit_code} memiliki riwayat transaksi Fuel Request dan tidak dapat dihapus tanpa Verifikasi 2 Langkah. Silakan gunakan Verifikasi 2 Langkah atau nonaktifkan statusnya.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'units'])->with('error', $msg);
        }

        $unitCode = $veh->unit_code;
        $veh->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus data Unit ID #{$id}: {$unitCode}{$verifyNote}.");

        return $this->respondSuccess($request, 'units', "Data Unit {$unitCode} berhasil dihapus.");
    }

    // =========================================================================
    // 3. AREA CRUD
    // =========================================================================

    public function editArea($id)
    {
        $area = Area::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $area,
        ]);
    }

    public function storeArea(Request $request)
    {
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            return $this->updateArea($request, $id);
        }

        $user = $this->getActiveUser();
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('areas', 'code')],
            'name' => ['required', 'string', 'max:120', Rule::unique('areas', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = !empty($validated['code'])
            ? $validated['code']
            : $this->generateUniqueAreaCode($validated['name']);

        $area = Area::create([
            'name' => $validated['name'],
            'code' => $code,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menambahkan Area Operasional: {$area->name} ({$area->code}).");

        return $this->respondSuccess($request, 'areas', "Area operasional {$area->name} berhasil disimpan.", $area);
    }

    public function updateArea(Request $request, $id)
    {
        $user = $this->getActiveUser();
        $area = Area::findOrFail($id);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('areas', 'code')->ignore($area->id)],
            'name' => ['required', 'string', 'max:120', Rule::unique('areas', 'name')->ignore($area->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = !empty($validated['code'])
            ? $validated['code']
            : ($area->code ?: $this->generateUniqueAreaCode($validated['name'], $area->id));

        $area->update([
            'name' => $validated['name'],
            'code' => $code,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $area->is_active,
        ]);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, $area->is_active ? 'active' : 'inactive', "Memperbarui Area Operasional ID #{$area->id}: {$area->name} ({$area->code}).");

        return $this->respondSuccess($request, 'areas', "Area operasional {$area->name} berhasil diperbarui.", $area->fresh());
    }

    public function toggleArea(Request $request, $id)
    {
        return $this->toggleEntityById($request, Area::class, $id, 'area', 'areas');
    }

    public function destroyArea(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $area = Area::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        if (($area->fuelRequests()->exists() || FuelRequest::where('area', $area->name)->exists()) && !$twoStepVerified) {
            $msg = "Area operasional {$area->name} memiliki riwayat transaksi Fuel Request dan tidak dapat dihapus tanpa Verifikasi 2 Langkah.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'areas'])->with('error', $msg);
        }

        $areaName = $area->name;
        $area->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus Area Operasional ID #{$id}: {$areaName}{$verifyNote}.");

        return $this->respondSuccess($request, 'areas', "Area operasional {$areaName} berhasil dihapus.");
    }

    protected function generateUniqueAreaCode(string $name, ?int $ignoreId = null): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6)) ?: 'AREA';
        $code = $base;
        $counter = 1;
        while (Area::where('code', $code)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $code = $base . '-' . $counter;
            $counter++;
        }
        return $code;
    }

    // =========================================================================
    // 4. VENDOR CRUD
    // =========================================================================

    public function editVendor($id)
    {
        $vendor = Vendor::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendor,
        ]);
    }

    public function storeVendor(Request $request)
    {
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            return $this->updateVendor($request, $id);
        }

        $user = $this->getActiveUser();
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('vendors', 'code')],
            'name' => ['required', 'string', 'max:150', Rule::unique('vendors', 'name')],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = !empty($validated['code'])
            ? $validated['code']
            : $this->generateUniqueVendorCode();

        $vendor = Vendor::create([
            'name' => $validated['name'],
            'code' => $code,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menambahkan data Vendor: {$vendor->name} ({$vendor->code}).");

        return $this->respondSuccess($request, 'vendors', "Data Vendor {$vendor->name} berhasil disimpan.", $vendor);
    }

    public function updateVendor(Request $request, $id)
    {
        $user = $this->getActiveUser();
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('vendors', 'code')->ignore($vendor->id)],
            'name' => ['required', 'string', 'max:150', Rule::unique('vendors', 'name')->ignore($vendor->id)],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = !empty($validated['code'])
            ? $validated['code']
            : ($vendor->code ?: $this->generateUniqueVendorCode($vendor->id));

        $vendor->update([
            'name' => $validated['name'],
            'code' => $code,
            'contact_person' => array_key_exists('contact_person', $validated) ? $validated['contact_person'] : $vendor->contact_person,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $vendor->phone,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $vendor->is_active,
        ]);

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, $vendor->is_active ? 'active' : 'inactive', "Memperbarui data Vendor ID #{$vendor->id}: {$vendor->name} ({$vendor->code}).");

        return $this->respondSuccess($request, 'vendors', "Data Vendor {$vendor->name} berhasil diperbarui.", $vendor->fresh());
    }

    public function toggleVendor(Request $request, $id)
    {
        return $this->toggleEntityById($request, Vendor::class, $id, 'vendor', 'vendors');
    }

    public function destroyVendor(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $vendor = Vendor::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        if (($vendor->fuelRequests()->exists() || FuelRequest::where('vendor_name', $vendor->name)->exists()) && !$twoStepVerified) {
            $msg = "Data Vendor {$vendor->name} memiliki riwayat transaksi Fuel Request dan tidak dapat dihapus tanpa Verifikasi 2 Langkah.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'vendors'])->with('error', $msg);
        }

        $vendorName = $vendor->name;
        $vendor->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus data Vendor ID #{$id}: {$vendorName}{$verifyNote}.");

        return $this->respondSuccess($request, 'vendors', "Data Vendor {$vendorName} berhasil dihapus.");
    }

    protected function generateUniqueVendorCode(?int $ignoreId = null): string
    {
        do {
            $code = 'VND-' . rand(100, 999);
        } while (Vendor::where('code', $code)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists());

        return $code;
    }

    // =========================================================================
    // 5. USER & ROLE CRUD
    // =========================================================================

    public function editUser($id)
    {
        $targetUser = User::with(['employee', 'vendor', 'roles'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $targetUser,
        ]);
    }

    public function storeUser(Request $request)
    {
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            return $this->updateUser($request, $id);
        }

        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', 'in:admin,driver,spv,depthead,vendor,fuelman'],
            'jabatan' => ['required', 'string', 'max:100'],
            'nrp' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nrp')],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'jabatan' => $validated['jabatan'],
            'nrp' => $validated['nrp'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'vendor_id' => $validated['vendor_id'] ?? null,
            'password' => Hash::make(!empty($validated['password']) ? $validated['password'] : 'password123'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        $targetUser = User::create($payload);
        $this->syncUserRolePivot($targetUser, $validated['role']);

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, 'active', "Menambahkan akun pengguna: {$targetUser->username} (Role: {$targetUser->role}).");

        return $this->respondSuccess($request, 'users', "Akun pengguna {$targetUser->name} berhasil disimpan.", $targetUser);
    }

    public function updateUser(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $targetUser = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')->ignore($targetUser->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'role' => ['required', 'in:admin,driver,spv,depthead,vendor,fuelman'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'nrp' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nrp')->ignore($targetUser->id)],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'jabatan' => $validated['jabatan'] ?? $targetUser->jabatan ?? ucfirst($validated['role']),
            'nrp' => array_key_exists('nrp', $validated) ? $validated['nrp'] : $targetUser->nrp,
            'employee_id' => array_key_exists('employee_id', $validated) ? $validated['employee_id'] : $targetUser->employee_id,
            'vendor_id' => array_key_exists('vendor_id', $validated) ? $validated['vendor_id'] : $targetUser->vendor_id,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $targetUser->is_active,
        ];

        // Password hanya di-hash dan diperbarui jika diisi oleh Admin; jika kosong, password lama tetap utuh
        if ($request->filled('password')) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($payload);
        $this->syncUserRolePivot($targetUser, $validated['role']);

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, $targetUser->is_active ? 'active' : 'inactive', "Memperbarui akun pengguna ID #{$targetUser->id}: {$targetUser->username} (Role: {$targetUser->role}).");

        return $this->respondSuccess($request, 'users', "Akun pengguna {$targetUser->name} berhasil diperbarui.", $targetUser->fresh(['roles', 'employee', 'vendor']));
    }

    public function toggleUser(Request $request, $id)
    {
        return $this->toggleEntityById($request, User::class, $id, 'user', 'users');
    }

    public function destroyUser(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $targetUser = User::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        if ((int) $targetUser->id === (int) $admin->id) {
            $msg = 'Akun Admin yang sedang Anda gunakan login saat ini tidak dapat dihapus.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'users'])->with('error', $msg);
        }

        $hasHistory = $targetUser->fuelRequests()->exists()
            || $targetUser->spvRequests()->exists()
            || $targetUser->deptheadRequests()->exists()
            || $targetUser->fuelmanRequests()->exists()
            || $targetUser->approvals()->exists()
            || $targetUser->fuelTransactions()->exists();

        if ($hasHistory && !$twoStepVerified) {
            $msg = "Akun pengguna {$targetUser->name} ({$targetUser->username}) memiliki riwayat pengajuan atau persetujuan BBM dan tidak dapat dihapus tanpa Verifikasi 2 Langkah.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'users'])->with('error', $msg);
        }

        $username = $targetUser->username;
        $targetUser->roles()->detach();
        $targetUser->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus akun pengguna ID #{$id}: {$username}{$verifyNote}.");

        return $this->respondSuccess($request, 'users', "Akun pengguna {$username} berhasil dihapus.");
    }

    protected function syncUserRolePivot(User $targetUser, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $targetUser->roles()->sync([$role->id]);
        }
    }

    // =========================================================================
    // 6. AREA APPROVER CRUD
    // =========================================================================

    public function editAreaApprover($id)
    {
        $approver = AreaApprover::with(['area', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $approver,
        ]);
    }

    public function storeAreaApprover(Request $request)
    {
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            return $this->updateAreaApprover($request, $id);
        }

        $admin = $this->getActiveUser();
        $validated = $this->validateAreaApproverPayload($request);

        $aa = AreaApprover::create([
            'area_id' => $validated['area_id'],
            'user_id' => $validated['user_id'],
            'role_type' => $validated['role_type'],
            'start_date' => $validated['start_date'] ?? now()->toDateString(),
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, 'active', "Menetapkan penugasan Approver ({$validated['role_type']}) untuk Area ID {$validated['area_id']}.");

        return $this->respondSuccess($request, 'approvers', 'Penugasan Approver Area berhasil disimpan.', $aa->load(['area', 'user']));
    }

    public function updateAreaApprover(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $aa = AreaApprover::findOrFail($id);

        $validated = $this->validateAreaApproverPayload($request, $aa->id);

        $aa->update([
            'area_id' => $validated['area_id'],
            'user_id' => $validated['user_id'],
            'role_type' => $validated['role_type'],
            'start_date' => $validated['start_date'] ?? ($aa->start_date ? $aa->start_date->toDateString() : now()->toDateString()),
            'end_date' => array_key_exists('end_date', $validated) ? $validated['end_date'] : ($aa->end_date ? $aa->end_date->toDateString() : null),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $aa->is_active,
        ]);

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, $aa->is_active ? 'active' : 'inactive', "Memperbarui penugasan Approver ID #{$aa->id} ({$aa->role_type}) untuk Area ID {$aa->area_id}.");

        return $this->respondSuccess($request, 'approvers', 'Penugasan Approver Area berhasil diperbarui.', $aa->fresh(['area', 'user']));
    }

    public function toggleAreaApprover(Request $request, $id)
    {
        return $this->toggleEntityById($request, AreaApprover::class, $id, 'approver', 'approvers');
    }

    public function destroyAreaApprover(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $aa = AreaApprover::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        $hasHistory = FuelRequest::where('area_id', $aa->area_id)
            ->where(function ($q) use ($aa) {
                $q->where('spv_id', $aa->user_id)
                    ->orWhere('depthead_id', $aa->user_id)
                    ->orWhere('manager_id', $aa->user_id)
                    ->orWhere('fuelman_id', $aa->user_id)
                    ->orWhere('vendor_user_id', $aa->user_id);
            })->exists();

        if ($hasHistory && !$twoStepVerified) {
            $msg = 'Penugasan Approver ini telah digunakan pada riwayat transaksi Fuel Request dan tidak dapat dihapus tanpa Verifikasi 2 Langkah.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('admin.master', ['tab' => 'approvers'])->with('error', $msg);
        }

        $aa->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus penugasan Approver Area ID #{$id}{$verifyNote}.");

        return $this->respondSuccess($request, 'approvers', 'Penugasan Approver Area berhasil dihapus.');
    }

    protected function validateAreaApproverPayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'user_id' => ['required', 'exists:users,id'],
            'role_type' => ['required', 'in:spv,manager,vendor,fuelman'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $roleType = $validated['role_type'];

        $roleMatches = match ($roleType) {
            'spv' => $targetUser->isSpv(),
            'manager' => $targetUser->isManager(),
            'vendor' => $targetUser->isVendor(),
            'fuelman' => $targetUser->isFuelman(),
            default => false,
        };

        if (!$roleMatches) {
            throw ValidationException::withMessages([
                'user_id' => "Pengguna {$targetUser->name} (role: {$targetUser->role}) tidak sesuai dengan jenis penugasan approver '{$roleType}'.",
            ]);
        }

        $duplicateExists = AreaApprover::where('area_id', $validated['area_id'])
            ->where('user_id', $validated['user_id'])
            ->where('role_type', $validated['role_type'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'user_id' => 'Penugasan approver untuk kombinasi Area, User, dan Tipe Role tersebut sudah terdaftar.',
            ]);
        }

        return $validated;
    }

    // =========================================================================
    // 7. FUEL STANDARD & 8. FUEL PRICE & 9. SYSTEM BACKGROUND
    // =========================================================================

    public function storeBackground(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:system_backgrounds,id',
            'title' => 'required|string|max:150',
            'notes' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'set_active' => 'nullable|boolean',
        ], [
            'image.image' => 'File yang diunggah harus berupa gambar (JPG, PNG, atau WEBP).',
            'image.max' => 'Ukuran gambar background maksimal 5 MB.',
        ]);

        $bgDir = public_path('images/backgrounds');
        if (!is_dir($bgDir)) {
            @mkdir($bgDir, 0775, true);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'ut_bg_' . date('Ymd_His') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($bgDir, $filename);
            $imagePath = 'images/backgrounds/' . $filename;
        }

        $setActive = $request->boolean('set_active', true);

        if (!empty($validated['id'])) {
            $bg = SystemBackground::findOrFail($validated['id']);
            $payload = [
                'title' => $validated['title'],
                'notes' => $validated['notes'] ?? null,
            ];
            if ($imagePath) {
                $payload['image_path'] = $imagePath;
            }
            if ($setActive) {
                SystemBackground::where('id', '!=', $bg->id)->update(['is_active' => false]);
                $payload['is_active'] = true;
            }
            $bg->update($payload);
            $actionMsg = "Memperbarui Background Sistem: {$bg->title}.";
        } else {
            if (!$imagePath) {
                return redirect()->route('admin.master', ['tab' => 'backgrounds'])
                    ->withErrors(['image' => 'Silakan pilih file gambar background untuk diunggah.']);
            }
            if ($setActive) {
                SystemBackground::query()->update(['is_active' => false]);
            }
            $bg = SystemBackground::create([
                'title' => $validated['title'],
                'image_path' => $imagePath,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $setActive,
                'uploaded_by' => $admin->id,
            ]);
            $actionMsg = "Mengunggah Background Sistem baru: {$bg->title}.";
        }

        FuelRequestHistory::record(null, $admin->id, 'background_updated', null, 'active', $actionMsg);

        return redirect()->route('admin.master', ['tab' => 'backgrounds'])
            ->with('success', "Background '{$bg->title}' berhasil disimpan" . ($bg->is_active ? ' dan diterapkan sebagai background aktif.' : '.'));
    }

    public function deleteBackground(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $bg = SystemBackground::findOrFail($id);

        if ($bg->image_path === 'images/mining_bg.jpg') {
            return redirect()->route('admin.master', ['tab' => 'backgrounds'])
                ->with('error', 'Background default bawaan sistem tidak dapat dihapus.');
        }

        $wasActive = $bg->is_active;
        $title = $bg->title;

        if ($bg->image_path && str_starts_with($bg->image_path, 'images/backgrounds/')) {
            $fullPath = public_path($bg->image_path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $bg->delete();

        if ($wasActive) {
            $fallback = SystemBackground::first();
            if ($fallback) {
                $fallback->update(['is_active' => true]);
            }
        }

        FuelRequestHistory::record(null, $admin->id, 'background_deleted', 'active', 'deleted', "Menghapus gambar Background Sistem: {$title}.");

        return redirect()->route('admin.master', ['tab' => 'backgrounds'])
            ->with('success', "Background '{$title}' berhasil dihapus.");
    }

    // =========================================================================
    // 10. TOGGLE ACTIVE / INACTIVE STATUS
    // =========================================================================

    protected function toggleEntityById(Request $request, string $modelClass, $id, string $entityName, string $tab)
    {
        $admin = $this->getActiveUser();
        $record = $modelClass::findOrFail($id);
        $newStatus = !$record->is_active;
        $record->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
        $recordLabel = $record->unit_code ?? $record->name ?? $record->fuel_type ?? ('ID #' . $record->id);

        FuelRequestHistory::record(
            null,
            $admin->id,
            'master_status_toggled',
            $newStatus ? 'inactive' : 'active',
            $newStatus ? 'active' : 'inactive',
            "Status Master Data ({$entityName}: {$recordLabel}) diubah menjadi {$statusLabel}."
        );

        return $this->respondSuccess(
            $request,
            $request->input('tab', $tab),
            "Status data {$recordLabel} berhasil diubah menjadi {$statusLabel}.",
            $record->fresh()
        );
    }

    public function toggleStatus(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'entity' => 'required|in:unit,employee,area,vendor,user,approver,standard,price,background',
            'id' => 'required|integer',
            'tab' => 'nullable|string',
        ]);

        if ($validated['entity'] === 'background') {
            $bg = SystemBackground::findOrFail($validated['id']);
            SystemBackground::where('id', '!=', $bg->id)->update(['is_active' => false]);
            $bg->update(['is_active' => true]);

            FuelRequestHistory::record(
                null,
                $admin->id,
                'background_activated',
                'inactive',
                'active',
                "Mengaktifkan gambar Background Sistem: {$bg->title}."
            );

            return redirect()->route('admin.master', ['tab' => 'backgrounds'])
                ->with('success', "Background '{$bg->title}' berhasil diaktifkan sebagai tampilan utama sistem.");
        }

        $modelMap = [
            'unit' => [Vehicle::class, 'units'],
            'employee' => [Employee::class, 'employees'],
            'area' => [Area::class, 'areas'],
            'vendor' => [Vendor::class, 'vendors'],
            'user' => [User::class, 'users'],
            'approver' => [AreaApprover::class, 'approvers'],
            'standard' => [FuelStandard::class, 'standards'],
            'price' => [FuelPrice::class, 'prices'],
        ];

        [$modelClass, $defaultTab] = $modelMap[$validated['entity']];

        return $this->toggleEntityById(
            $request,
            $modelClass,
            $validated['id'],
            $validated['entity'],
            $validated['tab'] ?? $defaultTab
        );
    }

    public function storeFuelStandard(Request $request)
    {
        $admin = $this->getActiveUser();
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            $std = FuelStandard::findOrFail($id);
        } else {
            $std = null;
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'standard_type' => 'nullable|in:km_per_liter,liter_per_hour',
            'standard_value' => 'required|numeric|gt:0',
            'tolerance_percentage' => 'nullable|numeric|min:0|max:100',
            'effective_start' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        if (!$std && !$vehicle->is_active) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Tidak dapat menambahkan standar baru untuk unit yang sedang nonaktif.',
            ]);
        }

        $standardType = !empty($validated['standard_type'])
            ? $validated['standard_type']
            : ($vehicle->meter_type === 'hm' ? 'liter_per_hour' : 'km_per_liter');

        $tolerancePct = array_key_exists('tolerance_percentage', $validated) && $validated['tolerance_percentage'] !== null && $validated['tolerance_percentage'] !== ''
            ? (float) $validated['tolerance_percentage']
            : 5.00;

        if ($std) {
            $std->update([
                'vehicle_id' => $validated['vehicle_id'],
                'standard_type' => $standardType,
                'standard_value' => $validated['standard_value'],
                'tolerance_percentage' => $tolerancePct,
                'effective_start' => $validated['effective_start'],
                'notes' => $validated['notes'] ?? null,
            ]);
            FuelRequestHistory::record(null, $admin->id, 'standard_updated', null, 'active', "Memperbarui standar konsumsi BBM ID #{$std->id} untuk Unit {$vehicle->unit_code}: {$std->standard_value} ({$std->standard_type}).");
            return redirect()->route('admin.master', ['tab' => 'standards'])->with('success', "Data standar konsumsi BBM untuk unit {$vehicle->unit_code} berhasil diperbarui.");
        }

        // Close previous active standard without overwriting it (preserves history)
        FuelStandard::where('vehicle_id', $vehicle->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'effective_end' => $validated['effective_start'],
            ]);

        $std = FuelStandard::create([
            'vehicle_id' => $vehicle->id,
            'standard_type' => $standardType,
            'standard_value' => $validated['standard_value'],
            'tolerance_percentage' => $tolerancePct,
            'effective_start' => $validated['effective_start'],
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        FuelRequestHistory::record(null, $admin->id, 'standard_updated', null, 'active', "Menambahkan standar konsumsi BBM baru untuk Unit {$vehicle->unit_code}: {$std->standard_value} ({$std->standard_type}).");

        return redirect()->route('admin.master', ['tab' => 'standards'])->with('success', "Standar konsumsi BBM untuk unit {$vehicle->unit_code} berhasil ditambahkan (riwayat standar lama tetap tersimpan).");
    }

    public function storeFuelPrice(Request $request)
    {
        $admin = $this->getActiveUser();
        $id = $request->input('id');
        if ($id !== null && $id !== '') {
            $price = FuelPrice::findOrFail($id);
        } else {
            $price = null;
        }

        $validated = $request->validate([
            'fuel_type' => 'required|string|max:80',
            'price_per_liter' => 'required|numeric|gt:0',
            'effective_start' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($price) {
            $price->update([
                'fuel_type' => $validated['fuel_type'],
                'price_per_liter' => $validated['price_per_liter'],
                'effective_start' => $validated['effective_start'],
                'notes' => $validated['notes'] ?? null,
            ]);
            FuelRequestHistory::record(null, $admin->id, 'price_updated', null, 'active', "Memperbarui harga BBM ID #{$price->id} ({$price->fuel_type}): Rp " . number_format($price->price_per_liter, 0, ',', '.') . "/Liter.");
            return redirect()->route('admin.master', ['tab' => 'prices'])->with('success', 'Data harga BBM berhasil diperbarui.');
        }

        FuelPrice::where('is_active', true)->update([
            'is_active' => false,
            'effective_end' => $validated['effective_start'],
        ]);

        $price = FuelPrice::create([
            'fuel_type' => $validated['fuel_type'],
            'price_per_liter' => $validated['price_per_liter'],
            'effective_start' => $validated['effective_start'],
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        FuelRequestHistory::record(null, $admin->id, 'price_updated', null, 'active', "Memperbarui harga BBM ({$price->fuel_type}): Rp " . number_format($price->price_per_liter, 0, ',', '.') . "/Liter mulai {$price->effective_start->format('d/m/Y')}.");

        return redirect()->route('admin.master', ['tab' => 'prices'])->with('success', 'Harga BBM baru berhasil disimpan (riwayat harga lama tetap tersimpan).');
    }

    public function destroyFuelStandard(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $std = FuelStandard::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        $label = ($std->vehicle?->unit_code ?? 'Vehicle #' . $std->vehicle_id) . ' (' . (float) $std->standard_value . ')';
        $std->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus Standar BBM ID #{$id}: {$label}{$verifyNote}.");

        return $this->respondSuccess($request, 'standards', "Data Standar BBM {$label} berhasil dihapus.");
    }

    public function destroyFuelPrice(Request $request, $id)
    {
        $admin = $this->getActiveUser();
        $price = FuelPrice::findOrFail($id);
        $twoStepVerified = $this->verifyTwoStepDeleteConfirmation($request);

        $label = $price->fuel_type . ' (Rp ' . number_format($price->price_per_liter, 0, ',', '.') . ')';
        $price->delete();

        $verifyNote = $twoStepVerified ? ' [Terverifikasi 2 Langkah]' : '';
        FuelRequestHistory::record(null, $admin->id, 'master_deleted', 'active', 'deleted', "Menghapus Harga BBM ID #{$id}: {$label}{$verifyNote}.");

        return $this->respondSuccess($request, 'prices', "Data Harga BBM {$label} berhasil dihapus.");
    }
}
