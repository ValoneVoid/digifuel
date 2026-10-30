<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\Employee;
use App\Models\FuelRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
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
}
