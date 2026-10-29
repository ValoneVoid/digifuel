<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\Vendor;
use App\Models\AreaApprover;
use App\Models\FuelStandard;
use App\Models\FuelPrice;
use App\Models\FuelRequest;
use App\Models\FuelTransaction;
use App\Models\FuelRequestHistory;
use App\Models\FuelEvaluation;
use Illuminate\Support\Facades\Hash;

class DigiFuelSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // 1. Roles
        $rolesData = [
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Full access to master data, reports, and fuel evaluation'],
            ['name' => 'driver', 'display_name' => 'Employee / Driver', 'description' => 'Submit Fuel Requests and print approved documents'],
            ['name' => 'spv', 'display_name' => 'SPV / Dept Head (Acknowledge)', 'description' => 'First stage Fuel Request approval'],
            ['name' => 'depthead', 'display_name' => 'Dept Head / Manager (Approve)', 'description' => 'Second stage Fuel Request approval'],
            ['name' => 'vendor', 'display_name' => 'Vendor BBM', 'description' => 'Final approval before fueling for assigned Vendor company'],
            ['name' => 'fuelman', 'display_name' => 'Fuel Man', 'description' => 'Verify Fuel Request and record actual fueling realization'],
        ];
        foreach ($rolesData as $r) {
            Role::updateOrCreate(['name' => $r['name']], $r);
        }

        // 2. Master Areas
        $areas = [
            ['code' => 'SDU-01', 'name' => 'SDU (Sungai Danau)'],
            ['code' => 'PORT-02', 'name' => 'Satui Port Facility'],
            ['code' => 'WS-03', 'name' => 'Satui Central Workshop'],
            ['code' => 'PIT1-04', 'name' => 'Pit 1 North Concession'],
        ];
        $areaModels = [];
        foreach ($areas as $a) {
            $areaModels[$a['name']] = Area::updateOrCreate(['name' => $a['name']], [
                'code' => $a['code'],
                'is_active' => true,
            ]);
        }

        // 3. Master Vendors
        $vendors = [
            ['code' => 'VND-001', 'name' => 'Cv. Sudan Mandiri', 'contact_person' => 'Bpk. Hendra Sudan', 'phone' => '0812-5100-1122'],
            ['code' => 'VND-002', 'name' => 'CV. Prima Pertama', 'contact_person' => 'Bpk. Rudi Prima', 'phone' => '0813-4800-3344'],
            ['code' => 'VND-003', 'name' => 'PT Pertamina Patra Niaga', 'contact_person' => 'Fuel Depot Satui', 'phone' => '0811-500-9900'],
        ];
        $vendorModels = [];
        foreach ($vendors as $ven) {
            $vendorModels[$ven['name']] = Vendor::updateOrCreate(['name' => $ven['name']], [
                'code' => $ven['code'],
                'contact_person' => $ven['contact_person'],
                'phone' => $ven['phone'],
                'is_active' => true,
            ]);
        }

        // 4. Master Employees (Local & DIGISIM Prepared)
        $emp1 = Employee::updateOrCreate(['nrp' => '80124024'], [
            'name' => 'Adji Tri Irianto',
            'position' => 'Driver',
            'department' => 'General Affairs & Fleet',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'source' => 'digisim',
            'external_system' => 'DIGISIM',
            'external_reference' => 'SIMPERS-80124024',
            'last_synced_at' => now(),
            'is_active' => true,
        ]);

        $emp2 = Employee::updateOrCreate(['nrp' => '80125512'], [
            'name' => 'Rachmat Hidayat',
            'position' => 'Driver',
            'department' => 'Plant & Operation',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'source' => 'digisim',
            'external_system' => 'DIGISIM',
            'external_reference' => 'SIMPERS-80125512',
            'last_synced_at' => now(),
            'is_active' => true,
        ]);

        $emp3 = Employee::updateOrCreate(['nrp' => '80129033'], [
            'name' => 'Muhammad Hamdi',
            'position' => 'Driver',
            'department' => 'Mining Operation',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'source' => 'local',
            'is_active' => true,
        ]);

        // 5. Users (All 6 Roles)
        $driver1 = User::updateOrCreate(['email' => 'adji@satui.unitedtractors.com'], [
            'employee_id' => $emp1->id,
            'name' => 'Adji Tri Irianto',
            'username' => 'adji',
            'password' => $password,
            'role' => 'driver',
            'jabatan' => 'Driver',
            'nrp' => '80124024',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => 'images/sig_driver.png',
            'is_active' => true,
        ]);

        $driver2 = User::updateOrCreate(['email' => 'rachmat@satui.unitedtractors.com'], [
            'employee_id' => $emp2->id,
            'name' => 'Rachmat Hidayat',
            'username' => 'rachmat',
            'password' => $password,
            'role' => 'driver',
            'jabatan' => 'Driver',
            'nrp' => '80125512',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => 'images/sig_driver.png',
            'is_active' => true,
        ]);

        $driver3 = User::updateOrCreate(['email' => 'hamdi@satui.unitedtractors.com'], [
            'employee_id' => $emp3->id,
            'name' => 'Muhammad Hamdi',
            'username' => 'hamdi',
            'password' => $password,
            'role' => 'driver',
            'jabatan' => 'Driver',
            'nrp' => '80129033',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => null,
            'is_active' => true,
        ]);

        $spv1 = User::updateOrCreate(['email' => 'aklis@satui.unitedtractors.com'], [
            'name' => 'Aklis',
            'username' => 'aklis',
            'password' => $password,
            'role' => 'spv',
            'jabatan' => 'Supervisor(SPV)',
            'nrp' => '98798',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => 'images/sig_spv.png',
            'is_active' => true,
        ]);

        $fuelman1 = User::updateOrCreate(['email' => 'adya@satui.unitedtractors.com'], [
            'name' => 'ADYA',
            'username' => 'adya',
            'password' => $password,
            'role' => 'fuelman',
            'jabatan' => 'Fuel Man(Vendor)',
            'nrp' => '99201',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => 'images/sig_fuelman.png',
            'is_active' => true,
        ]);

        $depthead1 = User::updateOrCreate(['email' => 'garius@satui.unitedtractors.com'], [
            'name' => 'Garius',
            'username' => 'Pdh-kusan',
            'password' => $password,
            'role' => 'depthead',
            'jabatan' => 'Dept Head(Manager)',
            'nrp' => '97110',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => 'images/sig_depthead.png',
            'is_active' => true,
        ]);

        $vendorUser1 = User::updateOrCreate(['email' => 'vendor.sudan@satui.unitedtractors.com'], [
            'vendor_id' => $vendorModels['Cv. Sudan Mandiri']->id,
            'name' => 'Cv. Sudan Mandiri (Approver)',
            'username' => 'vendor_sudan',
            'password' => $password,
            'role' => 'vendor',
            'jabatan' => 'Vendor Approver',
            'nrp' => 'VND-001',
            'company' => 'Cv. Sudan Mandiri',
            'signature_url' => 'images/sig_fuelman.png',
            'is_active' => true,
        ]);

        $admin1 = User::updateOrCreate(['email' => 'admin@satui.unitedtractors.com'], [
            'name' => 'Administrator UT Satui',
            'username' => 'admin',
            'password' => $password,
            'role' => 'admin',
            'jabatan' => 'Administrator',
            'nrp' => '10001',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'signature_url' => 'images/sig_depthead.png',
            'is_active' => true,
        ]);

        // 6. Area Approver Assignments (for dependent dropdowns)
        foreach ($areaModels as $areaObj) {
            AreaApprover::updateOrCreate(
                ['area_id' => $areaObj->id, 'user_id' => $spv1->id, 'role_type' => 'spv'],
                ['start_date' => '2025-01-01', 'is_active' => true]
            );
            AreaApprover::updateOrCreate(
                ['area_id' => $areaObj->id, 'user_id' => $depthead1->id, 'role_type' => 'manager'],
                ['start_date' => '2025-01-01', 'is_active' => true]
            );
            AreaApprover::updateOrCreate(
                ['area_id' => $areaObj->id, 'user_id' => $fuelman1->id, 'role_type' => 'fuelman'],
                ['start_date' => '2025-01-01', 'is_active' => true]
            );
            AreaApprover::updateOrCreate(
                ['area_id' => $areaObj->id, 'user_id' => $vendorUser1->id, 'role_type' => 'vendor'],
                ['start_date' => '2025-01-01', 'is_active' => true]
            );
        }

        // 7. Fuel Price History (Rp 27.000/L as per Dashboard Monitoring BBM Mei 2026)
        FuelPrice::updateOrCreate(
            ['fuel_type' => 'Solar Industri B35', 'effective_start' => '2025-01-01'],
            [
                'price_per_liter' => 27000.00,
                'is_active' => true,
                'notes' => 'Harga resmi Solar Industri Site Satui sesuai laporan evaluasi BBM',
            ]
        );

        // 8. Master Vehicles (PPT Units + 15 LV Units from Dashboard_Monitoring_BBM MEI 2026.xlsx)
        $pptVehicles = [
            ['unit_code' => 'unit_26376G', 'police_no' => 'police_8793unk', 'model' => 'Komatsu PC650LC-11', 'unit_type' => 'Heavy Equipment', 'meter_type' => 'hm', 'std_type' => 'liter_per_hour', 'std_val' => 35.0, 'cap' => 880],
            ['unit_code' => 'unit_10284A', 'police_no' => 'DA 8291 UT', 'model' => 'Komatsu HD785-7', 'unit_type' => 'Heavy Equipment', 'meter_type' => 'hm', 'std_type' => 'liter_per_hour', 'std_val' => 45.0, 'cap' => 1000],
            ['unit_code' => 'unit_77812B', 'police_no' => 'DA 7102 UT', 'model' => 'Toyota Hilux 4x4 Double Cabin', 'unit_type' => 'Light Vehicle', 'meter_type' => 'km', 'std_type' => 'km_per_liter', 'std_val' => 8.0, 'cap' => 80],
            ['unit_code' => 'unit_33190K', 'police_no' => 'DA 9014 SP', 'model' => 'Komatsu GD825A-2 Motor Grader', 'unit_type' => 'Heavy Equipment', 'meter_type' => 'hm', 'std_type' => 'liter_per_hour', 'std_val' => 30.0, 'cap' => 500],
            ['unit_code' => 'unit_90123M', 'police_no' => 'DA 6543 UT', 'model' => 'Scania P460 Heavy Tipper', 'unit_type' => 'Dump Truck', 'meter_type' => 'km', 'std_type' => 'km_per_liter', 'std_val' => 4.0, 'cap' => 400],
        ];

        foreach ($pptVehicles as $pv) {
            $veh = Vehicle::updateOrCreate(['unit_code' => $pv['unit_code']], [
                'police_no' => $pv['police_no'],
                'model' => $pv['model'],
                'unit_type' => $pv['unit_type'],
                'meter_type' => $pv['meter_type'],
                'tank_capacity' => $pv['cap'],
                'is_active' => true,
            ]);
            FuelStandard::updateOrCreate(['vehicle_id' => $veh->id, 'effective_start' => '2025-01-01'], [
                'standard_type' => $pv['std_type'],
                'standard_value' => $pv['std_val'],
                'tolerance_percentage' => 5.00,
                'is_active' => true,
            ]);
        }

        // Real 15 Light Vehicles & Evaluations from Dashboard_Monitoring_BBM MEI 2026.xlsx
        $excelLvUnits = [
            ['code' => 'UT 208 DA 8191', 'police' => 'DA 8191', 'type' => 'B/U FMC JB HDX TRITON', 'dist' => 4958, 'liters' => 906, 'std' => 8, 'status' => 'needs_review', 'waste' => 286.25, 'loss' => 7728750, 'm_start' => 100706, 'm_end' => 105664],
            ['code' => 'UT - 304 ( DA 8078 JJ )', 'police' => 'DA 8078 JJ', 'type' => 'LV BOX HDX TRITON', 'dist' => 6388, 'liters' => 710, 'std' => 8, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 77117, 'm_end' => 83505],
            ['code' => 'UT - 202 ( DA 8232 JI )', 'police' => 'DA 8232 JI', 'type' => 'B/U FIELD HDX TRITON', 'dist' => 4016, 'liters' => 550, 'std' => 8, 'status' => 'needs_review', 'waste' => 48, 'loss' => 1296000, 'm_start' => 92798, 'm_end' => 96814],
            ['code' => 'UT - 206 ( DA 8457 ZQ )', 'police' => 'DA 8457 ZQ', 'type' => 'B/U FIELD HILUX', 'dist' => 3214, 'liters' => 380, 'std' => 8, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 64000, 'm_end' => 67214],
            ['code' => 'UT - 207 ( DA 8645 JI )', 'police' => 'DA 8645 JI', 'type' => 'B/U GOH HDX TRITON', 'dist' => 4486, 'liters' => 600, 'std' => 8, 'status' => 'needs_review', 'waste' => 39.25, 'loss' => 1059750, 'm_start' => 81000, 'm_end' => 85486],
            ['code' => 'UT - 209 ( DA 8562 ZP )', 'police' => 'DA 8562 ZP', 'type' => 'B/U GOH HDX TRITON', 'dist' => 3426, 'liters' => 420, 'std' => 8, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 52000, 'm_end' => 55426],
            ['code' => 'UT - 302 ( DA 1730 YX )', 'police' => 'DA 1730 YX', 'type' => 'LV OPS ASC HDX TRITON', 'dist' => 4310, 'liters' => 420, 'std' => 8, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 48000, 'm_end' => 52310],
            ['code' => 'UT- 303( DA 8493 JI )', 'police' => 'DA 8493 JI', 'type' => 'LV OPS P2U HILUX', 'dist' => 2784, 'liters' => 310, 'std' => 8, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 39000, 'm_end' => 41784],
            ['code' => 'UT B- 02 (N 7133 UI)', 'police' => 'N 7133 UI', 'type' => 'B/U FMC BAGONG', 'dist' => 3278, 'liters' => 800, 'std' => 4, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 112000, 'm_end' => 115278],
            ['code' => 'UT- 204 (DA 8180 JI)', 'police' => 'DA 8180 JI', 'type' => 'B/U FMC JB HDX TRITON', 'dist' => 5470, 'liters' => 1010, 'std' => 8, 'status' => 'needs_review', 'waste' => 326.25, 'loss' => 8808750, 'm_start' => 88000, 'm_end' => 93470],
            ['code' => 'UT- 205 (DA 8231 JI)', 'police' => 'DA 8231 JI', 'type' => 'B/U FMC JB HDX TRITON', 'dist' => 5132, 'liters' => 930, 'std' => 8, 'status' => 'needs_review', 'waste' => 288.50, 'loss' => 7789500, 'm_start' => 91000, 'm_end' => 96132],
            ['code' => 'UT- 210 ( DA 8054 ZR )', 'police' => 'DA 8054 ZR', 'type' => 'B/U FMC JB HDX TRITON SPV', 'dist' => 2530, 'liters' => 320, 'std' => 8, 'status' => 'normal', 'waste' => 3.75, 'loss' => 101250, 'm_start' => 44000, 'm_end' => 46530],
            ['code' => 'UT- 211 ( DA 8754 JJ )', 'police' => 'DA 8754 JJ', 'type' => 'B/U FMC JB HDX TRITON', 'dist' => 4178, 'liters' => 670, 'std' => 8, 'status' => 'needs_review', 'waste' => 147.75, 'loss' => 3989250, 'm_start' => 73000, 'm_end' => 77178],
            ['code' => 'UT- 212 ( DA 8624 JK )', 'police' => 'DA 8624 JK', 'type' => 'B/U FMC JB HDX TRITON SPV', 'dist' => 3024, 'liters' => 320, 'std' => 8, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 61000, 'm_end' => 64024],
            ['code' => 'UT-B- 01 (DA 7526 CD)', 'police' => 'DA 7526 CD', 'type' => 'BUS HAICE', 'dist' => 7950, 'liters' => 916, 'std' => 6, 'status' => 'normal', 'waste' => 0, 'loss' => 0, 'm_start' => 130000, 'm_end' => 137950],
        ];

        foreach ($excelLvUnits as $lv) {
            $veh = Vehicle::updateOrCreate(['unit_code' => $lv['code']], [
                'police_no' => $lv['police'],
                'model' => $lv['type'],
                'unit_type' => 'Light Vehicle',
                'meter_type' => 'km',
                'tank_capacity' => 80,
                'is_active' => true,
            ]);

            FuelStandard::updateOrCreate(['vehicle_id' => $veh->id, 'effective_start' => '2025-01-01'], [
                'standard_type' => 'km_per_liter',
                'standard_value' => $lv['std'],
                'tolerance_percentage' => 5.00,
                'is_active' => true,
            ]);

            $stdLiters = round($lv['dist'] / $lv['std'], 2);
            $actualRatio = round($lv['dist'] / $lv['liters'], 2);
            $variance = round($lv['liters'] - $stdLiters, 2);

            FuelEvaluation::updateOrCreate(
                ['period' => '2026-05', 'vehicle_id' => $veh->id],
                [
                    'evaluation_no' => 'EVAL-202605-' . preg_replace('/[^A-Za-z0-9]/', '', $lv['code']),
                    'meter_start' => $lv['m_start'],
                    'meter_end' => $lv['m_end'],
                    'total_distance_or_hours' => $lv['dist'],
                    'total_actual_liters' => $lv['liters'],
                    'total_standard_liters' => $stdLiters,
                    'actual_ratio' => $actualRatio,
                    'standard_type' => 'km_per_liter',
                    'standard_used' => $lv['std'],
                    'tolerance_used' => 5.00,
                    'price_used' => 27000,
                    'total_actual_cost' => $lv['liters'] * 27000,
                    'variance_liters' => $variance,
                    'waste_liters' => $lv['waste'],
                    'estimated_cost_loss' => $lv['loss'],
                    'evaluation_status' => $lv['status'],
                    'notes' => $lv['status'] === 'needs_review'
                        ? "Terdeteksi varian pemborosan {$lv['waste']} Liter di atas standar {$lv['std']} Km/L."
                        : "Konsumsi BBM berada dalam batas wajar sesuai target rasio {$lv['std']} Km/L.",
                    'calculated_by' => $admin1->id,
                    'calculated_at' => now(),
                ]
            );
        }

        // 9. Fuel Requests (from PPT slides)
        $fr1 = FuelRequest::updateOrCreate(['request_no' => 'FR-2025-05-001'], [
            'user_id' => $driver2->id,
            'employee_id' => $emp2->id,
            'driver_name' => $driver2->name,
            'driver_nrp' => $driver2->nrp,
            'request_date' => '2025-05-11 22:29:00',
            'unit_code' => 'unit_26376G',
            'police_no' => 'police_8793unk',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'hm_km' => '79',
            'meter_type' => 'hm',
            'total_liters' => 80,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv1->id,
            'spv_name' => 'Approval By 98798 - Aklis',
            'fuelman_id' => $fuelman1->id,
            'fuelman_name' => 'Approval By ADYA',
            'depthead_id' => $depthead1->id,
            'depthead_name' => 'Approval By Pdh-kusan',
            'vendor_id' => $vendorModels['Cv. Sudan Mandiri']->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'status_spv' => 'menunggu',
            'status_fuelman' => '-',
            'status_depthead' => '-',
            'overall_status' => 'menunggu',
            'current_stage' => 'pending_spv',
            'driver_signature' => $driver2->signature_url,
        ]);

        $fr2 = FuelRequest::updateOrCreate(['request_no' => 'FR-2025-05-002'], [
            'user_id' => $driver1->id,
            'employee_id' => $emp1->id,
            'driver_name' => $driver1->name,
            'driver_nrp' => $driver1->nrp,
            'request_date' => '2025-05-12 15:52:00',
            'unit_code' => 'unit_26376G',
            'police_no' => 'police_8793unk',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'hm_km' => '85',
            'meter_type' => 'hm',
            'total_liters' => 78,
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv1->id,
            'spv_name' => 'Approval By 98798 - Aklis',
            'fuelman_id' => $fuelman1->id,
            'fuelman_name' => 'Approval By ADYA',
            'depthead_id' => $depthead1->id,
            'depthead_name' => 'Approval By Pdh-kusan',
            'vendor_id' => $vendorModels['Cv. Sudan Mandiri']->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'status_spv' => 'menunggu',
            'status_fuelman' => '-',
            'status_depthead' => '-',
            'overall_status' => 'menunggu',
            'current_stage' => 'pending_spv',
            'driver_signature' => $driver1->signature_url,
        ]);

        $fr3 = FuelRequest::updateOrCreate(['request_no' => 'FR-2025-03-001'], [
            'user_id' => $driver3->id,
            'employee_id' => $emp3->id,
            'driver_name' => $driver3->name,
            'driver_nrp' => $driver3->nrp,
            'request_date' => '2025-03-11 14:16:00',
            'unit_code' => 'unit_10284A',
            'police_no' => 'DA 8291 UT',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'hm_km' => '120',
            'meter_type' => 'hm',
            'total_liters' => 65,
            'actual_liters' => 65,
            'actual_hm_km' => 120,
            'fueled_at' => '2025-03-11 16:00:00',
            'area' => 'SDU (Sungai Danau)',
            'spv_id' => $spv1->id,
            'spv_name' => 'Approval By 98798 - Aklis',
            'fuelman_id' => $fuelman1->id,
            'fuelman_name' => 'Approval By ADYA',
            'depthead_id' => $depthead1->id,
            'depthead_name' => 'Approval By Pdh-kusan',
            'vendor_id' => $vendorModels['CV. Prima Pertama']->id,
            'vendor_name' => 'CV. Prima Pertama',
            'status_spv' => 'disetujui',
            'spv_action_at' => '2025-03-11 14:30:00',
            'status_depthead' => 'disetujui',
            'depthead_action_at' => '2025-03-11 15:00:00',
            'status_vendor' => 'disetujui',
            'vendor_action_at' => '2025-03-11 15:45:00',
            'status_fuelman' => 'disetujui',
            'fuelman_action_at' => '2025-03-11 16:00:00',
            'overall_status' => 'disetujui',
            'current_stage' => 'completed',
            'driver_signature' => 'images/sig_driver.png',
            'spv_signature' => 'images/sig_spv.png',
            'fuelman_signature' => 'images/sig_fuelman.png',
            'vendor_signature' => 'images/sig_fuelman.png',
            'depthead_signature' => 'images/sig_depthead.png',
        ]);

        $fr4 = FuelRequest::updateOrCreate(['request_no' => 'FR-2025-03-002'], [
            'user_id' => $driver3->id,
            'employee_id' => $emp3->id,
            'driver_name' => $driver3->name,
            'driver_nrp' => $driver3->nrp,
            'request_date' => '2025-03-12 09:16:00',
            'unit_code' => 'unit_77812B',
            'police_no' => 'DA 7102 UT',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'hm_km' => '142',
            'meter_type' => 'km',
            'total_liters' => 50,
            'actual_liters' => 50,
            'actual_hm_km' => 142,
            'fueled_at' => '2025-03-12 11:15:00',
            'area' => 'Satui Port Facility',
            'spv_id' => $spv1->id,
            'spv_name' => 'Approval By 98798 - Aklis',
            'fuelman_id' => $fuelman1->id,
            'fuelman_name' => 'Approval By ADYA',
            'depthead_id' => $depthead1->id,
            'depthead_name' => 'Approval By Pdh-kusan',
            'vendor_id' => $vendorModels['Cv. Sudan Mandiri']->id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'status_spv' => 'disetujui',
            'spv_action_at' => '2025-03-12 09:40:00',
            'status_depthead' => 'disetujui',
            'depthead_action_at' => '2025-03-12 10:15:00',
            'status_vendor' => 'disetujui',
            'vendor_action_at' => '2025-03-12 11:00:00',
            'status_fuelman' => 'disetujui',
            'fuelman_action_at' => '2025-03-12 11:15:00',
            'overall_status' => 'disetujui',
            'current_stage' => 'completed',
            'driver_signature' => 'images/sig_driver.png',
            'spv_signature' => 'images/sig_spv.png',
            'fuelman_signature' => 'images/sig_fuelman.png',
            'vendor_signature' => 'images/sig_fuelman.png',
            'depthead_signature' => 'images/sig_depthead.png',
        ]);

        // Seed transactions for completed requests
        FuelTransaction::updateOrCreate(['transaction_no' => 'TRX-20250311-001'], [
            'fuel_request_id' => $fr3->id,
            'fuelman_id' => $fuelman1->id,
            'requested_liters' => 65,
            'actual_liters' => 65,
            'variance_liters' => 0,
            'meter_at_fueling' => 120,
            'fueled_at' => '2025-03-11 16:00:00',
            'fuelman_signature' => 'images/sig_fuelman.png',
            'notes' => 'Realisasi pengisian selesai sesuai surat Fuel Request FR-2025-03-001.',
        ]);

        FuelTransaction::updateOrCreate(['transaction_no' => 'TRX-20250312-002'], [
            'fuel_request_id' => $fr4->id,
            'fuelman_id' => $fuelman1->id,
            'requested_liters' => 50,
            'actual_liters' => 50,
            'variance_liters' => 0,
            'meter_at_fueling' => 142,
            'fueled_at' => '2025-03-12 11:15:00',
            'fuelman_signature' => 'images/sig_fuelman.png',
            'notes' => 'Realisasi pengisian selesai sesuai surat Fuel Request FR-2025-03-002.',
        ]);
    }
}
