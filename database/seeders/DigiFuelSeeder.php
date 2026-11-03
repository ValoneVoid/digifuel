<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use App\Models\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\Vendor;
use App\Models\UnitVendor;
use App\Models\AreaApprover;
use App\Models\FuelStandard;
use App\Models\FuelPrice;
use App\Models\FuelRequest;
use App\Models\FuelRequestApproval;
use App\Models\FuelTransaction;
use App\Models\FuelRequestHistory;
use App\Models\FuelEvaluation;
use App\Models\UserSignature;

class DigiFuelSeeder extends Seeder
{
    /**
     * Seed the application's database following strict Foreign Key dependency order:
     * 1. Roles
     * 2. Areas
     * 3. Vendors
     * 4. Employees
     * 5. Vehicles
     * 6. Users
     * 7. User Roles (Pivot)
     * 8. Area Approvers (Verified User + Area + Role consistency)
     * 9. Fuel Standards
     * 10. Fuel Prices
     * 11. User Signatures & Demo Transactions (Fuel Requests, Approvals, Transactions, History, Evaluations)
     */
    public function run(): void
    {
        DB::transaction(function () {
            $password = Hash::make('password123');

            // =========================================================================
            // 1. ROLES
            // =========================================================================
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

            // =========================================================================
            // 2. MASTER AREAS
            // =========================================================================
            $areasData = [
                ['code' => 'SDU-01', 'name' => 'SDU (Sungai Danau)'],
                ['code' => 'PORT-02', 'name' => 'Satui Port Facility'],
                ['code' => 'WS-03', 'name' => 'Satui Central Workshop'],
                ['code' => 'PIT1-04', 'name' => 'Pit 1 North Concession'],
            ];

            foreach ($areasData as $a) {
                Area::updateOrCreate(
                    ['name' => $a['name']],
                    [
                        'code' => $a['code'],
                        'is_active' => true,
                    ]
                );
            }

            $defaultArea = $this->findAreaByCode('SDU-01');

            // =========================================================================
            // 3. MASTER VENDORS
            // =========================================================================
            $vendorsData = [
                ['code' => 'VND-001', 'name' => 'Cv. Sudan Mandiri', 'contact_person' => 'Bpk. Hendra Sudan', 'phone' => '0812-5100-1122'],
                ['code' => 'VND-002', 'name' => 'CV. Prima Pertama', 'contact_person' => 'Bpk. Rudi Prima', 'phone' => '0813-4800-3344'],
                ['code' => 'VND-003', 'name' => 'PT Pertamina Patra Niaga', 'contact_person' => 'Fuel Depot Satui', 'phone' => '0811-500-9900'],
            ];

            foreach ($vendorsData as $ven) {
                Vendor::updateOrCreate(
                    ['name' => $ven['name']],
                    [
                        'code' => $ven['code'],
                        'contact_person' => $ven['contact_person'],
                        'phone' => $ven['phone'],
                        'is_active' => true,
                    ]
                );
            }

            // =========================================================================
            // 3b. MASTER VENDOR UNIT (Penyedia Unit Kendaraan / Rental / Internal)
            // =========================================================================
            $unitVendorsData = [
                ['code' => 'UVND-001', 'name' => 'PT United Tractors Tbk (Internal UT)', 'contact_person' => 'Asset & Fleet Management UT', 'phone' => '0512-88001', 'email' => 'fleet@unitedtractors.com', 'address' => 'Site Satui, Tanah Bumbu, Kalimantan Selatan'],
                ['code' => 'UVND-002', 'name' => 'PT Serasi Autoraya (TRAC)', 'contact_person' => 'Bpk. Wahyu TRAC', 'phone' => '0812-3344-5566', 'email' => 'satui.ops@trac.astra.co.id', 'address' => 'Jl. A. Yani KM 10, Banjarmasin'],
                ['code' => 'UVND-003', 'name' => 'CV Bagong Transport', 'contact_person' => 'Bpk. Sugeng Bagong', 'phone' => '0811-7788-9900', 'email' => 'operasional@bagongtransport.com', 'address' => 'Pool Bus Satui Km 4, Tanah Bumbu'],
                ['code' => 'UVND-004', 'name' => 'PT Mitra Pinasthika Mustika Rent', 'contact_person' => 'Bpk. Deni MPM', 'phone' => '0813-2211-4455', 'email' => 'fleet.kalimantan@mpm-rent.com', 'address' => 'Banjarbaru, Kalimantan Selatan'],
            ];

            foreach ($unitVendorsData as $uv) {
                UnitVendor::updateOrCreate(
                    ['name' => $uv['name']],
                    [
                        'code' => $uv['code'],
                        'contact_person' => $uv['contact_person'],
                        'phone' => $uv['phone'],
                        'email' => $uv['email'],
                        'address' => $uv['address'],
                        'is_active' => true,
                    ]
                );
            }

            // =========================================================================
            // 4. MASTER EMPLOYEES (Local & DIGISIM Prepared)
            // =========================================================================
            $employeesData = [
                [
                    'nrp' => '80124024',
                    'name' => 'Adji Tri Irianto',
                    'position' => 'Driver',
                    'department' => 'General Affairs & Fleet',
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'source' => 'digisim',
                    'external_system' => 'DIGISIM',
                    'external_reference' => 'SIMPERS-80124024',
                    'last_synced_at' => now(),
                    'is_active' => true,
                ],
                [
                    'nrp' => '80125512',
                    'name' => 'Rachmat Hidayat',
                    'position' => 'Driver',
                    'department' => 'Plant & Operation',
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'source' => 'digisim',
                    'external_system' => 'DIGISIM',
                    'external_reference' => 'SIMPERS-80125512',
                    'last_synced_at' => now(),
                    'is_active' => true,
                ],
                [
                    'nrp' => '80129033',
                    'name' => 'Muhammad Hamdi',
                    'position' => 'Driver',
                    'department' => 'Mining Operation',
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'source' => 'local',
                    'external_system' => null,
                    'external_reference' => null,
                    'last_synced_at' => null,
                    'is_active' => true,
                ],
            ];

            foreach ($employeesData as $empData) {
                Employee::updateOrCreate(
                    ['nrp' => $empData['nrp']],
                    $empData
                );
            }

            // =========================================================================
            // 5. MASTER VEHICLES (PPT Units + 15 LV Units from Monitoring BBM Mei 2026)
            // =========================================================================
            $pptVehicles = [
                ['unit_code' => 'unit_26376G', 'police_no' => 'police_8793unk', 'model' => 'Komatsu PC650LC-11', 'unit_type' => 'Heavy Equipment', 'meter_type' => 'hm', 'std_type' => 'liter_per_hour', 'std_val' => 35.0, 'cap' => 880],
                ['unit_code' => 'unit_10284A', 'police_no' => 'DA 8291 UT', 'model' => 'Komatsu HD785-7', 'unit_type' => 'Heavy Equipment', 'meter_type' => 'hm', 'std_type' => 'liter_per_hour', 'std_val' => 45.0, 'cap' => 1000],
                ['unit_code' => 'unit_77812B', 'police_no' => 'DA 7102 UT', 'model' => 'Toyota Hilux 4x4 Double Cabin', 'unit_type' => 'Light Vehicle', 'meter_type' => 'km', 'std_type' => 'km_per_liter', 'std_val' => 8.0, 'cap' => 80],
                ['unit_code' => 'unit_33190K', 'police_no' => 'DA 9014 SP', 'model' => 'Komatsu GD825A-2 Motor Grader', 'unit_type' => 'Heavy Equipment', 'meter_type' => 'hm', 'std_type' => 'liter_per_hour', 'std_val' => 30.0, 'cap' => 500],
                ['unit_code' => 'unit_90123M', 'police_no' => 'DA 6543 UT', 'model' => 'Scania P460 Heavy Tipper', 'unit_type' => 'Dump Truck', 'meter_type' => 'km', 'std_type' => 'km_per_liter', 'std_val' => 4.0, 'cap' => 400],
            ];

            $vendorUtInternal = $this->findUnitVendorByCode('UVND-001');
            $vendorTrac = $this->findUnitVendorByCode('UVND-002');
            $vendorBagong = $this->findUnitVendorByCode('UVND-003');

            foreach ($pptVehicles as $pv) {
                $uvId = str_contains($pv['model'], 'Hilux') ? $vendorTrac->id : $vendorUtInternal->id;
                Vehicle::updateOrCreate(
                    ['unit_code' => $pv['unit_code']],
                    [
                        'police_no' => $pv['police_no'],
                        'model' => $pv['model'],
                        'unit_type' => $pv['unit_type'],
                        'meter_type' => $pv['meter_type'],
                        'default_area_id' => $defaultArea->id,
                        'unit_vendor_id' => $uvId,
                        'tank_capacity' => $pv['cap'],
                        'is_active' => true,
                    ]
                );
            }

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
                $uvId = (stripos($lv['type'], 'BAGONG') !== false)
                    ? $vendorBagong->id
                    : ((stripos($lv['type'], 'HILUX') !== false || stripos($lv['type'], 'HAICE') !== false) ? $vendorTrac->id : $vendorUtInternal->id);

                Vehicle::updateOrCreate(
                    ['unit_code' => $lv['code']],
                    [
                        'police_no' => $lv['police'],
                        'model' => $lv['type'],
                        'unit_type' => 'Light Vehicle',
                        'meter_type' => 'km',
                        'default_area_id' => $defaultArea->id,
                        'unit_vendor_id' => $uvId,
                        'tank_capacity' => 80,
                        'is_active' => true,
                    ]
                );
            }

            // =========================================================================
            // 6. USERS (Resolved via stable Employee NRP & Vendor Code)
            // =========================================================================
            $empAdji = $this->findEmployeeByNrp('80124024');
            $empRachmat = $this->findEmployeeByNrp('80125512');
            $empHamdi = $this->findEmployeeByNrp('80129033');
            $vendorSudan = $this->findVendorByCode('VND-001');

            $usersData = [
                [
                    'username' => 'adji',
                    'email' => 'adji@satui.unitedtractors.com',
                    'employee_id' => $empAdji->id,
                    'vendor_id' => null,
                    'name' => 'Adji Tri Irianto',
                    'password' => $password,
                    'role' => 'driver',
                    'jabatan' => 'Driver',
                    'nrp' => '80124024',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => 'images/sig_driver.png',
                    'is_active' => true,
                ],
                [
                    'username' => 'rachmat',
                    'email' => 'rachmat@satui.unitedtractors.com',
                    'employee_id' => $empRachmat->id,
                    'vendor_id' => null,
                    'name' => 'Rachmat Hidayat',
                    'password' => $password,
                    'role' => 'driver',
                    'jabatan' => 'Driver',
                    'nrp' => '80125512',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => 'images/sig_driver.png',
                    'is_active' => true,
                ],
                [
                    'username' => 'hamdi',
                    'email' => 'hamdi@satui.unitedtractors.com',
                    'employee_id' => $empHamdi->id,
                    'vendor_id' => null,
                    'name' => 'Muhammad Hamdi',
                    'password' => $password,
                    'role' => 'driver',
                    'jabatan' => 'Driver',
                    'nrp' => '80129033',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => null,
                    'is_active' => true,
                ],
                [
                    'username' => 'aklis',
                    'email' => 'aklis@satui.unitedtractors.com',
                    'employee_id' => null,
                    'vendor_id' => null,
                    'name' => 'Aklis',
                    'password' => $password,
                    'role' => 'spv',
                    'jabatan' => 'Supervisor(SPV)',
                    'nrp' => '98798',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => 'images/sig_spv.png',
                    'is_active' => true,
                ],
                [
                    'username' => 'adya',
                    'email' => 'adya@satui.unitedtractors.com',
                    'employee_id' => null,
                    'vendor_id' => null,
                    'name' => 'ADYA',
                    'password' => $password,
                    'role' => 'fuelman',
                    'jabatan' => 'Fuel Man(Vendor)',
                    'nrp' => '99201',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => 'images/sig_fuelman.png',
                    'is_active' => true,
                ],
                [
                    'username' => 'Pdh-kusan',
                    'email' => 'garius@satui.unitedtractors.com',
                    'employee_id' => null,
                    'vendor_id' => null,
                    'name' => 'Garius',
                    'password' => $password,
                    'role' => 'depthead',
                    'jabatan' => 'Dept Head(Manager)',
                    'nrp' => '97110',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => 'images/sig_depthead.png',
                    'is_active' => true,
                ],
                [
                    'username' => 'vendor_sudan',
                    'email' => 'vendor.sudan@satui.unitedtractors.com',
                    'employee_id' => null,
                    'vendor_id' => $vendorSudan->id,
                    'name' => 'Cv. Sudan Mandiri (Approver)',
                    'password' => $password,
                    'role' => 'vendor',
                    'jabatan' => 'Vendor Approver',
                    'nrp' => 'VND-001',
                    'company' => 'Cv. Sudan Mandiri',
                    'signature_url' => 'images/sig_fuelman.png',
                    'is_active' => true,
                ],
                [
                    'username' => 'admin',
                    'email' => 'admin@satui.unitedtractors.com',
                    'employee_id' => null,
                    'vendor_id' => null,
                    'name' => 'Administrator UT Satui',
                    'password' => $password,
                    'role' => 'admin',
                    'jabatan' => 'Administrator',
                    'nrp' => '10001',
                    'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
                    'signature_url' => 'images/sig_depthead.png',
                    'is_active' => true,
                ],
            ];

            foreach ($usersData as $uData) {
                User::updateOrCreate(
                    ['email' => $uData['email']],
                    $uData
                );
            }

            // =========================================================================
            // 7. USER ROLES (Pivot Table user_roles via verified User & Role models)
            // =========================================================================
            foreach ($usersData as $uData) {
                $userModel = $this->findUserByUsername($uData['username']);
                $roleModel = $this->findRoleByName($uData['role']);

                DB::table('user_roles')->updateOrInsert(
                    [
                        'user_id' => $userModel->id,
                        'role_id' => $roleModel->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // =========================================================================
            // 8. AREA APPROVERS (Verified by Area Code, Username & Business Role Match)
            // =========================================================================
            $areaApproverMatrix = [
                ['username' => 'aklis', 'role_type' => 'spv'],
                ['username' => 'Pdh-kusan', 'role_type' => 'manager'],
                ['username' => 'adya', 'role_type' => 'fuelman'],
                ['username' => 'vendor_sudan', 'role_type' => 'vendor'],
            ];

            foreach ($areasData as $areaItem) {
                $area = $this->findAreaByCode($areaItem['code']);
                foreach ($areaApproverMatrix as $assignment) {
                    $approverUser = $this->findUserByUsername($assignment['username']);
                    $this->assignAreaApprover($area, $approverUser, $assignment['role_type']);
                }
            }

            // =========================================================================
            // 9. FUEL STANDARDS (Resolved via Vehicle Unit Code)
            // =========================================================================
            foreach ($pptVehicles as $pv) {
                $veh = $this->findVehicleByUnitCode($pv['unit_code']);
                $this->upsertFuelStandard(
                    $veh,
                    '2025-01-01',
                    $pv['std_type'],
                    (float) $pv['std_val'],
                    5.00,
                    "Standar konsumsi BBM resmi {$veh->unit_code}"
                );
            }

            foreach ($excelLvUnits as $lv) {
                $veh = $this->findVehicleByUnitCode($lv['code']);
                $this->upsertFuelStandard(
                    $veh,
                    '2025-01-01',
                    'km_per_liter',
                    (float) $lv['std'],
                    5.00,
                    "Standar konsumsi LV {$veh->unit_code} ({$lv['std']} Km/L)"
                );
            }

            // =========================================================================
            // 10. FUEL PRICES (1 Vendor BBM memiliki 1-M Fuel Prices)
            // =========================================================================
            $vendorPrima = $this->findVendorByCode('VND-002');
            $vendorPertamina = $this->findVendorByCode('VND-003');

            $this->upsertFuelPrice(
                'Solar Industri B35',
                '2025-01-01',
                27000.00,
                'Harga resmi Solar Industri Site Satui untuk Cv. Sudan Mandiri',
                $vendorSudan->id
            );
            $this->upsertFuelPrice(
                'Solar Industri B35',
                '2025-01-01',
                27000.00,
                'Harga resmi Solar Industri Site Satui untuk CV. Prima Pertama',
                $vendorPrima->id
            );
            $this->upsertFuelPrice(
                'Solar Industri B35',
                '2025-01-01',
                27000.00,
                'Harga resmi Pertamina Patra Niaga Depot Satui',
                $vendorPertamina->id
            );

            // =========================================================================
            // 11. USER SIGNATURES & TRANSACTIONAL DEMO DATA
            // =========================================================================
            foreach ($usersData as $uData) {
                if (!empty($uData['signature_url'])) {
                    $uModel = $this->findUserByUsername($uData['username']);
                    UserSignature::updateOrCreate(
                        [
                            'user_id' => $uModel->id,
                            'signature_path' => $uData['signature_url'],
                        ],
                        [
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Lookup verified models for Fuel Requests, Transactions & Evaluations
            $driverAdji = $this->findUserByUsername('adji');
            $driverRachmat = $this->findUserByUsername('rachmat');
            $driverHamdi = $this->findUserByUsername('hamdi');
            $spvAklis = $this->findUserByUsername('aklis');
            $fuelmanAdya = $this->findUserByUsername('adya');
            $deptheadGarius = $this->findUserByUsername('Pdh-kusan');
            $vendorUserSudan = $this->findUserByUsername('vendor_sudan');
            $adminUser = $this->findUserByUsername('admin');

            $areaSdu = $this->findAreaByCode('SDU-01');
            $areaPort = $this->findAreaByCode('PORT-02');
            $vendorPrima = $this->findVendorByCode('VND-002');

            $veh26376G = $this->findVehicleByUnitCode('unit_26376G');
            $veh10284A = $this->findVehicleByUnitCode('unit_10284A');
            $veh77812B = $this->findVehicleByUnitCode('unit_77812B');

            // 11A. Fuel Requests
            $fr1 = FuelRequest::updateOrCreate(
                ['request_no' => 'FR-2025-05-001'],
                [
                    'user_id' => $driverRachmat->id,
                    'employee_id' => $empRachmat->id,
                    'driver_name' => $driverRachmat->name,
                    'driver_nrp' => $driverRachmat->nrp,
                    'driver_position' => $empRachmat->position,
                    'driver_department' => $empRachmat->department,
                    'source_company' => $empRachmat->company,
                    'request_date' => '2025-05-11 22:29:00',
                    'planned_fuel_time' => '2025-05-11 23:00:00',
                    'unit_code' => $veh26376G->unit_code,
                    'vehicle_id' => $veh26376G->id,
                    'unit_vendor_id' => $veh26376G->unit_vendor_id,
                    'police_no' => $veh26376G->police_no,
                    'unit_type' => $veh26376G->unit_type,
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'hm_km' => '79',
                    'meter_type' => 'hm',
                    'total_liters' => 80,
                    'area' => $areaSdu->name,
                    'area_id' => $areaSdu->id,
                    'spv_id' => $spvAklis->id,
                    'spv_name' => 'Approval By 98798 - Aklis',
                    'manager_id' => $deptheadGarius->id,
                    'manager_name' => 'Approval By Pdh-kusan',
                    'fuelman_id' => $fuelmanAdya->id,
                    'fuelman_name' => 'Approval By ADYA',
                    'depthead_id' => $deptheadGarius->id,
                    'depthead_name' => 'Approval By Pdh-kusan',
                    'vendor_id' => $vendorSudan->id,
                    'vendor_user_id' => $vendorUserSudan->id,
                    'vendor_name' => $vendorSudan->name,
                    'status_spv' => 'menunggu',
                    'status_manager' => '-',
                    'status_vendor' => '-',
                    'status_fuelman' => '-',
                    'status_depthead' => '-',
                    'overall_status' => 'menunggu',
                    'current_stage' => 'pending_spv',
                    'driver_signature' => $driverRachmat->signature_url,
                ]
            );

            $fr2 = FuelRequest::updateOrCreate(
                ['request_no' => 'FR-2025-05-002'],
                [
                    'user_id' => $driverAdji->id,
                    'employee_id' => $empAdji->id,
                    'driver_name' => $driverAdji->name,
                    'driver_nrp' => $driverAdji->nrp,
                    'driver_position' => $empAdji->position,
                    'driver_department' => $empAdji->department,
                    'source_company' => $empAdji->company,
                    'request_date' => '2025-05-12 15:52:00',
                    'planned_fuel_time' => '2025-05-12 16:30:00',
                    'unit_code' => $veh26376G->unit_code,
                    'vehicle_id' => $veh26376G->id,
                    'unit_vendor_id' => $veh26376G->unit_vendor_id,
                    'police_no' => $veh26376G->police_no,
                    'unit_type' => $veh26376G->unit_type,
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'hm_km' => '85',
                    'meter_type' => 'hm',
                    'total_liters' => 78,
                    'area' => $areaSdu->name,
                    'area_id' => $areaSdu->id,
                    'spv_id' => $spvAklis->id,
                    'spv_name' => 'Approval By 98798 - Aklis',
                    'manager_id' => $deptheadGarius->id,
                    'manager_name' => 'Approval By Pdh-kusan',
                    'fuelman_id' => $fuelmanAdya->id,
                    'fuelman_name' => 'Approval By ADYA',
                    'depthead_id' => $deptheadGarius->id,
                    'depthead_name' => 'Approval By Pdh-kusan',
                    'vendor_id' => $vendorSudan->id,
                    'vendor_user_id' => $vendorUserSudan->id,
                    'vendor_name' => $vendorSudan->name,
                    'status_spv' => 'menunggu',
                    'status_manager' => '-',
                    'status_vendor' => '-',
                    'status_fuelman' => '-',
                    'status_depthead' => '-',
                    'overall_status' => 'menunggu',
                    'current_stage' => 'pending_spv',
                    'driver_signature' => $driverAdji->signature_url,
                ]
            );

            $fr3 = FuelRequest::updateOrCreate(
                ['request_no' => 'FR-2025-03-001'],
                [
                    'user_id' => $driverHamdi->id,
                    'employee_id' => $empHamdi->id,
                    'driver_name' => $driverHamdi->name,
                    'driver_nrp' => $driverHamdi->nrp,
                    'driver_position' => $empHamdi->position,
                    'driver_department' => $empHamdi->department,
                    'source_company' => $empHamdi->company,
                    'request_date' => '2025-03-11 14:16:00',
                    'planned_fuel_time' => '2025-03-11 15:00:00',
                    'unit_code' => $veh10284A->unit_code,
                    'vehicle_id' => $veh10284A->id,
                    'unit_vendor_id' => $veh10284A->unit_vendor_id,
                    'police_no' => $veh10284A->police_no,
                    'unit_type' => $veh10284A->unit_type,
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'hm_km' => '120',
                    'meter_type' => 'hm',
                    'total_liters' => 65,
                    'actual_liters' => 65,
                    'actual_hm_km' => 120,
                    'fueled_at' => '2025-03-11 16:00:00',
                    'area' => $areaSdu->name,
                    'area_id' => $areaSdu->id,
                    'spv_id' => $spvAklis->id,
                    'spv_name' => 'Approval By 98798 - Aklis',
                    'manager_id' => $deptheadGarius->id,
                    'manager_name' => 'Approval By Pdh-kusan',
                    'fuelman_id' => $fuelmanAdya->id,
                    'fuelman_name' => 'Approval By ADYA',
                    'depthead_id' => $deptheadGarius->id,
                    'depthead_name' => 'Approval By Pdh-kusan',
                    'vendor_id' => $vendorPrima->id,
                    'vendor_user_id' => null,
                    'vendor_name' => $vendorPrima->name,
                    'status_spv' => 'disetujui',
                    'spv_action_at' => '2025-03-11 14:30:00',
                    'status_manager' => 'disetujui',
                    'manager_action_at' => '2025-03-11 15:00:00',
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
                ]
            );

            $fr4 = FuelRequest::updateOrCreate(
                ['request_no' => 'FR-2025-03-002'],
                [
                    'user_id' => $driverHamdi->id,
                    'employee_id' => $empHamdi->id,
                    'driver_name' => $driverHamdi->name,
                    'driver_nrp' => $driverHamdi->nrp,
                    'driver_position' => $empHamdi->position,
                    'driver_department' => $empHamdi->department,
                    'source_company' => $empHamdi->company,
                    'request_date' => '2025-03-12 09:16:00',
                    'planned_fuel_time' => '2025-03-12 10:00:00',
                    'unit_code' => $veh77812B->unit_code,
                    'vehicle_id' => $veh77812B->id,
                    'unit_vendor_id' => $veh77812B->unit_vendor_id,
                    'police_no' => $veh77812B->police_no,
                    'unit_type' => $veh77812B->unit_type,
                    'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                    'hm_km' => '142',
                    'meter_type' => 'km',
                    'total_liters' => 50,
                    'actual_liters' => 50,
                    'actual_hm_km' => 142,
                    'fueled_at' => '2025-03-12 11:15:00',
                    'area' => $areaPort->name,
                    'area_id' => $areaPort->id,
                    'spv_id' => $spvAklis->id,
                    'spv_name' => 'Approval By 98798 - Aklis',
                    'manager_id' => $deptheadGarius->id,
                    'manager_name' => 'Approval By Pdh-kusan',
                    'fuelman_id' => $fuelmanAdya->id,
                    'fuelman_name' => 'Approval By ADYA',
                    'depthead_id' => $deptheadGarius->id,
                    'depthead_name' => 'Approval By Pdh-kusan',
                    'vendor_id' => $vendorSudan->id,
                    'vendor_user_id' => $vendorUserSudan->id,
                    'vendor_name' => $vendorSudan->name,
                    'status_spv' => 'disetujui',
                    'spv_action_at' => '2025-03-12 09:40:00',
                    'status_manager' => 'disetujui',
                    'manager_action_at' => '2025-03-12 10:15:00',
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
                ]
            );

            // 11B. Fuel Request Approvals for completed requests
            foreach ([$fr3, $fr4] as $completedFr) {
                FuelRequestApproval::updateOrCreate(
                    ['fuel_request_id' => $completedFr->id, 'stage' => 'spv'],
                    [
                        'approver_id' => $spvAklis->id,
                        'status' => 'approved',
                        'note' => 'Disetujui oleh SPV.',
                        'signature_path' => 'images/sig_spv.png',
                        'action_at' => $completedFr->spv_action_at,
                    ]
                );
                FuelRequestApproval::updateOrCreate(
                    ['fuel_request_id' => $completedFr->id, 'stage' => 'manager'],
                    [
                        'approver_id' => $deptheadGarius->id,
                        'status' => 'approved',
                        'note' => 'Disetujui oleh Dept Head/Manager.',
                        'signature_path' => 'images/sig_depthead.png',
                        'action_at' => $completedFr->depthead_action_at,
                    ]
                );
                FuelRequestApproval::updateOrCreate(
                    ['fuel_request_id' => $completedFr->id, 'stage' => 'vendor'],
                    [
                        'approver_id' => $vendorUserSudan->id,
                        'status' => 'approved',
                        'note' => 'Disetujui oleh Vendor - Siap Pengisian BBM.',
                        'signature_path' => 'images/sig_fuelman.png',
                        'action_at' => $completedFr->vendor_action_at,
                    ]
                );
            }

            // 11C. Fuel Transactions for completed requests
            FuelTransaction::updateOrCreate(
                ['transaction_no' => 'TRX-20250311-001'],
                [
                    'fuel_request_id' => $fr3->id,
                    'fuelman_id' => $fuelmanAdya->id,
                    'requested_liters' => 65,
                    'actual_liters' => 65,
                    'variance_liters' => 0,
                    'meter_at_fueling' => 120,
                    'fueled_at' => '2025-03-11 16:00:00',
                    'fuelman_signature' => 'images/sig_fuelman.png',
                    'notes' => 'Realisasi pengisian selesai sesuai surat Fuel Request FR-2025-03-001.',
                ]
            );

            FuelTransaction::updateOrCreate(
                ['transaction_no' => 'TRX-20250312-002'],
                [
                    'fuel_request_id' => $fr4->id,
                    'fuelman_id' => $fuelmanAdya->id,
                    'requested_liters' => 50,
                    'actual_liters' => 50,
                    'variance_liters' => 0,
                    'meter_at_fueling' => 142,
                    'fueled_at' => '2025-03-12 11:15:00',
                    'fuelman_signature' => 'images/sig_fuelman.png',
                    'notes' => 'Realisasi pengisian selesai sesuai surat Fuel Request FR-2025-03-002.',
                ]
            );

            // 11D. Fuel Evaluations (from Dashboard_Monitoring_BBM MEI 2026.xlsx)
            foreach ($excelLvUnits as $lv) {
                $veh = $this->findVehicleByUnitCode($lv['code']);
                $stdLiters = round($lv['dist'] / $lv['std'], 2);
                $actualRatio = round($lv['dist'] / $lv['liters'], 2);
                $variance = round($lv['liters'] - $stdLiters, 2);

                FuelEvaluation::updateOrCreate(
                    [
                        'period' => '2026-05',
                        'vehicle_id' => $veh->id,
                    ],
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
                        'calculated_by' => $adminUser->id,
                        'calculated_at' => now(),
                    ]
                );
            }
        });
    }

    /**
     * Find Role by unique name or fail with descriptive message.
     */
    protected function findRoleByName(string $name): Role
    {
        $role = Role::where('name', $name)->first();
        if (!$role) {
            throw new RuntimeException("Data referensi Role '{$name}' tidak ditemukan pada tabel roles.");
        }
        return $role;
    }

    /**
     * Find Area by unique code (or name) or fail with descriptive message.
     */
    protected function findAreaByCode(string $code): Area
    {
        $area = Area::where('code', $code)->first();
        if (!$area) {
            throw new RuntimeException("Data referensi Area dengan kode '{$code}' tidak ditemukan pada tabel areas.");
        }
        return $area;
    }

    /**
     * Find Vendor by unique code or fail with descriptive message.
     */
    protected function findVendorByCode(string $code): Vendor
    {
        $vendor = Vendor::where('code', $code)->first();
        if (!$vendor) {
            throw new RuntimeException("Data referensi Vendor dengan kode '{$code}' tidak ditemukan pada tabel vendors.");
        }
        return $vendor;
    }

    /**
     * Find UnitVendor by unique code or fail with descriptive message.
     */
    protected function findUnitVendorByCode(string $code): UnitVendor
    {
        $vendor = UnitVendor::where('code', $code)->first();
        if (!$vendor) {
            throw new RuntimeException("Data referensi Unit Vendor dengan kode '{$code}' tidak ditemukan pada tabel unit_vendors.");
        }
        return $vendor;
    }

    /**
     * Find Employee by unique NRP or fail with descriptive message.
     */
    protected function findEmployeeByNrp(string $nrp): Employee
    {
        $employee = Employee::where('nrp', $nrp)->first();
        if (!$employee) {
            throw new RuntimeException("Data referensi Employee dengan NRP '{$nrp}' tidak ditemukan pada tabel employees.");
        }
        return $employee;
    }

    /**
     * Find Vehicle by unique unit_code or fail with descriptive message.
     */
    protected function findVehicleByUnitCode(string $unitCode): Vehicle
    {
        $vehicle = Vehicle::where('unit_code', $unitCode)->first();
        if (!$vehicle) {
            throw new RuntimeException("Data referensi Vehicle dengan unit_code '{$unitCode}' tidak ditemukan pada tabel vehicles.");
        }
        return $vehicle;
    }

    /**
     * Find User by unique username or fail with descriptive message.
     */
    protected function findUserByUsername(string $username): User
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            throw new RuntimeException("Data referensi User dengan username '{$username}' tidak ditemukan pada tabel users.");
        }
        return $user;
    }

    /**
     * Verify role compatibility and assign an AreaApprover safely without relying on hardcoded numeric IDs.
     */
    protected function assignAreaApprover(Area $area, User $user, string $roleType): AreaApprover
    {
        if (!$area->is_active) {
            throw new RuntimeException("Area '{$area->name}' sedang tidak aktif dan tidak dapat diberikan penugasan Approver.");
        }

        if (!$user->is_active) {
            throw new RuntimeException("User '{$user->username}' sedang tidak aktif dan tidak dapat ditugaskan sebagai Approver.");
        }

        // Validate business logic: ensure User's role matches the AreaApprover role_type
        $isValidRole = match ($roleType) {
            'spv' => $user->isSpv(),
            'manager' => $user->isManager(),
            'vendor' => $user->isVendor(),
            'fuelman' => $user->isFuelman(),
            default => false,
        };

        if (!$isValidRole) {
            throw new RuntimeException(
                "Pelanggaran aturan bisnis: User '{$user->username}' (role: {$user->role}) tidak sesuai untuk ditugaskan sebagai '{$roleType}' pada Area '{$area->name}'."
            );
        }

        return AreaApprover::updateOrCreate(
            [
                'area_id' => $area->id,
                'user_id' => $user->id,
                'role_type' => $roleType,
            ],
            [
                'start_date' => '2025-01-01',
                'end_date' => null,
                'is_active' => true,
            ]
        );
    }

    /**
     * Upsert FuelStandard safely across both MySQL and SQLite date formats.
     */
    protected function upsertFuelStandard(
        Vehicle $vehicle,
        string $effectiveStart,
        string $standardType,
        float $standardValue,
        float $tolerancePercentage,
        string $notes
    ): FuelStandard {
        $existing = FuelStandard::where('vehicle_id', $vehicle->id)
            ->whereDate('effective_start', $effectiveStart)
            ->first();

        $payload = [
            'vehicle_id' => $vehicle->id,
            'standard_type' => $standardType,
            'standard_value' => $standardValue,
            'tolerance_percentage' => $tolerancePercentage,
            'effective_start' => $effectiveStart,
            'effective_end' => null,
            'is_active' => true,
            'notes' => $notes,
        ];

        if ($existing) {
            $existing->update($payload);
            return $existing;
        }

        return FuelStandard::create($payload);
    }

    /**
     * Upsert FuelPrice safely across both MySQL and SQLite date formats with vendor relation.
     */
    protected function upsertFuelPrice(
        string $fuelType,
        string $effectiveStart,
        float $pricePerLiter,
        string $notes,
        ?int $vendorId = null
    ): FuelPrice {
        $existing = FuelPrice::where('fuel_type', $fuelType)
            ->where('vendor_id', $vendorId)
            ->whereDate('effective_start', $effectiveStart)
            ->first();

        $payload = [
            'vendor_id' => $vendorId,
            'fuel_type' => $fuelType,
            'price_per_liter' => $pricePerLiter,
            'effective_start' => $effectiveStart,
            'effective_end' => null,
            'is_active' => true,
            'notes' => $notes,
        ];

        if ($existing) {
            $existing->update($payload);
            return $existing;
        }

        return FuelPrice::create($payload);
    }
}
