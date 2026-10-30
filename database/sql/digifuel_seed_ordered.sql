-- =============================================================================
-- DIGIFUEL - PORTABLE & DEPENDENCY-ORDERED SQL SEED FILE (PHPMYADMIN READY)
-- =============================================================================
-- Keunggulan file SQL ini dibandingkan hasil Export standar phpMyAdmin:
-- 1. Urutan INSERT mengikuti hierarki Foreign Key (Tabel Induk -> Tabel Anak),
--    bukan urutan abjad (sehingga `users` dan `areas` selalu terisi sebelum `area_approvers`).
-- 2. TIDAK menggunakan ID numerik statis (seperti user_id = 4 atau area_id = 1).
--    Seluruh Foreign Key dicari secara dinamis menggunakan subquery berdasarkan
--    identitas unik (username, nrp, code, unit_code, request_no).
-- 3. Aman dijalankan berulang kali (Idempotent) tanpa menonaktifkan FOREIGN_KEY_CHECKS.
-- =============================================================================

START TRANSACTION;

-- -----------------------------------------------------------------------------
-- 1. ROLES (Tabel Induk)
-- -----------------------------------------------------------------------------
INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
('admin', 'Administrator', 'Full access to master data, reports, and fuel evaluation', NOW(), NOW()),
('driver', 'Employee / Driver', 'Submit Fuel Requests and print approved documents', NOW(), NOW()),
('spv', 'SPV / Dept Head (Acknowledge)', 'First stage Fuel Request approval', NOW(), NOW()),
('depthead', 'Dept Head / Manager (Approve)', 'Second stage Fuel Request approval', NOW(), NOW()),
('vendor', 'Vendor BBM', 'Final approval before fueling for assigned Vendor company', NOW(), NOW()),
('fuelman', 'Fuel Man', 'Verify Fuel Request and record actual fueling realization', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 2. AREAS (Tabel Induk)
-- -----------------------------------------------------------------------------
INSERT INTO `areas` (`code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
('SDU-01', 'SDU (Sungai Danau)', 1, NOW(), NOW()),
('PORT-02', 'Satui Port Facility', 1, NOW(), NOW()),
('WS-03', 'Satui Central Workshop', 1, NOW(), NOW()),
('PIT1-04', 'Pit 1 North Concession', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `code` = VALUES(`code`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 3. VENDORS (Tabel Induk)
-- -----------------------------------------------------------------------------
INSERT INTO `vendors` (`code`, `name`, `contact_person`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
('VND-001', 'Cv. Sudan Mandiri', 'Bpk. Hendra Sudan', '0812-5100-1122', 1, NOW(), NOW()),
('VND-002', 'CV. Prima Pertama', 'Bpk. Rudi Prima', '0813-4800-3344', 1, NOW(), NOW()),
('VND-003', 'PT Pertamina Patra Niaga', 'Fuel Depot Satui', '0811-500-9900', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `code` = VALUES(`code`),
    `contact_person` = VALUES(`contact_person`),
    `phone` = VALUES(`phone`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 4. EMPLOYEES (Tabel Induk)
-- -----------------------------------------------------------------------------
INSERT INTO `employees` (`nrp`, `name`, `position`, `department`, `company`, `source`, `external_system`, `external_reference`, `last_synced_at`, `is_active`, `created_at`, `updated_at`) VALUES
('80124024', 'Adji Tri Irianto', 'Driver', 'General Affairs & Fleet', 'PT UNITED TRACTORS Tbk - SITE SATUI', 'digisim', 'DIGISIM', 'SIMPERS-80124024', NOW(), 1, NOW(), NOW()),
('80125512', 'Rachmat Hidayat', 'Driver', 'Plant & Operation', 'PT UNITED TRACTORS Tbk - SITE SATUI', 'digisim', 'DIGISIM', 'SIMPERS-80125512', NOW(), 1, NOW(), NOW()),
('80129033', 'Muhammad Hamdi', 'Driver', 'Mining Operation', 'PT UNITED TRACTORS Tbk - SITE SATUI', 'local', NULL, NULL, NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `position` = VALUES(`position`),
    `department` = VALUES(`department`),
    `company` = VALUES(`company`),
    `source` = VALUES(`source`),
    `external_system` = VALUES(`external_system`),
    `external_reference` = VALUES(`external_reference`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 5. VEHICLES (Merujuk ke `areas` melalui kode unik 'SDU-01')
-- -----------------------------------------------------------------------------
INSERT INTO `vehicles` (`unit_code`, `police_no`, `model`, `unit_type`, `meter_type`, `default_area_id`, `tank_capacity`, `company`, `is_active`, `created_at`, `updated_at`) VALUES
('unit_26376G', 'police_8793unk', 'Komatsu PC650LC-11', 'Heavy Equipment', 'hm', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 880.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('unit_10284A', 'DA 8291 UT', 'Komatsu HD785-7', 'Heavy Equipment', 'hm', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 1000.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('unit_77812B', 'DA 7102 UT', 'Toyota Hilux 4x4 Double Cabin', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('unit_33190K', 'DA 9014 SP', 'Komatsu GD825A-2 Motor Grader', 'Heavy Equipment', 'hm', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 500.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('unit_90123M', 'DA 6543 UT', 'Scania P460 Heavy Tipper', 'Dump Truck', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 400.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT 208 DA 8191', 'DA 8191', 'B/U FMC JB HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT - 304 ( DA 8078 JJ )', 'DA 8078 JJ', 'LV BOX HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT - 202 ( DA 8232 JI )', 'DA 8232 JI', 'B/U FIELD HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT - 206 ( DA 8457 ZQ )', 'DA 8457 ZQ', 'B/U FIELD HILUX', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT - 207 ( DA 8645 JI )', 'DA 8645 JI', 'B/U GOH HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT - 209 ( DA 8562 ZP )', 'DA 8562 ZP', 'B/U GOH HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT - 302 ( DA 1730 YX )', 'DA 1730 YX', 'LV OPS ASC HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT- 303( DA 8493 JI )', 'DA 8493 JI', 'LV OPS P2U HILUX', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT B- 02 (N 7133 UI)', 'N 7133 UI', 'B/U FMC BAGONG', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT- 204 (DA 8180 JI)', 'DA 8180 JI', 'B/U FMC JB HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT- 205 (DA 8231 JI)', 'DA 8231 JI', 'B/U FMC JB HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT- 210 ( DA 8054 ZR )', 'DA 8054 ZR', 'B/U FMC JB HDX TRITON SPV', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT- 211 ( DA 8754 JJ )', 'DA 8754 JJ', 'B/U FMC JB HDX TRITON', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT- 212 ( DA 8624 JK )', 'DA 8624 JK', 'B/U FMC JB HDX TRITON SPV', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW()),
('UT-B- 01 (DA 7526 CD)', 'DA 7526 CD', 'BUS HAICE', 'Light Vehicle', 'km', (SELECT `id` FROM `areas` WHERE `code` = 'SDU-01' LIMIT 1), 80.00, 'PT UNITED TRACTORS TBK SITE SATUI', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `police_no` = VALUES(`police_no`),
    `model` = VALUES(`model`),
    `unit_type` = VALUES(`unit_type`),
    `meter_type` = VALUES(`meter_type`),
    `default_area_id` = VALUES(`default_area_id`),
    `tank_capacity` = VALUES(`tank_capacity`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 6. USERS (Merujuk ke `employees.nrp` dan `vendors.code` secara dinamis)
-- Password default seluruh akun: password123
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`employee_id`, `vendor_id`, `name`, `username`, `email`, `role`, `jabatan`, `nrp`, `company`, `signature_url`, `is_active`, `password`, `created_at`, `updated_at`) VALUES
((SELECT `id` FROM `employees` WHERE `nrp` = '80124024' LIMIT 1), NULL, 'Adji Tri Irianto', 'adji', 'adji@satui.unitedtractors.com', 'driver', 'Driver', '80124024', 'PT UNITED TRACTORS TBK SITE SATUI', 'images/sig_driver.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
((SELECT `id` FROM `employees` WHERE `nrp` = '80125512' LIMIT 1), NULL, 'Rachmat Hidayat', 'rachmat', 'rachmat@satui.unitedtractors.com', 'driver', 'Driver', '80125512', 'PT UNITED TRACTORS TBK SITE SATUI', 'images/sig_driver.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
((SELECT `id` FROM `employees` WHERE `nrp` = '80129033' LIMIT 1), NULL, 'Muhammad Hamdi', 'hamdi', 'hamdi@satui.unitedtractors.com', 'driver', 'Driver', '80129033', 'PT UNITED TRACTORS TBK SITE SATUI', NULL, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(NULL, NULL, 'Aklis', 'aklis', 'aklis@satui.unitedtractors.com', 'spv', 'Supervisor(SPV)', '98798', 'PT UNITED TRACTORS TBK SITE SATUI', 'images/sig_spv.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(NULL, NULL, 'ADYA', 'adya', 'adya@satui.unitedtractors.com', 'fuelman', 'Fuel Man(Vendor)', '99201', 'PT UNITED TRACTORS TBK SITE SATUI', 'images/sig_fuelman.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(NULL, NULL, 'Garius', 'Pdh-kusan', 'garius@satui.unitedtractors.com', 'depthead', 'Dept Head(Manager)', '97110', 'PT UNITED TRACTORS TBK SITE SATUI', 'images/sig_depthead.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(NULL, (SELECT `id` FROM `vendors` WHERE `code` = 'VND-001' LIMIT 1), 'Cv. Sudan Mandiri (Approver)', 'vendor_sudan', 'vendor.sudan@satui.unitedtractors.com', 'vendor', 'Vendor Approver', 'VND-001', 'Cv. Sudan Mandiri', 'images/sig_fuelman.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(NULL, NULL, 'Administrator UT Satui', 'admin', 'admin@satui.unitedtractors.com', 'admin', 'Administrator', '10001', 'PT UNITED TRACTORS TBK SITE SATUI', 'images/sig_depthead.png', 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `employee_id` = VALUES(`employee_id`),
    `vendor_id` = VALUES(`vendor_id`),
    `name` = VALUES(`name`),
    `role` = VALUES(`role`),
    `jabatan` = VALUES(`jabatan`),
    `nrp` = VALUES(`nrp`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 7. USER ROLES (Merujuk ke `users.username` dan `roles.name` secara dinamis)
-- -----------------------------------------------------------------------------
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`, `updated_at`) VALUES
((SELECT `id` FROM `users` WHERE `username` = 'adji' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'driver' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'rachmat' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'driver' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'hamdi' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'driver' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'aklis' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'spv' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'adya' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'fuelman' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'Pdh-kusan' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'depthead' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'vendor_sudan' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'vendor' LIMIT 1), NOW(), NOW()),
((SELECT `id` FROM `users` WHERE `username` = 'admin' LIMIT 1), (SELECT `id` FROM `roles` WHERE `name` = 'admin' LIMIT 1), NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 8. AREA APPROVERS (Merujuk ke `areas.code` dan `users.username` secara dinamis)
-- Tidak menggunakan ID numerik statis sehingga bebas dari error #1452
-- -----------------------------------------------------------------------------
INSERT INTO `area_approvers` (`area_id`, `user_id`, `role_type`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`)
SELECT a.`id`, u.`id`, m.`role_type`, '2025-01-01', NULL, 1, NOW(), NOW()
FROM `areas` a
CROSS JOIN (
    SELECT 'aklis' AS `username`, 'spv' AS `role_type`
    UNION ALL SELECT 'Pdh-kusan', 'manager'
    UNION ALL SELECT 'adya', 'fuelman'
    UNION ALL SELECT 'vendor_sudan', 'vendor'
) m
INNER JOIN `users` u ON u.`username` = m.`username`
WHERE a.`code` IN ('SDU-01', 'PORT-02', 'WS-03', 'PIT1-04')
  AND NOT EXISTS (
      SELECT 1 FROM `area_approvers` aa
      WHERE aa.`area_id` = a.`id`
        AND aa.`user_id` = u.`id`
        AND aa.`role_type` = m.`role_type`
  );

-- -----------------------------------------------------------------------------
-- 9. FUEL PRICES
-- -----------------------------------------------------------------------------
INSERT INTO `fuel_prices` (`fuel_type`, `price_per_liter`, `effective_start`, `effective_end`, `is_active`, `notes`, `created_at`, `updated_at`)
SELECT 'Solar Industri B35', 27000.00, '2025-01-01', NULL, 1, 'Harga resmi Solar Industri Site Satui sesuai laporan evaluasi BBM', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `fuel_prices` WHERE `fuel_type` = 'Solar Industri B35' AND `effective_start` = '2025-01-01'
);

COMMIT;

