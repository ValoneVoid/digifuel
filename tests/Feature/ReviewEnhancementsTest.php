<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\FuelEvaluation;
use App\Models\FuelRequest;
use App\Models\FuelStandard;
use App\Models\FuelTransaction;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FonnteWhatsappService;
use App\Services\FuelEvaluationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReviewEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_fuel_form_has_empty_defaults_and_shows_unit_vendor_and_spbu_visibility(): void
    {
        $driver = User::where('username', 'adji')->firstOrFail();
        $depthead = User::where('username', 'Pdh-kusan')->firstOrFail();

        // Driver: empty KM & Area defaults, sees Vendor Rental Unit field, does NOT see SPBU option
        $driverResp = $this->actingAs($driver)->get(route('fuel.create'));
        $driverResp->assertStatus(200);
        $driverResp->assertDontSee('value="79"', false);
        $driverResp->assertSee('Vendor Rental Unit / LV');
        $driverResp->assertDontSee('value="SPBU"', false);
        $driverResp->assertDontSee('id="spbuPanel"', false);

        // Dept Head: sees SPBU option & conditional panel
        $dhResp = $this->actingAs($depthead)->get(route('fuel.create'));
        $dhResp->assertStatus(200);
        $dhResp->assertSee('value="SPBU"', false);
        $dhResp->assertSee('id="spbuPanel"', false);
    }

    public function test_spbu_feature_is_restricted_to_depthead_and_performs_full_bypass(): void
    {
        $driver = User::where('username', 'adji')->firstOrFail();
        $depthead = User::where('username', 'Pdh-kusan')->firstOrFail();
        $vehicle = Vehicle::where('is_active', true)->firstOrFail();
        $area = Area::where('is_active', true)->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();

        // Create a real small PNG in memory for upload
        $tmpImg = tempnam(sys_get_temp_dir(), 'spbu_test_') . '.png';
        $im = imagecreatetruecolor(120, 120);
        imagefill($im, 0, 0, imagecolorallocate($im, 240, 240, 240));
        imagepng($im, $tmpImg);
        imagedestroy($im);
        $fakeReceipt = new UploadedFile($tmpImg, 'nota_spbu.png', 'image/png', null, true);

        // 1. Driver MUST be rejected if trying to submit SPBU
        $this->actingAs($driver)->post(route('fuel.store'), [
            'request_date' => '2026-11-24 10:00:00',
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'hm_km' => 15000,
            'total_liters' => 40,
            'area' => $area->name,
            'spv_id' => $spv->id,
            'depthead_id' => $depthead->id,
            'vendor_name' => 'SPBU',
            'spbu_location' => 'SPBU KM 171 Satui',
            'spbu_price_per_liter' => 14500,
            'spbu_receipt_photo' => $fakeReceipt,
            'action_type' => 'submit',
        ])->assertSessionHasErrors('vendor_name');

        // 2. Dept Head submits SPBU -> Full Bypass Approval (completed immediately + auto FuelTransaction)
        $fakeReceipt2 = new UploadedFile($tmpImg, 'nota_spbu.png', 'image/png', null, true);
        $resp = $this->actingAs($depthead)->post(route('fuel.store'), [
            'request_date' => '2026-11-24 10:30:00',
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'hm_km' => 15050,
            'total_liters' => 45,
            'area' => $area->name,
            'vendor_name' => 'SPBU',
            'spbu_location' => 'SPBU Pertamina Satui KM 171',
            'spbu_price_per_liter' => 14500,
            'spbu_receipt_photo' => $fakeReceipt2,
            'action_type' => 'submit',
        ]);
        $resp->assertRedirect(route('history.index'));

        $spbuReq = FuelRequest::latest('id')->firstOrFail();
        $this->assertTrue($spbuReq->isSpbu());
        $this->assertEquals('completed', $spbuReq->current_stage);
        $this->assertEquals('disetujui', $spbuReq->overall_status);
        $this->assertEquals('disetujui', $spbuReq->status_spv);
        $this->assertEquals('disetujui', $spbuReq->status_depthead);
        $this->assertEquals('disetujui', $spbuReq->status_vendor);
        $this->assertEquals('SPBU Pertamina Satui KM 171', $spbuReq->spbu_location);
        $this->assertEquals(14500.0, (float)$spbuReq->spbu_price_per_liter);
        $this->assertNotNull($spbuReq->spbu_receipt_photo);
        $this->assertEquals(1, $spbuReq->transactions()->count());

        // Receipt endpoint is accessible to authorized viewer
        $this->actingAs($depthead)->get(route('fuel.spbu.receipt', $spbuReq->id))->assertStatus(200);

        @unlink($tmpImg);
    }

    public function test_depthead_can_edit_liters_before_and_during_stage2_approval(): void
    {
        $driver = User::where('username', 'adji')->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();
        $depthead = User::where('username', 'Pdh-kusan')->firstOrFail();
        $vehicle = Vehicle::where('is_active', true)->firstOrFail();

        $req = FuelRequest::create([
            'request_no' => 'FR-TEST-DH-LITERS',
            'user_id' => $driver->id,
            'driver_name' => $driver->name,
            'vehicle_id' => $vehicle->id,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'hm_km' => '12000',
            'total_liters' => 60,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv->id,
            'depthead_id' => $depthead->id,
            'manager_id' => $depthead->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'request_date' => now(),
            'status_spv' => 'disetujui',
            'status_depthead' => 'menunggu',
            'status_manager' => 'menunggu',
            'current_stage' => 'pending_manager',
            'overall_status' => 'menunggu',
        ]);

        // 1. Dept Head updates liters via updateLiters endpoint
        $this->actingAs($depthead)->post(route('approval.updateLiters', $req->id), [
            'total_liters' => 50,
            'reason' => 'Disesuaikan rute operasional',
        ])->assertRedirect(route('approval.show', $req->id));

        $req->refresh();
        $this->assertEquals(50.0, (float)$req->total_liters);

        // 2. Dept Head can also adjust liters inline during Approve
        $this->actingAs($depthead)->post(route('approval.approve', $req->id), [
            'total_liters' => 45,
            'note' => 'ACC Dept Head 45 L',
        ])->assertRedirect(route('approval.show', $req->id));

        $req->refresh();
        $this->assertEquals(45.0, (float)$req->total_liters);
        $this->assertEquals('disetujui', $req->status_depthead);
        $this->assertEquals('pending_vendor', $req->current_stage);
    }

    public function test_whatsapp_messages_include_direct_links_and_login_uses_intended_redirect(): void
    {
        config(['services.fonnte.enable_in_tests' => true]);
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        $driver = User::where('username', 'adji')->firstOrFail();
        $spv = User::where('username', 'aklis')->firstOrFail();
        $vendor = User::where('username', 'vendor_sudan')->firstOrFail();
        $driver->update(['phone' => '081234567890']);
        $spv->update(['phone' => '081234567891']);

        $req = FuelRequest::firstOrFail();
        $req->update(['user_id' => $driver->id, 'spv_id' => $spv->id]);
        $req->refresh();

        $wa = new FonnteWhatsappService('dummy-token');
        $wa->notifyPendingSpv($req, $driver);
        $wa->notifyPendingUserVerification($req, $vendor);

        $expectedUrl = route('approval.show', $req->id);
        Http::assertSent(function ($request) use ($expectedUrl) {
            return str_contains((string) ($request['message'] ?? ''), $expectedUrl);
        });

        // Intended redirect: guest visits deep link /approval/{id} -> login -> redirected back to /approval/{id}
        $targetUrl = route('approval.show', $req->id);
        $this->get($targetUrl)->assertRedirect(route('login'));

        $loginResp = $this->post(route('login.submit'), [
            'login' => $spv->username,
            'password' => 'password123',
        ]);
        $loginResp->assertRedirect($targetUrl);
    }

    public function test_navbar_badge_counter_reflects_role_scoped_pending_items(): void
    {
        $spv = User::where('username', 'aklis')->firstOrFail();
        $driver = User::where('username', 'adji')->firstOrFail();
        $vehicle = Vehicle::where('is_active', true)->firstOrFail();

        FuelRequest::create([
            'request_no' => 'FR-BADGE-SPV-001',
            'user_id' => $driver->id,
            'driver_name' => $driver->name,
            'vehicle_id' => $vehicle->id,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'hm_km' => '11000',
            'total_liters' => 30,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'request_date' => now(),
            'status_spv' => 'menunggu',
            'current_stage' => 'pending_spv',
            'overall_status' => 'menunggu',
        ]);

        $resp = $this->actingAs($spv)->get(route('dashboard'));
        $resp->assertStatus(200);
        $resp->assertSee('digi-nav-count-badge', false);
    }

    public function test_dual_fuel_evaluation_range_standard_cumulative_and_admin_notes(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $driver = User::where('username', 'adji')->firstOrFail();
        $vehicle = Vehicle::where('unit_code', 'UT 208 DA 8191')->firstOrFail();

        // Set standard range 10 - 15 KM/L
        FuelStandard::where('vehicle_id', $vehicle->id)->update(['is_active' => false]);
        FuelStandard::create([
            'vehicle_id' => $vehicle->id,
            'standard_type' => 'km_per_liter',
            'standard_value' => 10.0,
            'standard_max' => 15.0,
            'tolerance_percentage' => 5.0,
            'effective_start' => '2026-01-01',
            'is_active' => true,
        ]);

        // Clear previous transactions for clean cumulative test
        FuelTransaction::whereHas('fuelRequest', fn($q) => $q->where('vehicle_id', $vehicle->id))->delete();

        // Create 2 historical transactions across 2 months:
        // Tx 1 (2026-08): 10000 -> 10500 KM (500 KM), 40 L => ratio 12.5 Km/L (Wajar in 10-15)
        // Tx 2 (2026-09): 10500 -> 11100 KM (600 KM), 50 L => ratio 12.0 Km/L (Wajar in 10-15)
        // Cumulative: (11100 - 10000) / (40 + 50) = 1100 / 90 = 12.22 Km/L (Wajar!)
        $fr1 = FuelRequest::create([
            'request_no' => 'FR-EVAL-001',
            'user_id' => $driver->id,
            'driver_name' => $driver->name,
            'vehicle_id' => $vehicle->id,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'hm_km' => '10000',
            'total_liters' => 40,
            'area' => 'SDU (Sungai Danau)',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'request_date' => '2026-08-10 08:00:00',
            'current_stage' => 'completed',
            'overall_status' => 'disetujui',
        ]);
        FuelTransaction::create([
            'transaction_no' => 'TRX-EVAL-001',
            'fuel_request_id' => $fr1->id,
            'fuelman_id' => $admin->id,
            'requested_liters' => 40,
            'actual_liters' => 40,
            'variance_liters' => 0,
            'meter_at_fueling' => 10500,
            'fueled_at' => '2026-08-10 09:00:00',
        ]);

        $fr2 = FuelRequest::create([
            'request_no' => 'FR-EVAL-002',
            'user_id' => $driver->id,
            'driver_name' => $driver->name,
            'vehicle_id' => $vehicle->id,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'hm_km' => '10500',
            'total_liters' => 50,
            'area' => 'SDU (Sungai Danau)',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'request_date' => '2026-09-12 08:00:00',
            'current_stage' => 'completed',
            'overall_status' => 'disetujui',
        ]);
        FuelTransaction::create([
            'transaction_no' => 'TRX-EVAL-002',
            'fuel_request_id' => $fr2->id,
            'fuelman_id' => $admin->id,
            'requested_liters' => 50,
            'actual_liters' => 50,
            'variance_liters' => 0,
            'meter_at_fueling' => 11100,
            'fueled_at' => '2026-09-12 09:00:00',
        ]);

        $service = app(FuelEvaluationService::class);
        $eval = $service->evaluateVehicle($vehicle, '2026-09', $admin->id);

        $this->assertEquals(10.0, (float)$eval->standard_used);
        $this->assertEquals(15.0, (float)$eval->standard_max_used);
        $this->assertEquals('normal', $eval->evaluation_status);
        $this->assertEquals(10000.0, (float)$eval->cumulative_meter_start);
        $this->assertEquals(11100.0, (float)$eval->cumulative_meter_end);
        $this->assertEquals(1100.0, (float)$eval->cumulative_distance);
        $this->assertEquals(90.0, (float)$eval->cumulative_liters);
        $this->assertEquals(12.22, (float)$eval->cumulative_ratio);
        $this->assertEquals('normal', $eval->cumulative_status);

        // Above max range (e.g. 20 KM/L > max 15) must flag needs_review
        $highEval = $service->evaluateVehicle($vehicle, '2026-10', $admin->id, 11100, 12100, 50);
        $this->assertEquals(20.0, (float)$highEval->actual_ratio);
        $this->assertEquals('needs_review', $highEval->evaluation_status);

        // Admin saves custom notes on evaluation
        $this->actingAs($admin)->post(route('evaluation.notes', $eval->id), [
            'admin_notes' => 'Unit baru ganti injektor dan maintenance 2x sebulan',
        ])->assertRedirect(route('evaluation.show', $eval->id));

        $eval->refresh();
        $this->assertEquals('Unit baru ganti injektor dan maintenance 2x sebulan', $eval->admin_notes);

        // Evaluation detail page renders cumulative section, per-fill columns, and admin notes
        $showResp = $this->actingAs($admin)->get(route('evaluation.show', $eval->id));
        $showResp->assertStatus(200);
        $showResp->assertSee('Evaluasi Keseluruhan / Kumulatif');
        $showResp->assertSee('Unit baru ganti injektor');
    }
}
