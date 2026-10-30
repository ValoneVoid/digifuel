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
}

