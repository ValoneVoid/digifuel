<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\FuelEvaluation;
use App\Models\FuelRequest;
use App\Models\FuelTransaction;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use Database\Seeders\DigiFuelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBackendTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $driverAdji;
    protected User $driverRachmat;
    protected User $spvAklis;
    protected User $managerGarius;
    protected User $vendorSudan;
    protected User $fuelmanAdya;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DigiFuelSeeder::class);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->driverAdji = User::where('username', 'adji')->firstOrFail();
        $this->driverRachmat = User::where('username', 'rachmat')->firstOrFail();
        $this->spvAklis = User::where('username', 'aklis')->firstOrFail();
        $this->managerGarius = User::where('username', 'Pdh-kusan')->firstOrFail();
        $this->vendorSudan = User::where('username', 'vendor_sudan')->firstOrFail();
        $this->fuelmanAdya = User::where('username', 'adya')->firstOrFail();
    }

    /**
     * Skenario 1: Guest tidak dapat mengakses Dashboard (Redirect to Login)
     */
    public function test_guest_cannot_access_dashboard_and_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('dashboard.charts'))->assertRedirect(route('login'));
    }

    /**
     * Skenario 2: Admin dapat melihat statistik seluruh Site Satui
     */
    public function test_admin_sees_aggregated_site_wide_statistics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('chartData');
        $response->assertViewHas('recentRequests');
        $response->assertViewHas('areas');

        $stats = $response->viewData('stats');
        $totalInDb = FuelRequest::count();
        $this->assertEquals($totalInDb, $stats['total_requests']);

        $totalLitersInDb = (float) FuelRequest::sum('total_liters');
        $this->assertEquals($totalLitersInDb, (float) $stats['total_requested_liters']);

        $this->assertArrayHasKey('eval_cost_loss', $stats);
        $this->assertArrayHasKey('eval_waste_liters', $stats);
        $this->assertArrayHasKey('active_units', $stats);
        $this->assertArrayHasKey('active_areas', $stats);
        $this->assertArrayHasKey('active_vendors', $stats);
    }

    /**
     * Skenario 3: Driver/Employee hanya melihat data pengajuan miliknya sendiri
     */
    public function test_driver_only_sees_own_requests_and_statistics(): void
    {
        // Hitung pengajuan khusus driver Adji
        $adjiTotalInDb = FuelRequest::where('user_id', $this->driverAdji->id)->count();
        $adjiLitersInDb = (float) FuelRequest::where('user_id', $this->driverAdji->id)->sum('total_liters');

        $response = $this->actingAs($this->driverAdji)->get(route('dashboard'));
        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertEquals($adjiTotalInDb, $stats['total_requests']);
        $this->assertEquals($adjiLitersInDb, (float) $stats['total_requested_liters']);

        // Pastikan tabel recentRequests hanya berisi pengajuan milik Adji
        $recent = $response->viewData('recentRequests');
        foreach ($recent as $req) {
            $this->assertEquals($this->driverAdji->id, $req->user_id);
        }
    }

    /**
     * Skenario 4: SPV hanya melihat data dan antrean tindakan sesuai kewenangannya
     */
    public function test_spv_only_sees_requests_within_assigned_scope(): void
    {
        $response = $this->actingAs($this->spvAklis)->get(route('dashboard'));
        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertIsInt($stats['waiting_my_action']);

        // Verifikasi bahwa recentRequests hanya mencakup yang ditugaskan ke SPV ini atau areanya
        $recent = $response->viewData('recentRequests');
        $spvAreaIds = AreaApprover::where('user_id', $this->spvAklis->id)
            ->where('is_active', true)
            ->where('role_type', 'spv')
            ->pluck('area_id')
            ->all();

        foreach ($recent as $req) {
            $isAssigned = ((int) $req->spv_id === (int) $this->spvAklis->id)
                || (in_array((int) $req->area_id, $spvAreaIds, true));
            $this->assertTrue($isAssigned);
        }
    }

    /**
     * Skenario 5: Dept Head / Manager melihat antrean tahap 2 yang menunggu persetujuannya
     */
    public function test_manager_sees_pending_stage_2_actions(): void
    {
        $response = $this->actingAs($this->managerGarius)->get(route('dashboard'));
        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('waiting_my_action', $stats);
        $this->assertIsInt($stats['waiting_my_action']);
    }

    /**
     * Skenario 6: Vendor hanya melihat data pengajuan yang ditujukan ke perusahaan Vendor miliknya
     */
    public function test_vendor_only_sees_requests_for_their_vendor_company(): void
    {
        $response = $this->actingAs($this->vendorSudan)->get(route('dashboard'));
        $response->assertStatus(200);

        $recent = $response->viewData('recentRequests');
        foreach ($recent as $req) {
            $isVendorMatch = ($req->vendor_id && (int) $req->vendor_id === (int) $this->vendorSudan->vendor_id)
                || (strcasecmp((string)$req->vendor_name, (string)$this->vendorSudan->vendor?->name) === 0)
                || ((int) $req->vendor_user_id === (int) $this->vendorSudan->id);
            $this->assertTrue($isVendorMatch);
        }
    }

    /**
     * Skenario 7: Fuel Man melihat antrean pengisian yang siap direalisasikan
     */
    public function test_fuelman_sees_queue_and_realization_within_authority(): void
    {
        $response = $this->actingAs($this->fuelmanAdya)->get(route('dashboard'));
        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('ready_fueling', $stats);
        $this->assertIsInt($stats['ready_fueling']);
    }

    /**
     * Skenario 8 & 9: Total liter pengajuan dan realisasi tidak tertukar
     */
    public function test_requested_liters_and_actual_liters_are_not_swapped_or_confused(): void
    {
        // Buat pengajuan baru dengan volume berbeda antara requested dan actual
        $vehicle = Vehicle::firstOrFail();
        $area = Area::firstOrFail();
        $vendor = Vendor::firstOrFail();

        $req = FuelRequest::create([
            'request_no' => 'FR-TEST-SWAP-001',
            'user_id' => $this->driverAdji->id,
            'driver_name' => $this->driverAdji->name,
            'request_date' => now(),
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'unit_type' => $vehicle->unit_type ?? 'Light Vehicle',
            'meter_type' => 'km',
            'hm_km' => '12000',
            'total_liters' => 80.0, // Requested
            'actual_liters' => 75.5, // Actual
            'area' => $area->name,
            'area_id' => $area->id,
            'vendor_name' => $vendor->name,
            'vendor_id' => $vendor->id,
            'spv_id' => $this->spvAklis->id,
            'depthead_id' => $this->managerGarius->id,
            'status_spv' => 'disetujui',
            'status_depthead' => 'disetujui',
            'status_vendor' => 'disetujui',
            'status_fuelman' => 'disetujui',
            'overall_status' => 'disetujui',
            'current_stage' => 'completed',
        ]);

        FuelTransaction::create([
            'transaction_no' => 'TRX-TEST-SWAP-001',
            'fuel_request_id' => $req->id,
            'fuelman_id' => $this->fuelmanAdya->id,
            'requested_liters' => 80.0,
            'actual_liters' => 75.5,
            'variance_liters' => -4.5,
            'meter_at_fueling' => 12000,
            'fueled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $stats = $response->viewData('stats');

        $this->assertGreaterThanOrEqual(80.0, (float) $stats['total_requested_liters']);
        $this->assertGreaterThanOrEqual(75.5, (float) $stats['total_actual_liters']);
        // Pastikan tidak tertukar
        $this->assertNotEquals($stats['total_requested_liters'], $stats['total_actual_liters']);
    }

    /**
     * Skenario 10: Grafik dan visualisasi mengembalikan struktur data yang benar untuk Chart.js
     */
    public function test_charts_endpoint_returns_valid_structured_data_for_chartjs(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('dashboard.charts'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'monthly_trend' => [
                    'labels',
                    'requested_liters',
                    'actual_liters',
                    'request_counts',
                ],
                'status_distribution' => [
                    'labels',
                    'series',
                    'colors',
                ],
                'area_distribution' => [
                    'labels',
                    'volumes',
                    'counts',
                ],
                'summary' => [
                    'total_requested',
                    'total_actual',
                    'total_requests',
                ],
            ],
        ]);
    }

    /**
     * Skenario 11 & 12: Dashboard tetap berjalan tanpa error ketika database kosong
     */
    public function test_dashboard_handles_empty_database_without_errors(): void
    {
        // Hapus seluruh data transaksi, pengajuan, dan evaluasi untuk menguji empty state
        FuelTransaction::query()->delete();
        FuelRequest::query()->delete();
        FuelEvaluation::query()->delete();

        $rolesToTest = [
            $this->admin,
            $this->driverAdji,
            $this->spvAklis,
            $this->managerGarius,
            $this->vendorSudan,
            $this->fuelmanAdya,
        ];

        foreach ($rolesToTest as $user) {
            $resp = $this->actingAs($user)->get(route('dashboard'));
            $resp->assertStatus(200);

            $stats = $resp->viewData('stats');
            $this->assertEquals(0, $stats['total_requests']);
            $this->assertEquals(0.0, (float) $stats['total_requested_liters']);
            $this->assertEquals(0.0, (float) $stats['total_actual_liters']);
            $this->assertEquals(0, $stats['waiting_my_action']);

            $chartResp = $this->actingAs($user)->getJson(route('dashboard.charts'));
            $chartResp->assertStatus(200);
            $this->assertTrue($chartResp->json('success'));
        }
    }
}

