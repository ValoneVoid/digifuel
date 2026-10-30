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
        $backgrounds = Schema::hasTable('system_backgrounds')
            ? SystemBackground::with('uploader')->orderBy('is_active', 'desc')->orderBy('id', 'desc')->get()
            : collect();
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
            'backgrounds',
            'auditLogs'
        ));
    }

    // 1. Store / Update Employee
    public function storeEmployee(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:employees,id',
            'nrp' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'company' => 'required|string|max:150',
            'source' => 'nullable|in:local,digisim',
            'external_reference' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'nrp' => $validated['nrp'],
            'name' => $validated['name'],
            'position' => $validated['position'] ?? 'Driver / Operator',
            'department' => $validated['department'] ?? 'Operation',
            'company' => $validated['company'],
            'source' => $validated['source'] ?? 'local',
            'external_system' => ($validated['source'] ?? 'local') === 'digisim' ? 'DIGISIM' : null,
            'external_reference' => $validated['external_reference'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        if (!empty($validated['id'])) {
            $emp = Employee::findOrFail($validated['id']);
            $emp->update($payload);
        } else {
            $emp = Employee::updateOrCreate(['nrp' => $validated['nrp']], $payload);
        }

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Employee: {$emp->nrp} - {$emp->name}.");

        return redirect()->route('admin.master', ['tab' => 'employees'])->with('success', "Data Employee {$emp->name} ({$emp->nrp}) berhasil disimpan.");
    }

    // 2. Store / Update Unit (Vehicle)
    public function storeVehicle(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:vehicles,id',
            'unit_code' => 'required|string|max:80',
            'police_no' => 'required|string|max:50',
            'model' => 'nullable|string|max:120',
            'unit_type' => 'nullable|string|max:80',
            'meter_type' => 'required|in:km,hm',
            'tank_capacity' => 'nullable|numeric|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'unit_code' => $validated['unit_code'],
            'police_no' => $validated['police_no'],
            'model' => $validated['model'] ?? $validated['unit_type'],
            'unit_type' => $validated['unit_type'] ?? 'Light Vehicle',
            'meter_type' => $validated['meter_type'],
            'tank_capacity' => $validated['tank_capacity'] ?? 80,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        if (!empty($validated['id'])) {
            $veh = Vehicle::findOrFail($validated['id']);
            $veh->update($payload);
        } else {
            $veh = Vehicle::updateOrCreate(['unit_code' => $validated['unit_code']], $payload);
        }

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Unit: {$veh->unit_code} ({$veh->police_no}).");

        return redirect()->route('admin.master', ['tab' => 'units'])->with('success', "Data Unit {$veh->unit_code} berhasil disimpan.");
    }

    // 3. Store / Update Area
    public function storeArea(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:areas,id',
            'code' => 'nullable|string|max:30',
            'name' => 'required|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'name' => $validated['name'],
            'code' => $validated['code'] ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 6)),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        if (!empty($validated['id'])) {
            $area = Area::findOrFail($validated['id']);
            $area->update($payload);
        } else {
            $area = Area::updateOrCreate(['name' => $validated['name']], $payload);
        }

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Area Operasional: {$area->name}.");

        return redirect()->route('admin.master', ['tab' => 'areas'])->with('success', "Area operasional {$area->name} berhasil disimpan.");
    }

    // 4. Store / Update Vendor
    public function storeVendor(Request $request)
    {
        $user = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:vendors,id',
            'code' => 'nullable|string|max:30',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'name' => $validated['name'],
            'code' => $validated['code'] ?: 'VND-' . rand(100, 999),
            'contact_person' => $validated['contact_person'],
            'phone' => $validated['phone'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        if (!empty($validated['id'])) {
            $vendor = Vendor::findOrFail($validated['id']);
            $vendor->update($payload);
        } else {
            $vendor = Vendor::updateOrCreate(['name' => $validated['name']], $payload);
        }

        FuelRequestHistory::record(null, $user->id, 'master_updated', null, 'active', "Menyimpan data Vendor: {$vendor->name}.");

        return redirect()->route('admin.master', ['tab' => 'vendors'])->with('success', "Data Vendor {$vendor->name} berhasil disimpan.");
    }

    // 5. Store / Update User & Role
    public function storeUser(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:users,id',
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

        $existing = !empty($validated['id'])
            ? User::find($validated['id'])
            : User::where('email', $validated['email'])->orWhere('username', $validated['username'])->first();

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'jabatan' => $validated['jabatan'],
            'nrp' => $validated['nrp'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
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

    // 6. Store / Update Area Approver Assignment
    public function storeAreaApprover(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:area_approvers,id',
            'area_id' => 'required|exists:areas,id',
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|in:spv,manager,vendor,fuelman',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'area_id' => $validated['area_id'],
            'user_id' => $validated['user_id'],
            'role_type' => $validated['role_type'],
            'start_date' => $validated['start_date'] ?? now()->toDateString(),
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        if (!empty($validated['id'])) {
            $aa = AreaApprover::findOrFail($validated['id']);
            $aa->update($payload);
        } else {
            AreaApprover::updateOrCreate(
                [
                    'area_id' => $validated['area_id'],
                    'user_id' => $validated['user_id'],
                    'role_type' => $validated['role_type'],
                ],
                $payload
            );
        }

        FuelRequestHistory::record(null, $admin->id, 'master_updated', null, 'active', "Menetapkan penugasan Approver ({$validated['role_type']}) untuk Area ID {$validated['area_id']}.");

        return redirect()->route('admin.master', ['tab' => 'approvers'])->with('success', 'Penugasan Approver Area berhasil disimpan.');
    }

    // 9. Store / Update System Background (Admin CRUD Background sesuai ketentuan UT)
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

    // 10. Toggle Active / Inactive Status for Any Master Entity
    public function toggleStatus(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'entity' => 'required|in:unit,employee,area,vendor,user,approver,standard,price,background',
            'id' => 'required|integer',
            'tab' => 'required|string',
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
            'unit' => Vehicle::class,
            'employee' => Employee::class,
            'area' => Area::class,
            'vendor' => Vendor::class,
            'user' => User::class,
            'approver' => AreaApprover::class,
            'standard' => FuelStandard::class,
            'price' => FuelPrice::class,
        ];

        $modelClass = $modelMap[$validated['entity']];
        $record = $modelClass::findOrFail($validated['id']);
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
            "Status Master Data ({$validated['entity']}: {$recordLabel}) diubah menjadi {$statusLabel}."
        );

        return redirect()->route('admin.master', ['tab' => $validated['tab']])
            ->with('success', "Status data {$recordLabel} berhasil diubah menjadi {$statusLabel}.");
    }

    // 7. Store / Update Fuel Standard (Preserves historical standards!)
    public function storeFuelStandard(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:fuel_standards,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'standard_type' => 'required|in:km_per_liter,liter_per_hour',
            'standard_value' => 'required|numeric|gt:0',
            'tolerance_percentage' => 'required|numeric|min:0|max:100',
            'effective_start' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['id'])) {
            $std = FuelStandard::findOrFail($validated['id']);
            $std->update([
                'vehicle_id' => $validated['vehicle_id'],
                'standard_type' => $validated['standard_type'],
                'standard_value' => $validated['standard_value'],
                'tolerance_percentage' => $validated['tolerance_percentage'],
                'effective_start' => $validated['effective_start'],
                'notes' => $validated['notes'] ?? null,
            ]);
            FuelRequestHistory::record(null, $admin->id, 'standard_updated', null, 'active', "Memperbarui standar konsumsi BBM ID #{$std->id} untuk Vehicle ID {$std->vehicle_id}: {$std->standard_value} ({$std->standard_type}).");
            return redirect()->route('admin.master', ['tab' => 'standards'])->with('success', 'Data standar konsumsi BBM berhasil diperbarui.');
        }

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
            'notes' => $validated['notes'] ?? null,
        ]);

        FuelRequestHistory::record(null, $admin->id, 'standard_updated', null, 'active', "Menambahkan standar konsumsi BBM baru untuk Vehicle ID {$std->vehicle_id}: {$std->standard_value} ({$std->standard_type}).");

        return redirect()->route('admin.master', ['tab' => 'standards'])->with('success', 'Standar konsumsi BBM berhasil ditambahkan (riwayat standar lama tetap tersimpan).');
    }

    // 8. Store / Update Fuel Price (Preserves historical prices!)
    public function storeFuelPrice(Request $request)
    {
        $admin = $this->getActiveUser();
        $validated = $request->validate([
            'id' => 'nullable|exists:fuel_prices,id',
            'fuel_type' => 'required|string|max:80',
            'price_per_liter' => 'required|numeric|gt:0',
            'effective_start' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['id'])) {
            $price = FuelPrice::findOrFail($validated['id']);
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
}

