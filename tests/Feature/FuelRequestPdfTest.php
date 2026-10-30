<?php

namespace Tests\Feature;

use App\Models\FuelRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FuelRequestPdfService;
use Database\Seeders\DigiFuelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $driverAdji;
    protected User $driverRachmat;
    protected User $spvAklis;
    protected User $deptheadManager;
    protected User $vendorSudan;
    protected User $fuelmanAdya;

    protected FuelRequest $approvedRequest;
    protected FuelRequest $pendingRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DigiFuelSeeder::class);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->driverAdji = User::where('username', 'adji')->firstOrFail();
        $this->driverRachmat = User::where('username', 'rachmat')->firstOrFail();
        $this->spvAklis = User::where('username', 'aklis')->firstOrFail();
        $this->deptheadManager = User::where('username', 'Pdh-kusan')->firstOrFail();
        $this->vendorSudan = User::where('username', 'vendor_sudan')->firstOrFail();
        $this->fuelmanAdya = User::where('username', 'adya')->firstOrFail();

        // 1. Create a clean Vendor-Approved request owned by Driver Adji
        $this->approvedRequest = FuelRequest::create([
            'request_no' => 'FR-2026-10-001',
            'user_id' => $this->driverAdji->id,
            'employee_id' => $this->driverAdji->employee_id,
            'driver_name' => $this->driverAdji->name,
            'driver_nrp' => $this->driverAdji->nrp ?? '80124024',
            'driver_position' => 'Driver Operasional',
            'driver_department' => 'Operation',
            'unit_code' => 'unit_10284A',
            'police_no' => 'DA 8291 UT',
            'unit_type' => 'Light Vehicle',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'area' => 'SDU (Sungai Danau)',
            'hm_km' => 79,
            'meter_type' => 'km',
            'request_date' => now(),
            'total_liters' => 50,
            'spv_id' => $this->spvAklis->id,
            'spv_name' => 'Aklis',
            'status_spv' => 'disetujui',
            'spv_action_at' => now(),
            'spv_signature' => 'images/sig_spv.png',
            'depthead_id' => $this->deptheadManager->id,
            'depthead_name' => 'Garius',
            'status_depthead' => 'disetujui',
            'depthead_action_at' => now(),
            'depthead_signature' => 'images/sig_depthead.png',
            'vendor_id' => $this->vendorSudan->vendor_id,
            'vendor_name' => 'Cv. Sudan Mandiri',
            'vendor_user_id' => $this->vendorSudan->id,
            'status_vendor' => 'disetujui',
            'vendor_action_at' => now(),
            'vendor_signature' => 'images/sig_fuelman.png',
            'fuelman_id' => $this->fuelmanAdya->id,
            'fuelman_name' => 'Adya',
            'status_fuelman' => 'disetujui',
            'driver_signature' => 'images/sig_driver.png',
            'overall_status' => 'disetujui',
            'current_stage' => 'approved',
        ]);

        // 2. Pending request (pending_spv)
        $this->pendingRequest = FuelRequest::where('current_stage', 'pending_spv')->first()
            ?? FuelRequest::create([
                'request_no' => 'FR-2025-05-002',
                'user_id' => $this->driverAdji->id,
                'driver_name' => $this->driverAdji->name,
                'unit_code' => 'unit_26376G',
                'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
                'area' => 'SDU (Sungai Danau)',
                'hm_km' => 120,
                'meter_type' => 'km',
                'request_date' => now(),
                'total_liters' => 45,
                'status_spv' => 'menunggu',
                'overall_status' => 'menunggu',
                'current_stage' => 'pending_spv',
            ]);
    }

    // =========================================================================
    // A. PENGUJIAN AKSES & KEAMANAN (IDOR, ROLES & AUTH)
    // =========================================================================

    /**
     * 1. Guest tidak dapat mengakses endpoint PDF (harus diarahkan ke login)
     */
    public function test_guest_cannot_access_pdf_endpoint(): void
    {
        $response = $this->get(route('fuel.pdf', $this->approvedRequest->id));
        $response->assertRedirect(route('login'));

        $streamResponse = $this->get(route('fuel.pdf.stream', $this->approvedRequest->id));
        $streamResponse->assertRedirect(route('login'));
    }

    /**
     * 2 & 3. Pengguna yang tidak berwenang tidak dapat mengakses dokumen milik orang lain (IDOR Protection)
     * Driver Rachmat tidak boleh mengakses PDF pengajuan milik Driver Adji
     */
    public function test_unauthorized_driver_cannot_access_other_driver_pdf(): void
    {
        $response = $this->actingAs($this->driverRachmat)
            ->get(route('fuel.pdf', $this->approvedRequest->id));

        $response->assertStatus(403);
    }

    /**
     * 4. Admin dapat mengakses dan mengunduh dokumen PDF pengajuan apapun yang telah disetujui
     */
    public function test_admin_can_download_approved_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('fuel.pdf', $this->approvedRequest->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * 5. Employee / Driver pemilik pengajuan dapat mengunduh dokumen PDF pengajuannya sendiri
     */
    public function test_employee_owner_can_download_own_approved_pdf(): void
    {
        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $this->approvedRequest->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename="Fuel-Request-FR-2026-10-001.pdf"', $response->headers->get('Content-Disposition'));
    }

    /**
     * 6. Approver internal (SPV, Dept Head, Fuel Man) dan Vendor yang ditugaskan dapat mengakses PDF
     */
    public function test_authorized_approvers_and_assigned_vendor_can_access_pdf(): void
    {
        // SPV
        $this->actingAs($this->spvAklis)
            ->get(route('fuel.pdf', $this->approvedRequest->id))
            ->assertStatus(200);

        // Dept Head
        $this->actingAs($this->deptheadManager)
            ->get(route('fuel.pdf', $this->approvedRequest->id))
            ->assertStatus(200);

        // Assigned Vendor (Cv. Sudan Mandiri)
        $this->actingAs($this->vendorSudan)
            ->get(route('fuel.pdf', $this->approvedRequest->id))
            ->assertStatus(200);

        // Fuel Man
        $this->actingAs($this->fuelmanAdya)
            ->get(route('fuel.pdf', $this->approvedRequest->id))
            ->assertStatus(200);
    }

    /**
     * Vendor lain yang tidak ditugaskan ditolak mengakses PDF (Isolasi Antar-Vendor)
     */
    public function test_unassigned_vendor_cannot_access_pdf(): void
    {
        $otherVendor = Vendor::where('name', 'CV. Prima Pertama')->first();
        if (!$otherVendor) {
            $otherVendor = Vendor::create([
                'name' => 'CV. Prima Pertama',
                'code' => 'VND-002',
                'is_active' => true,
            ]);
        }

        $otherVendorUser = User::factory()->create([
            'name' => 'Vendor Prima User',
            'username' => 'vendor_prima',
            'role' => 'vendor',
            'vendor_id' => $otherVendor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherVendorUser)
            ->get(route('fuel.pdf', $this->approvedRequest->id));

        $response->assertStatus(403);
    }

    // =========================================================================
    // B. PENGUJIAN STATUS PENGAJUAN & ATURAN BISNIS
    // =========================================================================

    /**
     * 7. Pengajuan yang belum disetujui Vendor (masih pending_spv / pending_vendor) tidak dapat menghasilkan PDF final
     */
    public function test_pending_request_cannot_generate_pdf(): void
    {
        // Driver Adji accessing their own pending request
        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $this->pendingRequest->id));

        $response->assertStatus(403);

        // Even Admin cannot generate final sheet for an unapproved request
        $adminResponse = $this->actingAs($this->admin)
            ->get(route('fuel.pdf', $this->pendingRequest->id));

        $adminResponse->assertStatus(403);
    }

    /**
     * 8. Pengajuan yang telah disetujui Vendor dapat menghasilkan PDF
     */
    public function test_vendor_approved_request_can_generate_pdf(): void
    {
        $this->assertTrue($this->approvedRequest->isVendorApproved());

        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $this->approvedRequest->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * 9. Pengajuan yang ditolak (rejected) tidak dapat menghasilkan dokumen seolah-olah disetujui
     */
    public function test_rejected_request_cannot_generate_pdf(): void
    {
        $rejectedRequest = FuelRequest::create([
            'request_no' => 'FR-REJECTED-001',
            'user_id' => $this->driverAdji->id,
            'driver_name' => $this->driverAdji->name,
            'unit_code' => 'LV-001',
            'police_no' => 'DA 1234 UT',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'area' => 'SDU (Sungai Danau)',
            'hm_km' => 12000,
            'meter_type' => 'km',
            'request_date' => now(),
            'total_liters' => 50,
            'status_spv' => 'ditolak',
            'spv_note' => 'Ditolak karena unit sedang perbaikan',
            'overall_status' => 'ditolak',
            'current_stage' => 'rejected',
        ]);

        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $rejectedRequest->id));

        $response->assertStatus(403);
    }

    /**
     * 10. Pengajuan yang dibatalkan (cancelled) tidak dapat menghasilkan dokumen final
     */
    public function test_cancelled_request_cannot_generate_pdf(): void
    {
        $cancelledRequest = FuelRequest::create([
            'request_no' => 'FR-CANCEL-001',
            'user_id' => $this->driverAdji->id,
            'driver_name' => $this->driverAdji->name,
            'unit_code' => 'LV-001',
            'police_no' => 'DA 1234 UT',
            'company' => 'PT UNITED TRACTORS TBK SITE SATUI',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'area' => 'SDU (Sungai Danau)',
            'hm_km' => 12000,
            'meter_type' => 'km',
            'request_date' => now(),
            'total_liters' => 50,
            'status_spv' => 'menunggu',
            'overall_status' => 'dibatalkan',
            'current_stage' => 'cancelled',
        ]);

        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $cancelledRequest->id));

        $response->assertStatus(403);
    }

    // =========================================================================
    // C. PENGUJIAN STRUKTUR, KONTEN PDF & INTEGRITAS DATA
    // =========================================================================

    /**
     * 11 & 12. Response memiliki MIME type application/pdf dan header valid
     */
    public function test_pdf_response_headers_and_binary_content(): void
    {
        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $this->approvedRequest->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertStringContainsString('%%EOF', $content);
    }

    /**
     * 13 - 17. Verifikasi data payload dokumen pada Service:
     * - Nomor pengajuan
     * - Identitas karyawan
     * - Unit & meter
     * - Liter diminta (bukan liter transaksi)
     * - Data persetujuan
     */
    public function test_service_prepares_exact_data_without_tampering_or_mixing_actuals(): void
    {
        /** @var FuelRequestPdfService $service */
        $service = app(FuelRequestPdfService::class);
        $payload = $service->preparePdfData($this->approvedRequest);

        // 13. Nomor pengajuan
        $this->assertEquals($this->approvedRequest->request_no, $payload['request']['request_no']);
        $this->assertEquals('PT UNITED TRACTORS Tbk - SITE SATUI', $payload['companyName']);

        // 14. Identitas karyawan
        $this->assertEquals($this->approvedRequest->driver_name, $payload['driver']['name']);
        $this->assertNotNull($payload['driver']['nrp']);

        // 15. Informasi unit
        $this->assertEquals($this->approvedRequest->unit_code, $payload['unit']['code']);
        $this->assertEquals($this->approvedRequest->police_no, $payload['unit']['police_no']);

        // 16. Data persetujuan
        $this->assertTrue($payload['approvals']['spv']['is_approved']);
        $this->assertTrue($payload['approvals']['vendor']['is_approved']);

        // 17. Volume diminta tidak tertukar dengan realisasi aktual
        $this->assertEquals((float)$this->approvedRequest->total_liters, $payload['request']['total_liters']);
    }

    /**
     * 18 & 19. Tanda tangan digital dapat dimuat apabila tersedia, dan jika tidak tersedia tidak menyebabkan error
     */
    public function test_handles_signatures_safely_when_available_and_when_null(): void
    {
        /** @var FuelRequestPdfService $service */
        $service = app(FuelRequestPdfService::class);

        // Existing image
        $existingSig = $service->getSignatureBase64('images/sig_driver.png');
        $this->assertNotNull($existingSig);
        $this->assertStringStartsWith('data:image/png;base64,', $existingSig);

        // Non-existent image returns null without throwing exception
        $nullSig = $service->getSignatureBase64(null);
        $this->assertNull($nullSig);

        $ghostSig = $service->getSignatureBase64('images/non_existent_signature_file.png');
        $this->assertNull($ghostSig);
    }

    /**
     * 20. Data yang tidak lengkap ditangani dengan aman tanpa crash
     */
    public function test_handles_incomplete_or_partial_data_safely(): void
    {
        $minimalRequest = FuelRequest::create([
            'request_no' => 'FR-MINIMAL-999',
            'user_id' => $this->driverAdji->id,
            'driver_name' => 'Minimal Driver',
            'unit_code' => 'LV-MINIMAL',
            'police_no' => 'DA 9999 UT',
            'vendor_name' => 'Cv. Sudan Mandiri',
            'company' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'area' => 'SDU (Sungai Danau)',
            'hm_km' => 0,
            'meter_type' => 'km',
            'driver_nrp' => null,
            'driver_position' => null,
            'notes' => null,
            'driver_signature' => null,
            'spv_signature' => null,
            'depthead_signature' => null,
            'vendor_signature' => null,
            'request_date' => now(),
            'total_liters' => 100,
            'status_spv' => 'disetujui',
            'status_depthead' => 'disetujui',
            'status_vendor' => 'disetujui',
            'overall_status' => 'disetujui',
            'current_stage' => 'approved',
        ]);

        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf', $minimalRequest->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test stream endpoint returns inline PDF
     */
    public function test_stream_endpoint_returns_inline_pdf(): void
    {
        $response = $this->actingAs($this->driverAdji)
            ->get(route('fuel.pdf.stream', $this->approvedRequest->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
    }
}
