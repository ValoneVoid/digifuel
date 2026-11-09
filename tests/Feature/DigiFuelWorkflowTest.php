<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\FuelRequest;
use App\Models\FuelEvaluation;
use App\Services\FuelEvaluationService;
use Database\Seeders\DigiFuelSeeder;

class DigiFuelWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DigiFuelSeeder::class);
    }

    public function test_login_and_role_redirection_works(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $loginResp = $this->post('/login', [
            'login' => 'adji',
            'password' => 'password123',
        ]);
        $loginResp->assertRedirect(route('dashboard'));

        $adminResp = $this->post('/login', [
            'login' => 'admin',
            'password' => 'password123',
        ]);
        $adminResp->assertRedirect(route('dashboard'));
    }

    public function test_ajax_vehicle_lookup_and_area_approvers_work(): void
    {
        $driver = User::where('username', 'adji')->first();
        $this->actingAs($driver);

        $vehResp = $this->getJson('/api/vehicle-lookup?unit_code=' . urlencode('UT 208 DA 8191'));
        $vehResp->assertStatus(200)
                ->assertJson([
                    'found' => true,
                    'police_no' => 'DA 8191',
                ]);

        $areaResp = $this->getJson('/api/area-approvers?area=' . urlencode('SDU (Sungai Danau)'));
        $areaResp->assertStatus(200)
                 ->assertJsonStructure(['area', 'spvs', 'deptheads', 'fuelmen', 'vendors']);
    }

    public function test_complete_sequential_fuel_request_approval_and_realization_workflow(): void
    {
        $driver = User::where('username', 'adji')->first();
        $otherDriver = User::where('username', 'rachmat')->first();
        $spv = User::where('username', 'aklis')->first();
        $depthead = User::where('username', 'Pdh-kusan')->first();
        $vendor = User::where('username', 'vendor_sudan')->first();
        $fuelman = User::where('username', 'adya')->first();

        // 1. Driver creates a Fuel Request
        $this->actingAs($driver);
        $storeResp = $this->post('/fuel', [
            'request_date' => '2026-05-20 08:30:00',
            'unit_code' => 'UT 208 DA 8191',
            'police_no' => 'DA 8191',
            'total_liters' => 45,
            'hm_km' => 106000,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv->id,
            'fuelman_id' => $fuelman->id,
            'depthead_id' => $depthead->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'action_type' => 'submit',
        ]);
        $storeResp->assertRedirect(route('history.index'));

        $fuelRequest = FuelRequest::latest('id')->first();
        $this->assertNotNull($fuelRequest);
        $this->assertEquals('pending_spv', $fuelRequest->current_stage);
        $this->assertEquals('PT UNITED TRACTORS Tbk - SITE SATUI', $fuelRequest->company);

        // IDOR check: Another driver cannot view this driver's document
        $this->actingAs($otherDriver);
        $this->get(route('fuel.document', $fuelRequest->id))->assertStatus(403);

        // 2. Out-of-order check: Dept Head cannot approve before SPV!
        $this->actingAs($depthead);
        $this->post(route('approval.approve', $fuelRequest->id));
        $fuelRequest->refresh();
        $this->assertEquals('pending_spv', $fuelRequest->current_stage);

        // 3. Stage 1: SPV Approves
        $this->actingAs($spv);
        $this->post(route('approval.approve', $fuelRequest->id), ['note' => 'Unit siap beroperasi']);
        $fuelRequest->refresh();
        $this->assertEquals('disetujui', $fuelRequest->status_spv);
        $this->assertEquals('pending_manager', $fuelRequest->current_stage);

        // 4. Out-of-order check: Fuel Man cannot record realization before Vendor approves!
        $this->actingAs($fuelman);
        $this->post(route('approval.realization', $fuelRequest->id), [
            'actual_liters' => 45,
            'meter_at_fueling' => 106005,
            'fueled_at' => '2026-05-20 09:30:00',
        ])->assertSessionHasErrors('status');

        // 5. Stage 2: Dept Head Approves
        $this->actingAs($depthead);
        $this->post(route('approval.approve', $fuelRequest->id), ['note' => 'ACC Dept Head']);
        $fuelRequest->refresh();
        $this->assertEquals('disetujui', $fuelRequest->status_depthead);
        $this->assertEquals('pending_vendor', $fuelRequest->current_stage);

        // 6. Stage 3: Vendor Approves (Final approval before fueling)
        $this->actingAs($vendor);
        $this->post(route('approval.approve', $fuelRequest->id), ['note' => 'Stok BBM tersedia']);
        $fuelRequest->refresh();
        $this->assertEquals('disetujui', $fuelRequest->status_vendor);
        $this->assertEquals('approved', $fuelRequest->current_stage);
        $this->assertTrue($fuelRequest->isVendorApproved());

        // 7. Stage 4: Fuel Man records actual fueling realization
        $this->actingAs($fuelman);
        $this->post(route('approval.realization', $fuelRequest->id), [
            'actual_liters' => 44.5,
            'meter_at_fueling' => 106010,
            'fueled_at' => '2026-05-20 10:00:00',
            'notes' => 'Pengisian selesai di Fuel Station SDU',
        ]);
        $fuelRequest->refresh();
        $this->assertEquals('completed', $fuelRequest->current_stage);
        $this->assertEquals(44.5, (float)$fuelRequest->actual_liters);
        $this->assertEquals(1, $fuelRequest->transactions()->count());
    }

    public function test_fuel_evaluation_calculation_and_invalid_meter_protection(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin);

        $service = app(FuelEvaluationService::class);
        $vehicle = Vehicle::where('unit_code', 'UT 208 DA 8191')->first();

        // Valid evaluation: 1000 -> 1100 KM (100 KM), Standard 8 KM/L (12.5 L), Actual 20 L -> Waste 7.5 L * Rp 27,000 = Rp 202,500
        $eval = $service->evaluateVehicle($vehicle, '2026-06', $admin->id, 1000, 1100, 20);
        $this->assertEquals('needs_review', $eval->evaluation_status);
        $this->assertEquals(100.0, (float)$eval->total_distance_or_hours);
        $this->assertEquals(12.5, (float)$eval->total_standard_liters);
        $this->assertEquals(7.5, (float)$eval->waste_liters);
        $this->assertEquals(202500.0, (float)$eval->estimated_cost_loss);

        // Invalid meter check (meter_end < meter_start): must NOT produce misleading cost loss!
        $invalidEval = $service->evaluateVehicle($vehicle, '2026-07', $admin->id, 2000, 1800, 50);
        $this->assertEquals('invalid_data', $invalidEval->evaluation_status);
        $this->assertEquals(0.0, (float)$invalidEval->estimated_cost_loss);
    }

    public function test_all_main_pages_and_xlsx_exports_render_successfully(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin);

        $this->get('/fuel')->assertStatus(200);
        $this->get('/history')->assertStatus(200);
        $this->get('/approval')->assertStatus(200);
        $this->get('/evaluation')->assertStatus(200);
        $this->get('/admin/master')->assertStatus(200);
        $this->get('/profile')->assertStatus(200);

        $histExport = $this->get('/history/export');
        $histExport->assertStatus(200);

        $evalExport = $this->get('/evaluation/export?period=2026-05');
        $evalExport->assertStatus(200);
    }

    public function test_seeder_is_idempotent_and_foreign_key_relations_are_valid(): void
    {
        $initialCounts = [
            'roles' => \App\Models\Role::count(),
            'areas' => \App\Models\Area::count(),
            'vendors' => \App\Models\Vendor::count(),
            'employees' => \App\Models\Employee::count(),
            'vehicles' => \App\Models\Vehicle::count(),
            'users' => \App\Models\User::count(),
            'user_roles' => \Illuminate\Support\Facades\DB::table('user_roles')->count(),
            'area_approvers' => \App\Models\AreaApprover::count(),
            'fuel_standards' => \App\Models\FuelStandard::count(),
            'fuel_prices' => \App\Models\FuelPrice::count(),
            'fuel_requests' => \App\Models\FuelRequest::count(),
            'fuel_request_approvals' => \App\Models\FuelRequestApproval::count(),
            'fuel_transactions' => \App\Models\FuelTransaction::count(),
            'fuel_evaluations' => \App\Models\FuelEvaluation::count(),
        ];

        // Run DigiFuelSeeder a second time to verify idempotency (no duplicates)
        $this->seed(DigiFuelSeeder::class);

        $this->assertEquals($initialCounts['roles'], \App\Models\Role::count());
        $this->assertEquals($initialCounts['areas'], \App\Models\Area::count());
        $this->assertEquals($initialCounts['vendors'], \App\Models\Vendor::count());
        $this->assertEquals($initialCounts['employees'], \App\Models\Employee::count());
        $this->assertEquals($initialCounts['vehicles'], \App\Models\Vehicle::count());
        $this->assertEquals($initialCounts['users'], \App\Models\User::count());
        $this->assertEquals($initialCounts['user_roles'], \Illuminate\Support\Facades\DB::table('user_roles')->count());
        $this->assertEquals($initialCounts['area_approvers'], \App\Models\AreaApprover::count());
        $this->assertEquals($initialCounts['fuel_standards'], \App\Models\FuelStandard::count());
        $this->assertEquals($initialCounts['fuel_prices'], \App\Models\FuelPrice::count());
        $this->assertEquals($initialCounts['fuel_requests'], \App\Models\FuelRequest::count());
        $this->assertEquals($initialCounts['fuel_request_approvals'], \App\Models\FuelRequestApproval::count());
        $this->assertEquals($initialCounts['fuel_transactions'], \App\Models\FuelTransaction::count());
        $this->assertEquals($initialCounts['fuel_evaluations'], \App\Models\FuelEvaluation::count());

        // Verify all AreaApprover rows have valid User and Area foreign keys and matching business roles
        $approvers = \App\Models\AreaApprover::with(['area', 'user'])->get();
        $this->assertGreaterThan(0, $approvers->count());

        foreach ($approvers as $assignment) {
            $this->assertNotNull($assignment->area, "AreaApprover ID {$assignment->id} has missing Area.");
            $this->assertNotNull($assignment->user, "AreaApprover ID {$assignment->id} has missing User.");
            $this->assertTrue($assignment->is_active);

            $roleValid = match ($assignment->role_type) {
                'spv' => $assignment->user->isSpv(),
                'manager' => $assignment->user->isManager(),
                'vendor' => $assignment->user->isVendor(),
                'fuelman' => $assignment->user->isFuelman(),
                default => false,
            };
            $this->assertTrue($roleValid, "User {$assignment->user->username} role does not match {$assignment->role_type}.");
        }
    }

    public function test_guest_is_redirected_to_login_on_all_protected_routes(): void
    {
        $this->assertGuest();

        $this->get('/')->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/fuel')->assertRedirect(route('login'));
        $this->get('/approval')->assertRedirect(route('login'));
        $this->get('/admin/master')->assertRedirect(route('login'));
        $this->get('/evaluation')->assertRedirect(route('login'));
        $this->get('/history')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));

        // Guest cannot bypass login via switch-user endpoint
        $admin = User::where('username', 'admin')->first();
        $this->post('/switch-user', ['user_id' => $admin->id])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_role_middleware_enforces_access_matrix_and_blocks_unauthorized_roles(): void
    {
        $driver = User::where('username', 'adji')->first();
        $spv = User::where('username', 'aklis')->first();
        $depthead = User::where('username', 'Pdh-kusan')->first();
        $vendor = User::where('username', 'vendor_sudan')->first();
        $fuelman = User::where('username', 'adya')->first();
        $admin = User::where('username', 'admin')->first();

        $ownFuelRequest = FuelRequest::where('user_id', $driver->id)->first();
        $otherVendorFuelRequest = FuelRequest::where('request_no', 'FR-2025-03-001')->first(); // CV. Prima Pertama

        // 1. Driver access checks
        $this->actingAs($driver);
        $this->get('/admin/master')->assertStatus(403);
        $this->get('/evaluation')->assertStatus(403);
        $this->get('/approval')->assertStatus(403);
        $this->get('/fuel')->assertStatus(200);
        $this->get('/history')->assertStatus(200);
        $this->get(route('fuel.document', $ownFuelRequest->id))->assertStatus(200);

        // 2. Vendor access checks
        $this->actingAs($vendor);
        $this->get('/admin/master')->assertStatus(403);
        $this->get('/evaluation')->assertStatus(403);
        $this->get('/fuel')->assertStatus(403);
        $this->get('/approval')->assertStatus(200);
        $this->get(route('approval.show', $ownFuelRequest->id))->assertStatus(200); // Cv. Sudan Mandiri
        $this->get(route('approval.show', $otherVendorFuelRequest->id))->assertStatus(403); // CV. Prima Pertama (IDOR blocked)

        // 3. SPV access checks
        $this->actingAs($spv);
        $this->get('/admin/master')->assertStatus(403);
        $this->get('/evaluation')->assertStatus(403);
        $this->get('/fuel')->assertStatus(403);
        $this->get('/approval')->assertStatus(200);
        $this->get(route('approval.show', $ownFuelRequest->id))->assertStatus(200);
        $this->post(route('approval.realization', $ownFuelRequest->id), [])->assertStatus(403);

        // 4. Dept Head / Manager access checks
        $this->actingAs($depthead);
        $this->get('/admin/master')->assertStatus(403);
        $this->get('/evaluation')->assertStatus(403);
        $this->get('/fuel')->assertStatus(403);
        $this->get('/approval')->assertStatus(200);
        $this->get(route('approval.show', $ownFuelRequest->id))->assertStatus(200);

        // 5. Fuel Man access checks
        $this->actingAs($fuelman);
        $this->get('/admin/master')->assertStatus(403);
        $this->get('/evaluation')->assertStatus(403);
        $this->get('/fuel')->assertStatus(403);
        $this->get('/approval')->assertStatus(200);
        $this->get(route('approval.show', $ownFuelRequest->id))->assertStatus(200);

        // 6. Admin access checks
        $this->actingAs($admin);
        $this->get('/admin/master')->assertStatus(200);
        $this->get('/evaluation')->assertStatus(200);
    }

    public function test_authentication_with_username_email_nrp_inactive_user_and_logout(): void
    {
        // Login via Email
        $this->post('/login', [
            'login' => 'adji@satui.unitedtractors.com',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Logout invalidates session
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        // Login via NRP
        $this->post('/login', [
            'login' => '80124024',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->post('/logout');

        // Inactive user cannot login
        $driver = User::where('username', 'adji')->first();
        $driver->update(['is_active' => false]);

        $this->post('/login', [
            'login' => 'adji',
            'password' => 'password123',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_rate_limiting_blocks_brute_force_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'login' => 'bruteforce_target',
                'password' => 'wrong_password',
            ])->assertSessionHasErrors('login');
        }

        // 6th attempt should be blocked by rate limiter
        $response = $this->post('/login', [
            'login' => 'bruteforce_target',
            'password' => 'wrong_password',
        ]);
        $response->assertSessionHasErrors(['login']);
        $this->assertTrue(str_contains(session('errors')->first('login'), 'Terlalu banyak percobaan login'));
    }

    /**
     * Test: Supervisor dapat memeriksa dan mengubah total liter permintaan BBM driver
     */
    public function test_supervisor_can_inspect_and_modify_requested_fuel_liters(): void
    {
        $driver = User::where('username', 'adji')->firstOrFail();
        $otherDriver = User::where('username', 'rachmat')->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();
        $otherSpv = User::where('username', 'eko')->first();
        if (!$otherSpv) {
            $otherSpv = User::create([
                'name' => 'Eko SPV Lain',
                'username' => 'eko_spv',
                'email' => 'eko_spv@unitedtractors.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'role' => 'spv',
                'jabatan' => 'Supervisor Mining',
                'is_active' => true,
            ]);
        }
        $depthead = User::where('username', 'Pdh-kusan')->firstOrFail();
        $fuelman = User::where('username', 'adya')->firstOrFail();

        // 1. Driver membuat permintaan BBM 50 Liter
        $this->actingAs($driver);
        $this->post('/fuel', [
            'request_date' => '2026-06-15 09:00:00',
            'unit_code' => 'UT 208 DA 8191',
            'police_no' => 'DA 8191',
            'total_liters' => 50,
            'hm_km' => 108000,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv->id,
            'fuelman_id' => $fuelman->id,
            'depthead_id' => $depthead->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'action_type' => 'submit',
        ])->assertRedirect(route('history.index'));

        $fuelRequest = FuelRequest::latest('id')->firstOrFail();
        $this->assertEquals(50.0, (float) $fuelRequest->total_liters);
        $this->assertEquals('pending_spv', $fuelRequest->current_stage);

        // 2. Supervisor memeriksa detail pengajuan di halaman show
        $this->actingAs($spv);
        $showResp = $this->get(route('approval.show', $fuelRequest->id));
        $showResp->assertStatus(200);
        $showResp->assertSee('Total Liter Diajukan Driver');
        $showResp->assertSee('50 Liter');
        $showResp->assertSee('Ubah Jumlah Liter');

        // 3. Security/Policy: Driver tidak boleh mengubah liter via endpoint SPV (403)
        $this->actingAs($driver);
        $this->post(route('approval.updateLiters', $fuelRequest->id), [
            'total_liters' => 40,
        ])->assertStatus(403);

        // Security/Policy: SPV lain yang tidak ditugaskan tidak boleh mengubah (403)
        $this->actingAs($otherSpv);
        $this->post(route('approval.updateLiters', $fuelRequest->id), [
            'total_liters' => 40,
        ])->assertStatus(403);

        // 4. SPV yang ditugaskan mengubah jumlah liter menjadi 42 Liter dengan alasan
        $this->actingAs($spv);
        $updateResp = $this->post(route('approval.updateLiters', $fuelRequest->id), [
            'total_liters' => 42.5,
            'reason' => 'Disesuaikan dengan estimasi kebutuhan shift siang',
        ]);
        $updateResp->assertRedirect(route('approval.show', $fuelRequest->id));
        $updateResp->assertSessionHas('success');

        $fuelRequest->refresh();
        $this->assertEquals(42.5, (float) $fuelRequest->total_liters);

        // Verifikasi Riwayat tercatat pada Audit Trail
        $history = $fuelRequest->histories()->where('action', 'adjusted_liters')->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('50 L menjadi 42.5 L', $history->note);
        $this->assertStringContainsString('Disesuaikan dengan estimasi kebutuhan shift siang', $history->note);

        // 5. SPV juga dapat menyetujui sekaligus menyesuaikan liter (misal menjadi 40 Liter) saat approve
        $approveResp = $this->post(route('approval.approve', $fuelRequest->id), [
            'total_liters' => 40,
            'note' => 'Disetujui 40 Liter sesuai kuota shift',
        ]);
        $approveResp->assertRedirect(route('approval.show', $fuelRequest->id));
        $approveResp->assertSessionHas('success');

        $fuelRequest->refresh();
        $this->assertEquals(40.0, (float) $fuelRequest->total_liters);
        $this->assertEquals('disetujui', $fuelRequest->status_spv);
        $this->assertEquals('pending_manager', $fuelRequest->current_stage);

        // 6. Setelah SPV selesai menyetujui, liter tidak dapat diubah lagi oleh SPV
        $this->post(route('approval.updateLiters', $fuelRequest->id), [
            'total_liters' => 30,
        ])->assertStatus(403);

        // 7. Dokumen cetak Fuel Request menampilkan total liter baru (40 L)
        $docResp = $this->get(route('fuel.document', $fuelRequest->id));
        $docResp->assertStatus(200);
        $docResp->assertSee('40 Liter');
    }

    /**
     * Test: Driver dapat melihat/membuka detail/approval (tombol ungu) miliknya sendiri,
     * tetapi tidak bisa membuka milik driver lain (IDOR) dan tidak bisa melakukan approve/reject/update.
     */
    public function test_driver_can_view_own_fuel_request_detail_and_cannot_view_others_or_perform_approval(): void
    {
        $driverAdji = User::where('username', 'adji')->firstOrFail();
        $driverRachmat = User::where('username', 'rachmat')->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();
        $depthead = User::where('username', 'Pdh-kusan')->firstOrFail();
        $fuelman = User::where('username', 'adya')->firstOrFail();

        // 1. Driver Adji membuat pengajuan
        $this->actingAs($driverAdji);
        $createResp = $this->post('/fuel', [
            'request_date' => '2026-07-01 10:00:00',
            'unit_code' => 'UT 208 DA 8191',
            'police_no' => 'DA 8191',
            'total_liters' => 35,
            'hm_km' => 110000,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv->id,
            'fuelman_id' => $fuelman->id,
            'depthead_id' => $depthead->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'action_type' => 'submit',
        ]);
        $createResp->assertRedirect(route('history.index'));

        $adjiRequest = FuelRequest::latest('id')->firstOrFail();
        $this->assertEquals($driverAdji->id, $adjiRequest->user_id);

        // 2. Driver Adji dapat membuka link tombol ungu (approval.show) untuk pengajuan miliknya sendiri
        $adjiViewResp = $this->actingAs($driverAdji)->get(route('approval.show', $adjiRequest->id));
        $adjiViewResp->assertStatus(200);
        $adjiViewResp->assertSee($adjiRequest->request_no);
        $adjiViewResp->assertSee('Isi Form Request');
        $adjiViewResp->assertSee('35 Liter');
        // Pastikan driver TIDAK melihat tombol Setuju atau Tolak
        $adjiViewResp->assertDontSee('Setuju (Tahap 1');
        $adjiViewResp->assertDontSee('id="approveModal"', false);
        $adjiViewResp->assertDontSee('id="rejectModal"', false);
        $adjiViewResp->assertDontSee('Ubah Jumlah Liter');

        // 3. Driver Rachmat TIDAK DAPAT membuka pengajuan milik Adji (IDOR Prevention -> 403 Forbidden)
        $this->actingAs($driverRachmat);
        $rachmatBlockedResp = $this->get(route('approval.show', $adjiRequest->id));
        $rachmatBlockedResp->assertStatus(403);

        // 4. Driver TIDAK DAPAT mengakses daftar antrean approval (/approval -> 403 Forbidden)
        $this->actingAs($driverAdji);
        $this->get(route('approval.index'))->assertStatus(403);

        // 5. Driver TIDAK DAPAT menyetujui, menolak, atau mengubah liter pengajuan (403 Forbidden)
        $this->post(route('approval.approve', $adjiRequest->id), ['note' => 'Coba approve'])->assertStatus(403);
        $this->post(route('approval.reject', $adjiRequest->id), ['note' => 'Coba tolak'])->assertStatus(403);
        $this->post(route('approval.updateLiters', $adjiRequest->id), ['total_liters' => 20])->assertStatus(403);
    }

    /**
     * Test: Aturan Penghapusan Data Pengajuan BBM Coba-coba / Salah Input
     */
    public function test_fuel_request_deletion_rules_for_trial_data_and_protection(): void
    {
        $driverAdji = User::where('username', 'adji')->firstOrFail();
        $driverRachmat = User::where('username', 'rachmat')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();
        $vehicle = Vehicle::where('is_active', true)->firstOrFail();

        // 1. Driver Adji membuat pengajuan coba-coba (pending SPV)
        $this->actingAs($driverAdji);
        $trialReq = FuelRequest::create([
            'request_no' => 'FR-TEST-DELETE-001',
            'user_id' => $driverAdji->id,
            'driver_name' => $driverAdji->name,
            'vehicle_id' => $vehicle->id,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'area' => 'SDU (Sungai Danau)',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'total_liters' => 25.0,
            'request_date' => now(),
            'current_stage' => 'pending_spv',
            'overall_status' => 'menunggu',
            'status_spv' => 'menunggu',
        ]);

        // 2. Driver Rachmat TIDAK BISA menghapus pengajuan milik Adji (403 IDOR prevention)
        $this->actingAs($driverRachmat);
        $this->delete(route('fuel.destroy', $trialReq->id))->assertStatus(403);
        $this->assertDatabaseHas('fuel_requests', ['id' => $trialReq->id]);

        // 3. Verifikasi 2 Langkah: Keyword salah ditolak, keyword 'HAPUS' berhasil
        $this->actingAs($driverAdji);
        $this->delete(route('fuel.destroy', $trialReq->id), [
            'verification_step' => '2',
            'confirm_keyword' => 'SALAH',
        ])->assertSessionHas('error');
        $this->assertDatabaseHas('fuel_requests', ['id' => $trialReq->id]);

        $delResp = $this->delete(route('fuel.destroy', $trialReq->id), [
            'verification_step' => '2',
            'confirm_keyword' => 'HAPUS',
        ]);
        $delResp->assertRedirect(route('history.index'));
        $this->assertDatabaseMissing('fuel_requests', ['id' => $trialReq->id]);

        // 4. Admin BISA menghapus pengajuan coba-coba tanpa transaksi
        $trialReqAdmin = FuelRequest::create([
            'request_no' => 'FR-TEST-DELETE-002',
            'user_id' => $driverRachmat->id,
            'driver_name' => $driverRachmat->name,
            'vehicle_id' => $vehicle->id,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'area' => 'SDU (Sungai Danau)',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'total_liters' => 50.0,
            'request_date' => now(),
            'current_stage' => 'pending_spv',
            'overall_status' => 'menunggu',
            'status_spv' => 'menunggu',
        ]);

        $this->actingAs($admin);
        $this->delete(route('fuel.destroy', $trialReqAdmin->id))->assertRedirect(route('history.index'));
        $this->assertDatabaseMissing('fuel_requests', ['id' => $trialReqAdmin->id]);

        // 5. Pengajuan yang SUDAH memiliki transaksi pengisian fisik DILARANG DIHAPUS oleh siapapun (termasuk Admin)
        $completedReq = FuelRequest::where('current_stage', 'completed')->first();
        if ($completedReq) {
            $this->actingAs($admin);
            $this->delete(route('fuel.destroy', $completedReq->id))->assertStatus(403);
            $this->assertDatabaseHas('fuel_requests', ['id' => $completedReq->id]);

            $this->actingAs($driverAdji);
            $this->delete(route('fuel.destroy', $completedReq->id))->assertStatus(403);
            $this->assertDatabaseHas('fuel_requests', ['id' => $completedReq->id]);
        }
    }
}

