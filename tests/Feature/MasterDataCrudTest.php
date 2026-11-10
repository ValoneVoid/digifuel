<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\Employee;
use App\Models\FuelRequest;
use App\Models\FuelStandard;
use App\Models\FuelPrice;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\UnitVendor;
use Database\Seeders\DigiFuelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DigiFuelSeeder::class);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->driver = User::where('username', 'adji')->firstOrFail();
    }

    /**
     * Skenario 1: Admin mengedit Employee berdasarkan ID -> Berhasil
     */
    public function test_admin_can_fetch_and_update_employee_by_id(): void
    {
        $employee = Employee::firstOrFail();
        $originalExternalRef = $employee->external_reference;

        // Fetch edit JSON by ID
        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.employees.edit', $employee->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.nrp', $employee->nrp);

        // Update by ID (keeping same NRP should succeed because unique rule ignores $id)
        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.employees.update', $employee->id), [
                'nrp' => $employee->nrp,
                'name' => 'Ahmad Santoso Updated',
                'position' => 'Senior Dump Truck Operator',
                'department' => 'Mining Operation',
                'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                'is_active' => 1,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'employees']));

        $employee->refresh();
        $this->assertSame('Ahmad Santoso Updated', $employee->name);
        $this->assertSame('Senior Dump Truck Operator', $employee->position);
        $this->assertSame($originalExternalRef, $employee->external_reference);
    }

    /**
     * Skenario 2: Admin mengedit Unit/Kendaraan berdasarkan ID -> Berhasil
     */
    public function test_admin_can_fetch_and_update_vehicle_by_id(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.units.edit', $vehicle->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.id', $vehicle->id)
            ->assertJsonPath('data.unit_code', $vehicle->unit_code);

        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.units.update', $vehicle->id), [
                'unit_code' => $vehicle->unit_code,
                'police_no' => 'DA 9988 UT',
                'model' => 'Toyota Hilux 4x4 Facelift',
                'unit_type' => 'Light Vehicle (LV)',
                'meter_type' => 'km',
                'tank_capacity' => 95,
                'is_active' => 1,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'units']));

        $vehicle->refresh();
        $this->assertSame('DA 9988 UT', $vehicle->police_no);
        $this->assertEquals(95, (float) $vehicle->tank_capacity);
    }

    /**
     * Skenario 3: Admin mengedit Area berdasarkan ID -> Berhasil
     */
    public function test_admin_can_fetch_and_update_area_by_id(): void
    {
        $area = Area::firstOrFail();

        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.areas.edit', $area->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.id', $area->id);

        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.areas.update', $area->id), [
                'code' => 'SDU-MAIN',
                'name' => 'SDU (Sungai Danau Utama)',
                'is_active' => 1,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'areas']));

        $area->refresh();
        $this->assertSame('SDU-MAIN', $area->code);
        $this->assertSame('SDU (Sungai Danau Utama)', $area->name);
    }

    /**
     * Skenario 4: Admin mengedit Vendor berdasarkan ID -> Berhasil
     */
    public function test_admin_can_fetch_and_update_vendor_by_id(): void
    {
        $vendor = Vendor::firstOrFail();

        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.vendors.edit', $vendor->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.id', $vendor->id);

        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.vendors.update', $vendor->id), [
                'code' => $vendor->code,
                'name' => $vendor->name,
                'contact_person' => 'Bpk. Hendra Wijaya',
                'phone' => '08115009988',
                'is_active' => 1,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'vendors']));

        $vendor->refresh();
        $this->assertSame('Bpk. Hendra Wijaya', $vendor->contact_person);
        $this->assertSame('08115009988', $vendor->phone);
    }

    /**
     * Skenario 5 & 6:
     * - Admin mengedit User berdasarkan ID tanpa mengubah password -> Password lama tetap berlaku
     * - Admin mengedit User dengan password baru -> Password berubah dan ter-hash
     */
    public function test_admin_can_update_user_without_and_with_password_change(): void
    {
        $targetUser = User::where('username', 'hamdi')->firstOrFail();
        $oldPasswordHash = $targetUser->password;

        // 1. Verify edit JSON does NOT expose password hash
        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.users.edit', $targetUser->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.id', $targetUser->id)
            ->assertJsonMissingPath('data.password');

        // 2. Update user without providing password -> old password remains valid
        $updateWithoutPass = $this->actingAs($this->admin)
            ->put(route('admin.master.users.update', $targetUser->id), [
                'name' => 'Ahmad Santoso (Updated)',
                'username' => $targetUser->username,
                'email' => $targetUser->email,
                'role' => 'driver',
                'jabatan' => 'Senior Driver LV',
                'nrp' => $targetUser->nrp,
                'password' => '', // Empty password
                'is_active' => 1,
            ]);

        $updateWithoutPass->assertRedirect(route('admin.master', ['tab' => 'users']));
        $targetUser->refresh();
        $this->assertSame('Ahmad Santoso (Updated)', $targetUser->name);
        $this->assertSame($oldPasswordHash, $targetUser->password);
        $this->assertTrue(Hash::check('password123', $targetUser->password));

        // 3. Update user WITH new password and changed role -> password hashed & user_roles pivot synced
        $updateWithPass = $this->actingAs($this->admin)
            ->put(route('admin.master.users.update', $targetUser->id), [
                'name' => 'Ahmad Santoso (SPV Promoted)',
                'username' => $targetUser->username,
                'email' => $targetUser->email,
                'role' => 'spv',
                'jabatan' => 'Supervisor Lapangan',
                'nrp' => $targetUser->nrp,
                'password' => 'NewSecretPass99!',
                'is_active' => 1,
            ]);

        $updateWithPass->assertRedirect(route('admin.master', ['tab' => 'users']));
        $targetUser->refresh();
        $this->assertNotSame($oldPasswordHash, $targetUser->password);
        $this->assertTrue(Hash::check('NewSecretPass99!', $targetUser->password));
        $this->assertTrue($targetUser->hasRole('spv'));
    }

    /**
     * Skenario 7: Admin mengedit Area Approver berdasarkan ID -> Berhasil & validasi role yang tidak cocok ditolak
     */
    public function test_admin_can_update_area_approver_by_id_and_validates_role_compatibility(): void
    {
        $approver = AreaApprover::firstOrFail();

        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.approvers.edit', $approver->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.id', $approver->id);

        // Update valid start_date & end_date
        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.approvers.update', $approver->id), [
                'area_id' => $approver->area_id,
                'user_id' => $approver->user_id,
                'role_type' => $approver->role_type,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_active' => 1,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'approvers']));
        $approver->refresh();
        $this->assertSame('2026-12-31', $approver->end_date->format('Y-m-d'));

        // Reject assigning a Driver user as 'spv' Area Approver
        $invalidRoleResp = $this->actingAs($this->admin)
            ->put(route('admin.master.approvers.update', $approver->id), [
                'area_id' => $approver->area_id,
                'user_id' => $this->driver->id,
                'role_type' => 'spv',
                'start_date' => '2026-01-01',
            ]);
        $invalidRoleResp->assertSessionHasErrors(['user_id']);

        // Reject end_date earlier than start_date
        $invalidDateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.approvers.update', $approver->id), [
                'area_id' => $approver->area_id,
                'user_id' => $approver->user_id,
                'role_type' => $approver->role_type,
                'start_date' => '2026-06-01',
                'end_date' => '2026-01-01',
            ]);
        $invalidDateResp->assertSessionHasErrors(['end_date']);
    }

    /**
     * Skenario 8, 9, 10:
     * - Admin menonaktifkan Employee -> is_active = false
     * - Admin mengaktifkan kembali Employee -> is_active = true
     * - Admin menonaktifkan Unit/Kendaraan -> is_active = false
     */
    public function test_admin_can_toggle_active_status_for_employee_and_unit(): void
    {
        $employee = Employee::where('is_active', true)->firstOrFail();

        // Nonaktifkan Employee
        $this->actingAs($this->admin)
            ->patch(route('admin.master.employees.toggle', $employee->id))
            ->assertRedirect();
        $this->assertFalse($employee->fresh()->is_active);

        // Aktifkan kembali Employee
        $this->actingAs($this->admin)
            ->patch(route('admin.master.employees.toggle', $employee->id))
            ->assertRedirect();
        $this->assertTrue($employee->fresh()->is_active);

        // Nonaktifkan Unit/Kendaraan
        $vehicle = Vehicle::where('is_active', true)->firstOrFail();
        $this->actingAs($this->admin)
            ->patch(route('admin.master.units.toggle', $vehicle->id))
            ->assertRedirect();
        $this->assertFalse($vehicle->fresh()->is_active);
    }

    /**
     * Skenario 11, 12, 13, 14:
     * - Unit nonaktif digunakan untuk Fuel Request baru -> Ditolak
     * - Area nonaktif digunakan untuk Fuel Request baru -> Ditolak
     * - Vendor nonaktif digunakan untuk Fuel Request baru -> Ditolak
     * - Area Approver nonaktif digunakan pada pengajuan baru -> Ditolak
     */
    public function test_inactive_master_data_is_rejected_on_new_fuel_request(): void
    {
        $vehicle = Vehicle::where('is_active', true)->firstOrFail();
        $area = Area::where('code', 'SDU-01')->firstOrFail();
        $vendor = Vendor::where('is_active', true)->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();
        $depthead = User::where('username', 'Pdh-kusan')->firstOrFail();
        $fuelman = User::where('username', 'adya')->firstOrFail();

        $basePayload = [
            'request_date' => now()->toDateTimeString(),
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'total_liters' => 45,
            'hm_km' => 15000,
            'area' => $area->name,
            'spv_id' => $spv->id,
            'depthead_id' => $depthead->id,
            'fuelman_id' => $fuelman->id,
            'vendor_name' => $vendor->name,
            'action_type' => 'submit',
        ];

        // 11. Unit nonaktif -> Ditolak
        $vehicle->update(['is_active' => false]);
        $this->actingAs($this->driver)
            ->post(route('fuel.store'), $basePayload)
            ->assertSessionHasErrors(['unit_code']);
        $vehicle->update(['is_active' => true]);

        // 12. Area nonaktif -> Ditolak
        $area->update(['is_active' => false]);
        $this->actingAs($this->driver)
            ->post(route('fuel.store'), $basePayload)
            ->assertSessionHasErrors(['area']);
        $area->update(['is_active' => true]);

        // 13. Vendor nonaktif -> Ditolak
        $vendor->update(['is_active' => false]);
        $this->actingAs($this->driver)
            ->post(route('fuel.store'), $basePayload)
            ->assertSessionHasErrors(['vendor_name']);
        $vendor->update(['is_active' => true]);

        // 14. Area Approver nonaktif -> Ditolak
        $spvAssignment = AreaApprover::where('area_id', $area->id)
            ->where('user_id', $spv->id)
            ->where('role_type', 'spv')
            ->firstOrFail();
        $spvAssignment->update(['is_active' => false]);

        $this->actingAs($this->driver)
            ->post(route('fuel.store'), $basePayload)
            ->assertSessionHasErrors(['spv_id']);
        $spvAssignment->update(['is_active' => true]);
    }

    /**
     * Skenario 15: Driver mencoba mengedit Master Data -> Akses ditolak (403)
     */
    public function test_driver_cannot_access_or_modify_master_data(): void
    {
        $employee = Employee::firstOrFail();
        $vehicle = Vehicle::firstOrFail();

        $this->actingAs($this->driver)
            ->get(route('admin.master'))
            ->assertStatus(403);

        $this->actingAs($this->driver)
            ->getJson(route('admin.master.employees.edit', $employee->id))
            ->assertStatus(403);

        $this->actingAs($this->driver)
            ->put(route('admin.master.employees.update', $employee->id), [
                'nrp' => $employee->nrp,
                'name' => 'Hacked Name',
                'company' => 'Hacked Corp',
            ])
            ->assertStatus(403);

        $this->actingAs($this->driver)
            ->patch(route('admin.master.units.toggle', $vehicle->id))
            ->assertStatus(403);
    }

    /**
     * Skenario 16: Data dengan unique value yang sama dimasukkan -> Validasi menolak
     */
    public function test_duplicate_unique_values_are_rejected_on_create_and_update(): void
    {
        $existingEmp = Employee::firstOrFail();
        $existingVeh = Vehicle::firstOrFail();
        $existingArea = Area::firstOrFail();
        $existingVendor = Vendor::firstOrFail();
        $existingUser = User::firstOrFail();

        // Duplicate Employee NRP on create
        $this->actingAs($this->admin)
            ->post(route('admin.master.employee'), [
                'nrp' => $existingEmp->nrp,
                'name' => 'Duplicate Employee',
                'company' => 'PT UNITED TRACTORS Tbk',
            ])
            ->assertSessionHasErrors(['nrp']);

        // Duplicate Unit Code on create
        $this->actingAs($this->admin)
            ->post(route('admin.master.vehicle'), [
                'unit_code' => $existingVeh->unit_code,
                'police_no' => 'DA 0000 XX',
                'meter_type' => 'km',
            ])
            ->assertSessionHasErrors(['unit_code']);

        // Duplicate Area Name on create
        $this->actingAs($this->admin)
            ->post(route('admin.master.area'), [
                'name' => $existingArea->name,
                'code' => 'NEWCODE',
            ])
            ->assertSessionHasErrors(['name']);

        // Duplicate Vendor Name on create
        $this->actingAs($this->admin)
            ->post(route('admin.master.vendor'), [
                'name' => $existingVendor->name,
                'code' => 'VND-NEW',
            ])
            ->assertSessionHasErrors(['name']);

        // Duplicate User Username & Email on create
        $this->actingAs($this->admin)
            ->post(route('admin.master.user'), [
                'name' => 'Duplicate User',
                'username' => $existingUser->username,
                'email' => $existingUser->email,
                'role' => 'driver',
                'jabatan' => 'Driver',
            ])
            ->assertSessionHasErrors(['username', 'email']);
    }

    /**
     * Skenario 17 & 18:
     * - ID tidak ditemukan -> 404
     * - Proteksi data historis dari hapus permanen & Soft Delete untuk Employee baru tanpa histori
     */
    public function test_not_found_id_returns_404_and_historical_data_is_protected_from_delete(): void
    {
        // Non-existent ID returns 404 across edit, update, toggle, and delete
        $this->actingAs($this->admin)
            ->getJson(route('admin.master.employees.edit', 999999))
            ->assertStatus(404);

        $this->actingAs($this->admin)
            ->put(route('admin.master.units.update', 999999), [
                'unit_code' => 'LV-NONE',
                'police_no' => 'DA 1234 XX',
                'meter_type' => 'km',
            ])
            ->assertStatus(404);

        $this->actingAs($this->admin)
            ->patch(route('admin.master.areas.toggle', 999999))
            ->assertStatus(404);

        // Historical Vehicle cannot be deleted permanently
        $usedVehicleId = FuelRequest::whereNotNull('vehicle_id')->value('vehicle_id');
        if ($usedVehicleId) {
            $this->actingAs($this->admin)
                ->deleteJson(route('admin.master.units.destroy', $usedVehicleId))
                ->assertStatus(422);
            $this->assertDatabaseHas('vehicles', ['id' => $usedVehicleId]);
        }

        // Unused new Employee can be Soft Deleted safely
        $tempEmp = Employee::create([
            'nrp' => 'NRP-TEMP-999',
            'name' => 'Temporary Employee',
            'company' => 'PT UNITED TRACTORS Tbk',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.master.employees.destroy', $tempEmp->id))
            ->assertRedirect(route('admin.master', ['tab' => 'employees']));

        $this->assertSoftDeleted('employees', ['id' => $tempEmp->id]);

        // Two-Step Verification test: wrong keyword is rejected
        $tempArea = Area::create([
            'code' => 'AREA-DEL',
            'name' => 'Area Uji Hapus 2 Langkah',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.master.areas.destroy', $tempArea->id), [
                'verification_step' => '2',
                'confirm_keyword' => 'BATAL',
            ])
            ->assertSessionHasErrors(['confirm_keyword']);
        $this->assertDatabaseHas('areas', ['id' => $tempArea->id]);

        // Two-Step Verification test: step 2 + 'HAPUS' keyword succeeds
        $this->actingAs($this->admin)
            ->delete(route('admin.master.areas.destroy', $tempArea->id), [
                'verification_step' => '2',
                'confirm_keyword' => 'HAPUS',
            ])
            ->assertRedirect(route('admin.master', ['tab' => 'areas']));
        $this->assertDatabaseMissing('areas', ['id' => $tempArea->id]);
    }

    /**
     * Skenario 19: Pengujian Lengkap Fitur Standar Konsumsi BBM (CRUD, Historical Versioning, & Evaluasi)
     */
    public function test_fuel_standard_crud_historical_versioning_and_evaluation(): void
    {
        // 1. Admin dapat melihat daftar standar BBM
        $indexResp = $this->actingAs($this->admin)
            ->get(route('admin.master', ['tab' => 'standards']));
        $indexResp->assertStatus(200);

        // 2. Admin dapat menambahkan standar untuk unit baru
        $newVehicle = Vehicle::create([
            'unit_code' => 'LV-TEST-99',
            'police_no' => 'DA 1999 UT',
            'model' => 'Toyota Hilux 4x4 Double Cabin',
            'unit_type' => 'Light Vehicle',
            'meter_type' => 'km',
            'tank_capacity' => 80,
            'is_active' => true,
        ]);

        $createResp = $this->actingAs($this->admin)
            ->post(route('admin.master.standard'), [
                'vehicle_id' => $newVehicle->id,
                'standard_value' => 8.5,
                'effective_start' => '2026-01-01',
                // standard_type and tolerance_percentage omitted to test auto-detection & default tolerance
            ]);

        $createResp->assertRedirect(route('admin.master', ['tab' => 'standards']));

        $std1 = FuelStandard::where('vehicle_id', $newVehicle->id)->firstOrFail();
        $this->assertSame('km_per_liter', $std1->standard_type);
        $this->assertEquals(8.5, (float) $std1->standard_value);
        $this->assertEquals(5.0, (float) $std1->tolerance_percentage);
        $this->assertTrue($std1->is_active);
        $this->assertNull($std1->effective_end);

        // 3. Admin dapat mengedit standar berdasarkan ID
        $updateResp = $this->actingAs($this->admin)
            ->post(route('admin.master.standard'), [
                'id' => $std1->id,
                'vehicle_id' => $newVehicle->id,
                'standard_value' => 8.2,
                'effective_start' => '2026-01-01',
                'notes' => 'Koreksi salah ketik target rasio',
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'standards']));
        $std1->refresh();
        $this->assertEquals(8.2, (float) $std1->standard_value);
        $this->assertSame('Koreksi salah ketik target rasio', $std1->notes);

        // 4. Tambah Standar Baru -> Standar lama otomatis ditutup (Historical Versioning)
        $newStandardResp = $this->actingAs($this->admin)
            ->post(route('admin.master.standard'), [
                'vehicle_id' => $newVehicle->id,
                'standard_value' => 7.8,
                'effective_start' => '2026-11-01',
            ]);

        $newStandardResp->assertRedirect(route('admin.master', ['tab' => 'standards']));

        $std1->refresh();
        $this->assertFalse($std1->is_active);
        $this->assertSame('2026-11-01', $std1->effective_end->format('Y-m-d'));

        $std2 = FuelStandard::where('vehicle_id', $newVehicle->id)->where('is_active', true)->firstOrFail();
        $this->assertEquals(7.8, (float) $std2->standard_value);
        $this->assertSame('2026-11-01', $std2->effective_start->format('Y-m-d'));
        $this->assertNull($std2->effective_end);

        // 5. Admin dapat menonaktifkan dan mengaktifkan kembali standar
        $this->actingAs($this->admin)
            ->post(route('admin.master.toggle'), [
                'entity' => 'standard',
                'id' => $std2->id,
                'tab' => 'standards',
            ])->assertRedirect();
        $this->assertFalse($std2->fresh()->is_active);

        $this->actingAs($this->admin)
            ->post(route('admin.master.toggle'), [
                'entity' => 'standard',
                'id' => $std2->id,
                'tab' => 'standards',
            ])->assertRedirect();
        $this->assertTrue($std2->fresh()->is_active);

        // 6. Unit nonaktif tidak dapat diberi standar baru
        $newVehicle->update(['is_active' => false]);
        $this->actingAs($this->admin)
            ->post(route('admin.master.standard'), [
                'vehicle_id' => $newVehicle->id,
                'standard_value' => 7.0,
                'effective_start' => '2026-12-01',
            ])
            ->assertSessionHasErrors(['vehicle_id']);
        $newVehicle->update(['is_active' => true]);

        // 7. Pengujian Rumus Evaluasi BBM: KM/L (Batas Tetap 0.5 Km/L) & Snapshot Evaluasi
        $evalService = app(\App\Services\FuelEvaluationService::class);
        $evalResult = $evalService->evaluateVehicle(
            $newVehicle,
            '2026-11',
            $this->admin->id,
            10000.0, // Meter Start
            10780.0, // Meter End (Jarak 780 KM)
            110.0    // Total Aktual Liter (Rasio Aktual: 780 / 110 = 7.09 KM/L)
        );

        // Standar 7.8, Rasio Aktual 7.09 < (7.8 - 0.5 = 7.3) -> Harus 'needs_review'
        $this->assertSame('needs_review', $evalResult->evaluation_status);
        $this->assertSame('km_per_liter', $evalResult->standard_type);
        $this->assertEquals(7.8, (float) $evalResult->standard_used);
        $this->assertNotNull($evalResult->evaluation_no);

        // 8. Hapus Standar dengan Verifikasi 2 Langkah
        $this->actingAs($this->admin)
            ->delete(route('admin.master.standards.destroy', $std1->id), [
                'verification_step' => '2',
                'confirm_keyword' => 'HAPUS',
            ])
            ->assertRedirect(route('admin.master', ['tab' => 'standards']));

        $this->assertDatabaseMissing('fuel_standards', ['id' => $std1->id]);
    }

    /**
     * Skenario 13: Paginasi dan Limit/Show Per Page pada Master Data
     */
    public function test_admin_master_page_supports_pagination_and_custom_per_page_limit(): void
    {
        // Pastikan ada lebih dari 15 data vehicle untuk menguji paginasi
        $existingCount = Vehicle::count();
        for ($i = $existingCount + 1; $i <= 18; $i++) {
            Vehicle::create([
                'unit_code' => sprintf('TEST-%03d', $i),
                'police_no' => sprintf('KT %04d Z', $i),
                'unit_type' => 'Light Vehicle',
                'model' => 'Toyota Hilux 4x4',
                'meter_type' => 'km',
                'tank_capacity' => 80,
                'is_active' => true,
            ]);
        }

        // 1. Request dengan per_page default 10
        $response10 = $this->actingAs($this->admin)
            ->get(route('admin.master', ['tab' => 'units', 'per_page' => 10]));

        $response10->assertStatus(200);
        $response10->assertViewHas('vehicles');
        $response10->assertViewHas('perPage', 10);
        $response10->assertViewHas('allVehicles');

        $paginator10 = $response10->viewData('vehicles');
        $this->assertEquals(10, $paginator10->perPage());
        $this->assertEquals(10, $paginator10->count());
        $this->assertGreaterThanOrEqual(18, $paginator10->total());
        $this->assertEquals(1, $paginator10->currentPage());

        // Verifikasi dropdown Limit per page menampilkan value 10 selected
        $response10->assertSee('value="10" selected', false);
        $response10->assertSee('Menampilkan <strong>1</strong> - <strong>10</strong> dari total', false);

        // 2. Request dengan per_page 25
        $response25 = $this->actingAs($this->admin)
            ->get(route('admin.master', ['tab' => 'units', 'per_page' => 25]));

        $response25->assertStatus(200);
        $response25->assertViewHas('perPage', 25);
        $paginator25 = $response25->viewData('vehicles');
        $this->assertEquals(25, $paginator25->perPage());
        $this->assertGreaterThanOrEqual(18, $paginator25->count());
        $response25->assertSee('value="25" selected', false);

        // 3. Request page 2 dengan per_page 10
        $responsePage2 = $this->actingAs($this->admin)
            ->get(route('admin.master', ['tab' => 'units', 'per_page' => 10, 'page' => 2]));

        $responsePage2->assertStatus(200);
        $paginatorPage2 = $responsePage2->viewData('vehicles');
        $this->assertEquals(2, $paginatorPage2->currentPage());
        $this->assertEquals(11, $paginatorPage2->firstItem());
        $responsePage2->assertSee('Menampilkan <strong>11</strong> -', false);
    }

    /**
     * Test Vendor BBM memiliki relasi 1-M dengan FuelPrice,
     * dan Admin dapat mengelola harga BBM per Vendor.
     */
    public function test_vendor_has_many_fuel_prices_and_admin_can_manage_prices_per_vendor(): void
    {
        $vendor = Vendor::where('is_active', true)->firstOrFail();

        // 1. Simpan harga BBM baru untuk vendor ini
        $response = $this->actingAs($this->admin)
            ->post(route('admin.master.price'), [
                'vendor_id' => $vendor->id,
                'fuel_type' => 'Solar Industri B35',
                'price_per_liter' => 28500,
                'effective_start' => now()->toDateString(),
                'notes' => 'Penyesuaian tarif kontrak baru',
            ]);

        $response->assertRedirect(route('admin.master', ['tab' => 'prices']));
        $response->assertSessionHas('success');

        // Verifikasi di database
        $this->assertDatabaseHas('fuel_prices', [
            'vendor_id' => $vendor->id,
            'fuel_type' => 'Solar Industri B35',
            'price_per_liter' => 28500,
            'is_active' => true,
        ]);

        // 2. Verifikasi relasi 1-M di Model
        $vendorPrices = $vendor->fresh()->fuelPrices;
        $this->assertGreaterThanOrEqual(1, $vendorPrices->count());
        $this->assertEquals($vendor->id, $vendorPrices->first()->vendor_id);
        $this->assertEquals($vendor->name, $vendorPrices->first()->vendor->name);

        // 3. Verifikasi getCurrentPrice per vendor
        $currentPrice = FuelPrice::getCurrentPrice(now()->toDateString(), $vendor->id);
        $this->assertNotNull($currentPrice);
        $this->assertEquals(28500, (float)$currentPrice->price_per_liter);
    }

    /**
     * Test UnitVendor memiliki relasi 1-M dengan Vehicle,
     * dan Admin dapat mengelola Vendor Unit serta menugaskannya pada Data Unit.
     */
    public function test_unit_vendor_has_many_vehicles_and_admin_can_manage_unit_vendors(): void
    {
        // 1. Admin membuat Unit Vendor baru
        $response = $this->actingAs($this->admin)
            ->post(route('admin.master.unit-vendor'), [
                'code' => 'UVND-TEST',
                'name' => 'PT Rental Perkasa Mandiri',
                'contact_person' => 'Bpk. Perkasa',
                'phone' => '081299887766',
                'address' => 'Satui Workshop KM 5',
            ]);

        $response->assertRedirect(route('admin.master', ['tab' => 'unit_vendors']));

        $unitVendor = UnitVendor::where('code', 'UVND-TEST')->firstOrFail();
        $this->assertEquals('PT Rental Perkasa Mandiri', $unitVendor->name);

        // 2. Admin membuat Unit baru yang terhubung ke Unit Vendor (1-to-many)
        $vehResponse = $this->actingAs($this->admin)
            ->post(route('admin.master.vehicle'), [
                'unit_code' => 'LV-PERKASA-01',
                'police_no' => 'DA 1234 XY',
                'model' => 'Mitsubishi Triton 4x4',
                'unit_vendor_id' => $unitVendor->id,
                'tank_capacity' => 75,
            ]);

        $vehResponse->assertRedirect(route('admin.master', ['tab' => 'units']));

        $vehicle = Vehicle::where('unit_code', 'LV-PERKASA-01')->firstOrFail();
        $this->assertEquals($unitVendor->id, $vehicle->unit_vendor_id);
        $this->assertEquals('km', $vehicle->meter_type);

        // 3. Verifikasi relasi 1-M
        $this->assertTrue($unitVendor->vehicles->contains($vehicle));
        $this->assertEquals('PT Rental Perkasa Mandiri', $vehicle->unitVendor->name);

        // 4. Admin dapat fetch edit Unit Vendor
        $editResp = $this->actingAs($this->admin)
            ->getJson(route('admin.master.unit-vendors.edit', $unitVendor->id));
        $editResp->assertStatus(200)
            ->assertJsonPath('data.name', 'PT Rental Perkasa Mandiri');

        // 5. Admin dapat update Unit Vendor
        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.unit-vendors.update', $unitVendor->id), [
                'name' => 'PT Rental Perkasa Mandiri Tbk',
                'contact_person' => 'Bpk. Perkasa Utama',
                'phone' => '081299880000',
            ]);
        $updateResp->assertRedirect(route('admin.master', ['tab' => 'unit_vendors']));
        $this->assertEquals('PT Rental Perkasa Mandiri Tbk', $unitVendor->fresh()->name);

        // 6. Admin dapat toggle status Unit Vendor
        $toggleResp = $this->actingAs($this->admin)
            ->patch(route('admin.master.unit-vendors.toggle', $unitVendor->id));
        $toggleResp->assertRedirect();
        $this->assertFalse($unitVendor->fresh()->is_active);

        // 7. View Master Data memuat Unit Vendor tab dan Vendor Unit pada tabel unit
        $pageResp = $this->actingAs($this->admin)
            ->get(route('admin.master', ['tab' => 'units']));
        $pageResp->assertStatus(200)
            ->assertSee('Vendor Unit (Rental / Internal)')
            ->assertSee('PT Rental Perkasa Mandiri Tbk');
    }

    /**
     * Skenario 16: Registrasi Unit Kendaraan langsung mengkonfigurasi Standar Konsumsi BBM (1 Langkah)
     */
    public function test_admin_can_create_and_update_vehicle_with_fuel_standard_in_one_step(): void
    {
        $uv = UnitVendor::firstOrFail();
        $area = Area::firstOrFail();

        // 1. Create Unit with standard_consumption in the same form
        $createResp = $this->actingAs($this->admin)
            ->post(route('admin.master.vehicle'), [
                'unit_code' => 'LV-STREAMLINE-01',
                'police_no' => 'DA 7788 UT',
                'model' => 'Toyota Hilux Single Cabin',
                'unit_vendor_id' => $uv->id,
                'default_area_id' => $area->id,
                'tank_capacity' => 85,
                'standard_consumption' => 8.5,
            ]);

        $createResp->assertRedirect(route('admin.master', ['tab' => 'units']));

        $veh = Vehicle::where('unit_code', 'LV-STREAMLINE-01')->firstOrFail();
        $this->assertEquals(85, (float) $veh->tank_capacity);
        $this->assertEquals($area->id, $veh->default_area_id);
        $this->assertNotNull($veh->activeFuelStandard);
        $this->assertEquals(8.5, (float) $veh->activeFuelStandard->standard_value);

        // 2. Update Unit with revised standard_consumption
        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.units.update', $veh->id), [
                'unit_code' => 'LV-STREAMLINE-01',
                'police_no' => 'DA 7788 UT',
                'model' => 'Toyota Hilux Single Cabin Updated',
                'unit_vendor_id' => $uv->id,
                'default_area_id' => $area->id,
                'tank_capacity' => 90,
                'standard_consumption' => 9.2,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'units']));

        $veh->refresh();
        $this->assertEquals(90, (float) $veh->tank_capacity);
        $this->assertEquals(9.2, (float) $veh->activeFuelStandard->standard_value);
    }

    /**
     * Skenario 17: Registrasi Vendor BBM langsung mengkonfigurasi Harga Solar per Liter (1 Langkah)
     */
    public function test_admin_can_create_and_update_vendor_with_fuel_price_in_one_step(): void
    {
        // 1. Create Vendor with price_per_liter
        $createResp = $this->actingAs($this->admin)
            ->post(route('admin.master.vendor'), [
                'name' => 'PT Solar Energi Prima',
                'contact_person' => 'Bpk. Ridwan Solar',
                'phone' => '081344556677',
                'fuel_type' => 'Solar Industri B35',
                'price_per_liter' => 28500,
            ]);

        $createResp->assertRedirect(route('admin.master', ['tab' => 'vendors']));

        $ven = Vendor::where('name', 'PT Solar Energi Prima')->firstOrFail();
        $this->assertNotNull($ven->activeFuelPrice);
        $this->assertEquals(28500, (float) $ven->activeFuelPrice->price_per_liter);

        // 2. Update Vendor with updated price
        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.vendors.update', $ven->id), [
                'name' => 'PT Solar Energi Prima Tbk',
                'contact_person' => 'Bpk. Ridwan Solar',
                'phone' => '081344556677',
                'fuel_type' => 'Solar Industri B35',
                'price_per_liter' => 29000,
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'vendors']));

        $ven->refresh();
        $this->assertEquals('PT Solar Energi Prima Tbk', $ven->name);
        $this->assertEquals(29000, (float) $ven->activeFuelPrice->price_per_liter);
    }

    /**
     * Skenario 18: Registrasi Pengguna sekaligus auto-sync Employee dan Penugasan Area (1 Langkah)
     */
    public function test_admin_can_create_user_with_employee_and_area_assignment_in_one_step(): void
    {
        $areas = Area::take(2)->get();
        $this->assertCount(2, $areas);

        $createResp = $this->actingAs($this->admin)
            ->post(route('admin.master.user'), [
                'name' => 'Doni SPV Satui',
                'username' => 'donispv',
                'email' => 'donispv@example.com',
                'phone' => '081234567899',
                'role' => 'spv',
                'jabatan' => 'Supervisor Mining',
                'nrp' => '80129999',
                'department' => 'Operation',
                'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                'assigned_area_ids' => $areas->pluck('id')->toArray(),
            ]);

        $createResp->assertRedirect(route('admin.master', ['tab' => 'users']));

        $user = User::where('username', 'donispv')->firstOrFail();

        // Verifikasi Employee otomatis dibuat via NRP
        $employee = Employee::where('nrp', '80129999')->firstOrFail();
        $this->assertEquals('Doni SPV Satui', $employee->name);
        $this->assertEquals($employee->id, $user->employee_id);

        // Verifikasi Area Approvers otomatis terbuat untuk 2 area yang dipilih
        $assignedAreaIds = AreaApprover::where('user_id', $user->id)->pluck('area_id')->toArray();
        $this->assertCount(2, $assignedAreaIds);
        $this->assertEquals($areas->pluck('id')->sort()->values()->toArray(), collect($assignedAreaIds)->sort()->values()->toArray());

        // Update penugasan area (misal dikurangi menjadi hanya 1 area)
        $updateResp = $this->actingAs($this->admin)
            ->put(route('admin.master.users.update', $user->id), [
                'name' => 'Doni SPV Satui Updated',
                'username' => 'donispv',
                'email' => 'donispv@example.com',
                'phone' => '081234567899',
                'role' => 'spv',
                'jabatan' => 'Senior Supervisor Mining',
                'nrp' => '80129999',
                'assigned_area_ids' => [$areas->first()->id],
            ]);

        $updateResp->assertRedirect(route('admin.master', ['tab' => 'users']));

        $remainingAreaIds = AreaApprover::where('user_id', $user->id)->pluck('area_id')->toArray();
        $this->assertCount(1, $remainingAreaIds);
        $this->assertEquals([$areas->first()->id], $remainingAreaIds);
    }
}
