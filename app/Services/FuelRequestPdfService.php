<?php

namespace App\Services;

use App\Models\FuelRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FuelRequestPdfService
{
    /**
     * Eager load all relations required for complete Fuel Request Sheet rendering.
     */
    public function loadFuelRequest(int|FuelRequest $fuelRequest): FuelRequest
    {
        if (is_int($fuelRequest)) {
            $fuelRequest = FuelRequest::findOrFail($fuelRequest);
        }

        return $fuelRequest->loadMissing([
            'user',
            'employee',
            'vehicle',
            'areaModel',
            'vendorModel',
            'spv',
            'depthead',
            'manager',
            'vendorUser',
            'fuelman',
            'approvals.approver',
        ]);
    }

    /**
     * Convert local image to Base64 Data URI for reliable, offline DomPDF embedding.
     */
    public function getSignatureBase64(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $realPath = null;
        $filename = basename($path);

        $candidates = [
            storage_path('app/private/signatures/' . $filename),
            public_path('uploads/signatures/' . $filename),
            public_path($path),
            public_path('images/' . $filename),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                $realPath = $candidate;
                break;
            }
        }

        if ($realPath) {
            try {
                $mime = @mime_content_type($realPath) ?: 'image/png';
                $content = @file_get_contents($realPath);
                if ($content !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal mengonversi tanda tangan ke base64: {$path}", ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Retrieve FuelLink logo as Base64 Data URI.
     */
    public function getLogoBase64(): ?string
    {
        $logoPath = file_exists(public_path('images/fuellink_logo.png'))
            ? public_path('images/fuellink_logo.png')
            : public_path('images/ut_header_logo.png');
        if (file_exists($logoPath) && is_file($logoPath)) {
            try {
                $mime = @mime_content_type($logoPath) ?: 'image/png';
                $content = @file_get_contents($logoPath);
                if ($content !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal mengonversi logo FuelLink ke base64", ['error' => $e->getMessage()]);
            }
        }
        return null;
    }

    /**
     * Prepare structured view payload ensuring historical fidelity and complete null-safety.
     */
    public function preparePdfData(FuelRequest $fuelRequest): array
    {
        $fuelRequest = $this->loadFuelRequest($fuelRequest);

        // Find stage approvals from audit table if available
        $spvApproval = $fuelRequest->approvals->where('stage', 'spv')->first();
        $deptheadApproval = $fuelRequest->approvals->where('stage', 'manager')->first();
        $vendorApproval = $fuelRequest->approvals->where('stage', 'vendor')->first();

        // Signatures resolution (Base64)
        $driverSig = $this->getSignatureBase64(
            $fuelRequest->driver_signature ?? $fuelRequest->user?->signature_url
        );

        $spvSig = $this->getSignatureBase64(
            $fuelRequest->spv_signature 
            ?? $fuelRequest->spv?->signature_url 
            ?? $spvApproval?->signature_path
        );

        $deptheadSig = $this->getSignatureBase64(
            $fuelRequest->depthead_signature 
            ?? $fuelRequest->depthead?->signature_url 
            ?? $fuelRequest->manager?->signature_url 
            ?? $deptheadApproval?->signature_path
        );

        $vendorSig = $this->getSignatureBase64(
            $fuelRequest->vendor_signature 
            ?? $fuelRequest->fuelman_signature 
            ?? $fuelRequest->vendorUser?->signature_url 
            ?? $vendorApproval?->signature_path
        );

        // Names & Approvers
        $driverName = $fuelRequest->driver_name ?? $fuelRequest->employee?->name ?? $fuelRequest->user?->name ?? 'Employee';
        $driverNrp = $fuelRequest->driver_nrp ?? $fuelRequest->employee?->nrp ?? $fuelRequest->user?->nrp ?? '-';
        $driverPosition = $fuelRequest->driver_position ?? $fuelRequest->employee?->position ?? $fuelRequest->user?->jabatan ?? 'Driver / Operator';

        $spvName = $fuelRequest->spv?->name ?? $fuelRequest->spv_name ?? $spvApproval?->approver?->name ?? 'Supervisor';
        $deptheadName = $fuelRequest->depthead?->name ?? $fuelRequest->manager?->name ?? $fuelRequest->manager_name ?? $deptheadApproval?->approver?->name ?? 'Dept. Head / Manager';
        $vendorName = $fuelRequest->vendor_name ?? $fuelRequest->vendorModel?->name ?? 'Vendor BBM';
        $vendorApproverName = $fuelRequest->vendorUser?->name ?? $vendorApproval?->approver?->name ?? $fuelRequest->fuelman?->name ?? 'Petugas Vendor';

        return [
            'fuelRequest' => $fuelRequest,
            'logoBase64' => $this->getLogoBase64(),
            'generatedAt' => Carbon::now(),
            'companyName' => 'PT UNITED TRACTORS Tbk - SITE SATUI',
            'docTitle' => 'FUEL REQUEST SHEET',
            'cleanRequestNo' => preg_replace('/[^A-Za-z0-9_\-]/', '-', $fuelRequest->request_no),
            
            // Driver / Employee
            'driver' => [
                'name' => $driverName,
                'nrp' => $driverNrp,
                'position' => $driverPosition,
                'department' => $fuelRequest->driver_department ?? $fuelRequest->employee?->department ?? 'Operation',
                'signature' => $driverSig,
            ],

            // Unit details
            'unit' => [
                'code' => $fuelRequest->unit_code,
                'police_no' => $fuelRequest->police_no ?? $fuelRequest->vehicle?->police_no ?? '-',
                'unit_type' => $fuelRequest->unit_type ?? $fuelRequest->vehicle?->unit_type ?? 'Light Vehicle',
                'unit_vendor' => $fuelRequest->unitVendor?->name ?? ($fuelRequest->vehicle?->unitVendor?->name ?? 'Internal UT'),
                'area' => $fuelRequest->area ?? $fuelRequest->areaModel?->name ?? 'Satui Site',
            ],

            // Fuel Request details
            'request' => [
                'request_no' => $fuelRequest->request_no,
                'date' => $fuelRequest->request_date ? $fuelRequest->request_date->format('d/m/Y') : '-',
                'time' => $fuelRequest->request_date ? $fuelRequest->request_date->format('H:i') : '-',
                'planned_time' => $fuelRequest->planned_fuel_time ? $fuelRequest->planned_fuel_time->format('d/m/Y H:i') : null,
                'meter_type' => 'KM',
                'hm_km' => $fuelRequest->hm_km !== null ? (float)$fuelRequest->hm_km : 0,
                'total_liters' => (float)$fuelRequest->total_liters,
                'vendor_name' => $vendorName,
                'area' => $fuelRequest->area ?? 'Satui Site',
                'notes' => $fuelRequest->notes,
                'stage_label' => $fuelRequest->stage_label,
            ],

            // Approvals matrix
            'approvals' => [
                'spv' => [
                    'name' => $spvName,
                    'status' => $fuelRequest->status_spv === 'disetujui' ? 'Disetujui' : ($fuelRequest->status_spv ?? '-'),
                    'action_at' => $fuelRequest->spv_action_at ?? $spvApproval?->action_at,
                    'signature' => $spvSig,
                    'is_approved' => $fuelRequest->status_spv === 'disetujui',
                ],
                'depthead' => [
                    'name' => $deptheadName,
                    'status' => ($fuelRequest->status_depthead === 'disetujui' || $fuelRequest->status_manager === 'disetujui') ? 'Disetujui' : ($fuelRequest->status_depthead ?? $fuelRequest->status_manager ?? '-'),
                    'action_at' => $fuelRequest->depthead_action_at ?? $fuelRequest->manager_action_at ?? $deptheadApproval?->action_at,
                    'signature' => $deptheadSig,
                    'is_approved' => ($fuelRequest->status_depthead === 'disetujui' || $fuelRequest->status_manager === 'disetujui'),
                ],
                'vendor' => [
                    'company' => $vendorName,
                    'name' => $vendorApproverName,
                    'status' => $fuelRequest->isVendorApproved() ? 'Disetujui (Ready for Fueling)' : ($fuelRequest->status_vendor ?? '-'),
                    'action_at' => $fuelRequest->vendor_action_at ?? $fuelRequest->fuelman_action_at ?? $vendorApproval?->action_at,
                    'signature' => $vendorSig,
                    'is_approved' => $fuelRequest->isVendorApproved(),
                ],
            ],
        ];
    }

    /**
     * Generate DomPDF instance for the fuel request.
     */
    public function generate(FuelRequest $fuelRequest): \Barryvdh\DomPDF\PDF
    {
        $data = $this->preparePdfData($fuelRequest);

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.fuel-request-sheet', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false, // Using Base64 data URIs for robust offline/sandboxed rendering
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 96,
        ]);

        return $pdf;
    }

    /**
     * Download the Fuel Request PDF as attachment.
     */
    public function download(FuelRequest $fuelRequest, ?string $filename = null): Response
    {
        $pdf = $this->generate($fuelRequest);
        $cleanNo = preg_replace('/[^A-Za-z0-9_\-]/', '-', $fuelRequest->request_no);
        $finalFilename = $filename ?: "Fuel-Request-{$cleanNo}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $finalFilename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Stream the Fuel Request PDF inline in the browser.
     */
    public function stream(FuelRequest $fuelRequest, ?string $filename = null): Response
    {
        $pdf = $this->generate($fuelRequest);
        $cleanNo = preg_replace('/[^A-Za-z0-9_\-]/', '-', $fuelRequest->request_no);
        $finalFilename = $filename ?: "Fuel-Request-{$cleanNo}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $finalFilename . '"',
        ]);
    }
}

