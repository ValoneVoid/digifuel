<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\FuelEvaluation;
use App\Services\ExcelExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ZipArchive;

class MultiSheetExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_export_generates_multi_sheet_workbook_matching_monitoring_reference(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $vehicle1 = Vehicle::create([
            'unit_code' => 'UT 208 DA 8191',
            'police_no' => 'DA 8191',
            'model' => 'B/U FMC JB HDX TRITON',
            'unit_type' => 'Light Vehicle',
            'meter_type' => 'km',
            'tank_capacity' => 80,
            'is_active' => true,
        ]);

        $vehicle2 = Vehicle::create([
            'unit_code' => 'UT - 304 ( DA 8078 JJ )',
            'police_no' => 'DA 8078 JJ',
            'model' => 'LV BOX HDX TRITON',
            'unit_type' => 'Light Vehicle',
            'meter_type' => 'km',
            'tank_capacity' => 80,
            'is_active' => true,
        ]);

        FuelEvaluation::create([
            'evaluation_no' => 'EVAL-202605-001',
            'period' => '2026-05',
            'vehicle_id' => $vehicle1->id,
            'meter_start' => 100706,
            'meter_end' => 105664,
            'total_distance_or_hours' => 4958,
            'total_actual_liters' => 906,
            'total_standard_liters' => 619.75,
            'actual_ratio' => 5.47,
            'standard_type' => 'km_per_liter',
            'standard_used' => 8,
            'tolerance_used' => 5.0,
            'price_used' => 27000,
            'total_actual_cost' => 24462000,
            'variance_liters' => 286.25,
            'waste_liters' => 286.25,
            'estimated_cost_loss' => 7728750,
            'evaluation_status' => 'needs_review',
            'calculated_by' => $admin->id,
        ]);

        FuelEvaluation::create([
            'evaluation_no' => 'EVAL-202605-002',
            'period' => '2026-05',
            'vehicle_id' => $vehicle2->id,
            'meter_start' => 77117,
            'meter_end' => 83505,
            'total_distance_or_hours' => 6388,
            'total_actual_liters' => 710,
            'total_standard_liters' => 798.5,
            'actual_ratio' => 9.00,
            'standard_type' => 'km_per_liter',
            'standard_used' => 8,
            'tolerance_used' => 5.0,
            'price_used' => 27000,
            'total_actual_cost' => 19170000,
            'variance_liters' => 0,
            'waste_liters' => 0,
            'estimated_cost_loss' => 0,
            'evaluation_status' => 'normal',
            'calculated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/evaluation/export?period=2026-05');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->getFile();
        $this->assertNotNull($file);
        $tempPath = $file->getPathname();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempPath));

        $wbXml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $sheetNames = [];
        foreach ($wbXml->sheets->sheet as $s) {
            $sheetNames[] = (string)$s['name'];
        }

        // Must contain Dashboard, Master_Kendaraan, and dedicated vehicle sheets
        $this->assertContains('Dashboard', $sheetNames);
        $this->assertContains('Master_Kendaraan', $sheetNames);
        $this->assertContains('UT 208 DA 8191', $sheetNames);
        $this->assertContains('UT - 304 ( DA 8078 JJ )', $sheetNames);

        // Inspect Dashboard XML contains genuine formulas
        $dashXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString("SUM('UT 208 DA 8191'!G2:G1000)", $dashXml);
        $this->assertStringContainsString('Master_Kendaraan!A2', $dashXml);
        $this->assertStringContainsString('Master_Kendaraan!C2', $dashXml);
        $this->assertStringContainsString('TOTAL JARAK (KM)', $dashXml);

        // Inspect Unit Sheet XML contains VLOOKUP and calculation formulas
        $unitXml = $zip->getFromName('xl/worksheets/sheet3.xml');
        $this->assertStringContainsString('VLOOKUP(B2, Master_Kendaraan!$A$2:$C$50, 2, FALSE)', $unitXml);
        $this->assertStringContainsString('F2-E2', $unitXml);
        $this->assertStringContainsString('Total / Rata-rata', $unitXml);

        $zip->close();
        @unlink($tempPath);
    }
}
