<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Role;
use App\Models\AreaApprover;
use App\Models\FuelStandard;
use App\Models\FuelPrice;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminMasterController extends Controller
{
    protected function getActiveUser(): User
    {
        if (!Auth::check()) {
            $default = User::where('role', 'admin')->first() ?? User::first();
            if ($default) {
                Auth::login($default);
            }
        }
        return Auth::user();
    }

    public function index(Request $request)
    {
        $user = $this->getActiveUser();
        $tab = $request->get('tab', 'units');

        $employees = Employee::orderBy('name')->get();
        $vehicles = Vehicle::with(['defaultArea', 'activeFuelStandard'])->orderBy('unit_code')->get();
        $areas = Area::orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $users = User::with(['employee', 'vendor'])->orderBy('name')->get();
        $roles = Role::all();
        $areaApprovers = AreaApprover::with(['area', 'user'])->orderBy('area_id')->get();
        $fuelStandards = FuelStandard::with('vehicle')->orderBy('vehicle_id')->orderBy('effective_start', 'desc')->get();
        $fuelPrices = FuelPrice::orderBy('effective_start', 'desc')->get();
        $auditLogs = FuelRequestHistory::with(['user', 'fuelRequest'])->latest('created_at')->limit(100)->get();

        return view('admin.master', compact(
            'user',
            'tab',
            'employees',
            'vehicles',
            'areas',
            'vendors',
            'users',
            'roles',
            'areaApprovers',
            'fuelStandards',
            'fuelPrices',
            'auditLogs'
        ));
    }

    // 1. Store / Update Employee
    public function storeEmployee(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'nrp' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'company' => 'required|string|max:150',
            'source' => 'nullable|in:local,digisim',
            'external_reference' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $emp = Employee::updateOrCreate(
            ['nrp' => $validated['nrp']],
            [
                'name' => $validated['name'],
                'position' => $validated['position'] ?? 'Driver / Operator',
                'department' => $validated['department'] ?? 'Operation',
                'company' => $validated['company'],
                'source' => $validated['source'] ?? 'local',
                'external_system' => ($validated['source'] ?? 'local') === 'digisim' ? 'DIGISIM' : null,
                'external_reference' => $validated['external_reference'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Employee: {$emp->nrp} - {$emp->name}.");

        return redirect()->route('admin.master', ['tab' => 'employees'])->with('success', "Data Employee {$emp->name} ({$emp->nrp}) berhasil disimpan.");
    }

    // 2. Store / Update Unit (Vehicle)
    public function storeVehicle(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'unit_code' => 'required|string|max:80',
            'police_no' => 'required|string|max:50',
            'model' => 'nullable|string|max:120',
            'unit_type' => 'nullable|string|max:80',
            'meter_type' => 'required|in:km,hm',
            'tank_capacity' => 'nullable|numeric|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $veh = Vehicle::updateOrCreate(
            ['unit_code' => $validated['unit_code']],
            [
                'police_no' => $validated['police_no'],
                'model' => $validated['model'] ?? $validated['unit_type'],
                'unit_type' => $validated['unit_type'] ?? 'Light Vehicle',
                'meter_type' => $validated['meter_type'],
                'tank_capacity' => $validated['tank_capacity'] ?? 80,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Unit: {$veh->unit_code} ({$veh->police_no}).");

        return redirect()->route('admin.master', ['tab' => 'units'])->with('success', "Data Unit {$veh->unit_code} berhasil disimpan.");
    }

    // 3. Store / Update Area
    public function storeArea(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'code' => 'nullable|string|max:30',
            'name' => 'required|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        $area = Area::updateOrCreate(
            ['name' => $validated['name']],
            [
                'code' => $validated['code'] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 6)),
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Area Operasional: {$area->name}.");

        return redirect()->route('admin.master', ['tab' => 'areas'])->with('success', "Area operasional {$area->name} berhasil disimpan.");
    }

    // 4. Store / Update Vendor
    public function storeVendor(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'code' => 'nullable|string|max:30',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'is_active' => 'nullable|boolean',
        ]);

        $vendor = Vendor::updateOrCreate(
            ['name' => $validated['name']],
            [
                'code' => $validated['code'] ?? 'VND-' . rand(100, 999),
                'contact_person' => $validated['contact_person'],
                'phone' => $validated['phone'],
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Vendor: {$vendor->name}.");

        return redirect()->route('admin.master', ['tab' => 'vendors'])->with('success', "Data Vendor {$vendor->name} berhasil disimpan.");
    }

    // 5. Store / Update User & Role
    public function storeUser(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'username' => 'required|string|max:80',
            'email' => 'required|email|max:150',
            'role' => 'required|in:admin,driver,spv,depthead,vendor,fuelman',
            'jabatan' => 'required|string|max:100',
            'nrp' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|exists:vendors,id',
            'password' => 'nullable|string|min:6',
            'is_active' => 'nullable|boolean',
        ]);

        $existing = User::where('email', $validated['email'])->orWhere('username', $validated['username'])->first();

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'jabatan' => $validated['jabatan'],
            'nrp' => $validated['nrp'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        } elseif (!$existing) {
            $payload['password'] = Hash::make('password123');
        }

        $targetUser = $existing ? tap($existing)->update($payload) : User::create($payload);

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, 'active', "Menyimpan akun pengguna: {$targetUser->username} (Role: {$targetUser->role}).");

        return redirect()->route('admin.master', ['tab' => 'users'])->with('success', "Akun pengguna {$targetUser->name} berhasil disimpan.");
    }

    // 6. Store Area Approver Assignment
    public function storeAreaApprover(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|in:spv,manager,vendor,fuelman',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        AreaApprover::updateOrCreate(
            [
                'area_id' => $validated['area_id'],
                'user_id' => $validated['user_id'],
                'role_type' => $validated['role_type'],
            ],
            [
                'start_date' => $validated['start_date'] ?? now()->toDateString(),
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => true,
            ]
        );

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, 'active', "Menetapkan penugasan Approver ({$validated['role_type']}) untuk Area ID {$validated['area_id']}.");

        return redirect()->route('admin.master', ['tab' => 'approvers'])->with('success', 'Penugasan Approver Area berhasil disimpan.');
    }

    // 7. Store New Fuel Standard (Preserves historical standards!)
    public function storeFuelStandard(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'standard_type' => 'required|in:km_per_liter,liter_per_hour',
            'standard_value' => 'required|numeric|gt:0',
            'tolerance_percentage' => 'required|numeric|min:0|max:100',
            'effective_start' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Close previous active standard without overwriting it (preserves history)
        FuelStandard::where('vehicle_id', $validated['vehicle_id'])
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'effective_end' => $validated['effective_start'],
            ]);

        $std = FuelStandard::create([
            'vehicle_id' => $validated['vehicle_id'],
            'standard_type' => $validated['standard_type'],
            'standard_value' => $validated['standard_value'],
            'tolerance_percentage' => $validated['tolerance_percentage'],
            'effective_start' => $validated['effective_start'],
            'is_active' => true,
            'notes' => $validated['notes'],
        ]);

        FuelRequestHistory::record(null, $admin->id, 'standard_updated', null, 'active', "Menambahkan standar konsumsi BBM baru untuk Vehicle ID {$std->vehicle_id}: {$std->standard_value} ({$std->standard_type}).");

        return redirect()->route('admin.master', ['tab' => 'standards'])->with('success', 'Standar konsumsi BBM berhasil ditambahkan (riwayat standar lama tetap tersimpan).');
    }

    // 8. Store New Fuel Price (Preserves historical prices!)
    public function storeFuelPrice(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'fuel_type' => 'required|string|max:80',
            'price_per_liter' => 'required|numeric|gt:0',
            'effective_start' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        FuelPrice::where('is_active', true)->update([
            'is_active' => false,
            'effective_end' => $validated['effective_start'],
        ]);

        $price = FuelPrice::create([
            'fuel_type' => $validated['fuel_type'],
            'price_per_liter' => $validated['price_per_liter'],
            'effective_start' => $validated['effective_start'],
            'is_active' => true,
            'notes' => $validated['notes'],
        ]);

        FuelRequestHistory::record(null, $admin->id, 'price_updated', null, 'active', "Memperbarui harga BBM ({$price->fuel_type}): Rp " . number_format($price->price_per_liter, 0, ',', '.') . "/Liter mulai {$price->effective_start->format('d/m/Y')}.");

        return redirect()->route('admin.master', ['tab' => 'prices'])->with('success', 'Harga BBM baru berhasil disimpan (riwayat harga lama tetap tersimpan).');
    }
}

