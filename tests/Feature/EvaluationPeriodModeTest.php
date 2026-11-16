<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\FuelEvaluation;
use App\Models\FuelTransaction;
use App\Models\FuelRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ZipArchive;

class EvaluationPeriodModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->vehicle = Vehicle::create([
            'unit_code' => 'DT-01',
            'police_no' => 'KT 8888 UT',
            'model' => 'Hino 500 FM 260 JD',
            'division' => 'Operational',
            'unit_type' => 'Dump Truck',
            'meter_type' => 'km',
            'tank_capacity' => 200,
            'is_active' => true,
        ]);

        // Create evaluation for month 2026-01
        FuelEvaluation::create([
            'evaluation_no' => 'EVAL-202601-DT01',
            'period' => '2026-01',
            'vehicle_id' => $this->vehicle->id,
            'meter_start' => 1000,
            'meter_end' => 2000,
            'total_distance_or_hours' => 1000,
            'total_actual_liters' => 200,
            'total_standard_liters' => 125,
            'actual_ratio' => 5.0,
            'standard_type' => 'km_per_liter',
            'standard_used' => 8.0,
            'tolerance_used' => 5.0,
            'price_used' => 27000,
            'total_actual_cost' => 5400000,
            'variance_liters' => 75,
            'waste_liters' => 75,
            'estimated_cost_loss' => 2025000,
            'evaluation_status' => 'needs_review',
            'calculated_by' => $this->admin->id,
        ]);

        // Create evaluation for month 2026-02
        FuelEvaluation::create([
            'evaluation_no' => 'EVAL-202602-DT01',
            'period' => '2026-02',
            'vehicle_id' => $this->vehicle->id,
            'meter_start' => 2000,
            'meter_end' => 3200,
            'total_distance_or_hours' => 1200,
            'total_actual_liters' => 150,
            'total_standard_liters' => 150,
            'actual_ratio' => 8.0,
            'standard_type' => 'km_per_liter',
            'standard_used' => 8.0,
            'tolerance_used' => 5.0,
            'price_used' => 27000,
            'total_actual_cost' => 4050000,
            'variance_liters' => 0,
            'waste_liters' => 0,
            'estimated_cost_loss' => 0,
            'evaluation_status' => 'normal',
            'calculated_by' => $this->admin->id,
        ]);
    }

    public function test_monthly_mode_evaluation_page_and_export(): void
    {
        $response = $this->actingAs($this->admin)->get('/evaluation?mode=monthly&period=2026-01');
        $response->assertStatus(200);
        $response->assertSee('Mode Periode:');
        $response->assertSee('DT-01');

        $export = $this->actingAs($this->admin)->get('/evaluation/export?mode=monthly&period=2026-01');
        $export->assertStatus(200);
        $export->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_range_mode_evaluation_page_consolidated_and_monthly(): void
    {
        // Consolidated view
        $response = $this->actingAs($this->admin)->get('/evaluation?mode=range&period_start=2026-01&period_end=2026-06&view=consolidated');
        $response->assertStatus(200);
        $response->assertSee('Konsolidasi Unit');
        $response->assertSee('2 Bulan'); // 2 months evaluated

        // Monthly view
        $response2 = $this->actingAs($this->admin)->get('/evaluation?mode=range&period_start=2026-01&period_end=2026-06&view=monthly');
        $response2->assertStatus(200);
        $response2->assertSee('2026-01');
        $response2->assertSee('2026-02');

        // Export range
        $export = $this->actingAs($this->admin)->get('/evaluation/export?mode=range&period_start=2026-01&period_end=2026-06');
        $export->assertStatus(200);
        $export->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_yearly_mode_evaluation_page_and_export(): void
    {
        $response = $this->actingAs($this->admin)->get('/evaluation?mode=yearly&year=2026');
        $response->assertStatus(200);
        $response->assertSee('Tahun 2026');

        $export = $this->actingAs($this->admin)->get('/evaluation/export?mode=yearly&year=2026');
        $export->assertStatus(200);
        $export->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_calculate_evaluation_for_range_and_yearly(): void
    {
        $responseRange = $this->actingAs($this->admin)->post('/evaluation/calculate', [
            'calc_mode' => 'range',
            'calc_start' => '2026-03',
            'calc_end' => '2026-04',
        ]);
        $responseRange->assertRedirect();
        $responseRange->assertSessionHas('success');

        $responseYearly = $this->actingAs($this->admin)->post('/evaluation/calculate', [
            'calc_mode' => 'yearly',
            'calc_year' => '2026',
        ]);
        $responseYearly->assertRedirect();
        $responseYearly->assertSessionHas('success');
    }
}
