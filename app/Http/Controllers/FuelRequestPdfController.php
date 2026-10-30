<?php

namespace App\Http\Controllers;

use App\Models\FuelRequest;
use App\Models\User;
use App\Policies\FuelRequestPolicy;
use App\Services\FuelRequestPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FuelRequestPdfController extends Controller
{
    protected function getActiveUser(): User
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        return $user;
    }

    /**
     * Download the official Fuel Request Sheet as a PDF attachment.
     */
    public function download(Request $request, $id, FuelRequestPdfService $pdfService, FuelRequestPolicy $policy): Response
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        // 1. Authorization & IDOR Protection via FuelRequestPolicy
        if (!$policy->printDocument($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses atau mengunduh dokumen Fuel Request ini.');
        }

        // 2. Business Workflow Validation: Only Vendor-Approved requests can generate the official PDF
        if (!$fuelRequest->isVendorApproved()) {
            abort(403, 'Dokumen PDF hanya dapat diunduh untuk pengajuan BBM yang telah disetujui Vendor (Approved - Ready for Fueling).');
        }

        // 3. Reject cancelled or rejected requests
        if ($fuelRequest->overall_status === 'ditolak' || $fuelRequest->current_stage === 'cancelled') {
            abort(403, 'Dokumen pengajuan yang ditolak atau dibatalkan tidak dapat diunduh sebagai lembar pengajuan resmi.');
        }

        try {
            return $pdfService->download($fuelRequest);
        } catch (\Throwable $e) {
            Log::error("Gagal melakukan generate PDF Fuel Request #{$fuelRequest->id}: " . $e->getMessage(), [
                'fuel_request_id' => $fuelRequest->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Terjadi kendala saat memproses pembuatan dokumen PDF. Silakan hubungi Administrator.');
        }
    }

    /**
     * Stream the official Fuel Request Sheet PDF inline in the browser tab.
     */
    public function stream(Request $request, $id, FuelRequestPdfService $pdfService, FuelRequestPolicy $policy): Response
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        // 1. Authorization & IDOR Protection via FuelRequestPolicy
        if (!$policy->printDocument($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses dokumen Fuel Request ini.');
        }

        // 2. Business Workflow Validation: Only Vendor-Approved requests can generate the official PDF
        if (!$fuelRequest->isVendorApproved()) {
            abort(403, 'Dokumen PDF hanya dapat diakses untuk pengajuan BBM yang telah disetujui Vendor (Approved - Ready for Fueling).');
        }

        // 3. Reject cancelled or rejected requests
        if ($fuelRequest->overall_status === 'ditolak' || $fuelRequest->current_stage === 'cancelled') {
            abort(403, 'Dokumen pengajuan yang ditolak atau dibatalkan tidak dapat diakses sebagai lembar pengajuan resmi.');
        }

        try {
            return $pdfService->stream($fuelRequest);
        } catch (\Throwable $e) {
            Log::error("Gagal melakukan streaming PDF Fuel Request #{$fuelRequest->id}: " . $e->getMessage(), [
                'fuel_request_id' => $fuelRequest->id,
                'user_id' => $user->id,
            ]);

            abort(500, 'Terjadi kendala saat memproses tampilan dokumen PDF. Silakan hubungi Administrator.');
        }
    }
}

