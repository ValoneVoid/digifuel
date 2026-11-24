<?php

namespace App\Services;

use App\Models\FuelRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteWhatsappService
{
    protected ?string $token;
    protected string $url;

    public function __construct(?string $token = null, ?string $url = null)
    {
        $this->token = $token ?: config('services.fonnte.token', env('FONNTE_TOKEN', '3N9fc1D8HAt5E5ZiauFw'));
        $this->url = $url ?: config('services.fonnte.url', 'https://api.fonnte.com/send');
    }

    /**
     * Send a WhatsApp message via Fonnte API.
     * Guaranteed non-blocking and safe: fails silently with logging, never throws exceptions to the caller.
     */
    public function sendMessage(?string $target, string $message): bool
    {
        if (empty($target) || empty(trim($target))) {
            Log::info('[Fonnte WA] No recipient phone provided; skipping notification.');
            return false;
        }

        // Clean phone number (strip whitespace, dashes, plus signs)
        $cleanTarget = preg_replace('/[^0-9]/', '', trim($target));

        if (empty($cleanTarget) || strlen($cleanTarget) < 9) {
            Log::warning("[Fonnte WA] Invalid target phone number: '{$target}'.");
            return false;
        }

        if (empty($this->token)) {
            Log::warning('[Fonnte WA] Token not configured; skipping notification.');
            return false;
        }

        // When running automated tests, skip real HTTP network calls unless explicitly enabled
        if (app()->environment('testing') && !config('services.fonnte.enable_in_tests', false)) {
            Log::info("[Fonnte WA - Test] Skipped network dispatch to {$cleanTarget}.");
            return true;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->asForm()
                ->post($this->url, [
                    'target' => $cleanTarget,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            if ($response->successful()) {
                Log::info("[Fonnte WA] Notification sent successfully to {$cleanTarget}.", [
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::warning("[Fonnte WA] Failed to send message to {$cleanTarget}. HTTP Status: {$response->status()}", [
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("[Fonnte WA] Exception while sending message to {$cleanTarget}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build direct deep-link URL to the Fuel Request detail/approval page.
     */
    protected function buildApprovalUrl(FuelRequest $fuelRequest): string
    {
        try {
            return route('approval.show', $fuelRequest->id);
        } catch (\Throwable $e) {
            return rtrim((string) config('app.url', ''), '/') . '/approval/' . $fuelRequest->id;
        }
    }

    /**
     * Notify SPV when a new fuel request is submitted by Driver / Employee.
     */
    public function notifyPendingSpv(FuelRequest $fuelRequest, User $driver): bool
    {
        $spv = $fuelRequest->spv;
        if (!$spv || empty($spv->phone)) {
            return false;
        }

        $unitCode = $fuelRequest->vehicle?->unit_code ?? $fuelRequest->unit_code ?? '-';
        $liters = number_format((float)$fuelRequest->total_liters, 0, ',', '.');
        $area = $fuelRequest->area ?? '-';
        $directUrl = $this->buildApprovalUrl($fuelRequest);

        $message = "*[FuelLink - Menunggu Approval SPV]*\n\n"
            . "Halo *{$spv->name}*,\n"
            . "Terdapat Pengajuan BBM baru yang memerlukan persetujuan SPV Anda:\n\n"
            . "• *No. Surat:* {$fuelRequest->request_no}\n"
            . "• *Pemohon:* {$driver->name}\n"
            . "• *Unit:* {$unitCode}\n"
            . "• *Jumlah BBM:* {$liters} Liter\n"
            . "• *Area:* {$area}\n\n"
            . "Silakan buka tautan berikut untuk memeriksa dan menyetujui pengajuan:\n"
            . "{$directUrl}\n\n"
            . "Terima kasih.";

        return $this->sendMessage($spv->phone, $message);
    }

    /**
     * Notify Dept Head / Manager when SPV has approved the fuel request.
     */
    public function notifyPendingDepthead(FuelRequest $fuelRequest, User $spvApprover): bool
    {
        $depthead = $fuelRequest->depthead ?: $fuelRequest->manager;
        if (!$depthead || empty($depthead->phone)) {
            return false;
        }

        $unitCode = $fuelRequest->vehicle?->unit_code ?? $fuelRequest->unit_code ?? '-';
        $liters = number_format((float)$fuelRequest->total_liters, 0, ',', '.');
        $driverName = $fuelRequest->user?->name ?? $fuelRequest->driver_name ?? 'Driver';
        $area = $fuelRequest->area ?? '-';
        $directUrl = $this->buildApprovalUrl($fuelRequest);

        $message = "*[FuelLink - Menunggu Approval Dept Head]*\n\n"
            . "Halo *{$depthead->name}*,\n"
            . "Pengajuan BBM berikut telah disetujui oleh SPV ({$spvApprover->name}) dan memerlukan persetujuan Dept Head / Manager Anda:\n\n"
            . "• *No. Surat:* {$fuelRequest->request_no}\n"
            . "• *Pemohon:* {$driverName}\n"
            . "• *Unit:* {$unitCode}\n"
            . "• *Jumlah BBM:* {$liters} Liter\n"
            . "• *Area:* {$area}\n\n"
            . "Silakan buka tautan berikut untuk memeriksa dan memberikan persetujuan:\n"
            . "{$directUrl}\n\n"
            . "Terima kasih.";

        return $this->sendMessage($depthead->phone, $message);
    }

    /**
     * Notify Vendor BBM when Dept Head has approved the fuel request (ready for fueling).
     */
    public function notifyReadyForVendor(FuelRequest $fuelRequest, User $deptheadApprover): bool
    {
        // Find target phone for vendor:
        // 1. vendorUser on FuelRequest
        // 2. Any active user with role vendor belonging to vendor_id
        // 3. vendorModel->phone
        $targetPhone = null;
        $vendorRecipientName = 'Rekan Vendor BBM';

        if ($fuelRequest->vendorUser && !empty($fuelRequest->vendorUser->phone)) {
            $targetPhone = $fuelRequest->vendorUser->phone;
            $vendorRecipientName = $fuelRequest->vendorUser->name;
        } elseif ($fuelRequest->vendor_id) {
            $vendorUser = User::where('vendor_id', $fuelRequest->vendor_id)
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->first();
            if ($vendorUser) {
                $targetPhone = $vendorUser->phone;
                $vendorRecipientName = $vendorUser->name;
            } elseif ($fuelRequest->vendorModel && !empty($fuelRequest->vendorModel->phone)) {
                $targetPhone = $fuelRequest->vendorModel->phone;
                $vendorRecipientName = $fuelRequest->vendorModel->contact_person ?: $fuelRequest->vendorModel->name;
            }
        }

        if (empty($targetPhone)) {
            return false;
        }

        $unitCode = $fuelRequest->vehicle?->unit_code ?? $fuelRequest->unit_code ?? '-';
        $liters = number_format((float)$fuelRequest->total_liters, 0, ',', '.');
        $vendorName = $fuelRequest->vendor_name ?? $fuelRequest->vendorModel?->name ?? 'Vendor BBM';
        $driverName = $fuelRequest->user?->name ?? $fuelRequest->driver_name ?? 'Driver';
        $directUrl = $this->buildApprovalUrl($fuelRequest);

        $message = "*[FuelLink - Siap Pengisian BBM / Approval Vendor]*\n\n"
            . "Halo *{$vendorRecipientName}* ({$vendorName}),\n"
            . "Pengajuan BBM berikut telah disetujui oleh Dept Head ({$deptheadApprover->name}) dan siap untuk diproses:\n\n"
            . "• *No. Surat:* {$fuelRequest->request_no}\n"
            . "• *Pemohon:* {$driverName}\n"
            . "• *Unit:* {$unitCode}\n"
            . "• *Jumlah BBM:* {$liters} Liter\n"
            . "• *Area:* " . ($fuelRequest->area ?? '-') . "\n\n"
            . "Silakan buka tautan berikut untuk memverifikasi dan mencatat realisasi pengisian BBM:\n"
            . "{$directUrl}\n\n"
            . "Terima kasih.";

        return $this->sendMessage($targetPhone, $message);
    }

    /**
     * Notify requesting Driver / Employee when Vendor has completed fueling realization
     * and awaits Final Verification (Final Approve).
     */
    public function notifyPendingUserVerification(FuelRequest $fuelRequest, User $vendorUser): bool
    {
        $requester = $fuelRequest->user;
        if (!$requester || empty($requester->phone)) {
            return false;
        }

        $unitCode = $fuelRequest->vehicle?->unit_code ?? $fuelRequest->unit_code ?? '-';
        $approvedLiters = number_format((float)$fuelRequest->total_liters, 1, ',', '.');
        $actualLiters = number_format((float)($fuelRequest->actual_liters ?? $fuelRequest->total_liters), 1, ',', '.');
        $vendorName = $fuelRequest->vendor_name ?? $vendorUser->name;
        $directUrl = $this->buildApprovalUrl($fuelRequest);

        $message = "*[FuelLink - Verifikasi Akhir Pengisian BBM]*\n\n"
            . "Halo *{$requester->name}*,\n"
            . "Vendor BBM (*{$vendorName}*) telah mencatat realisasi pengisian bahan bakar Anda:\n\n"
            . "• *No. Surat:* {$fuelRequest->request_no}\n"
            . "• *Unit:* {$unitCode}\n"
            . "• *Liter Disetujui:* {$approvedLiters} Liter\n"
            . "• *Liter Aktual Terisi:* {$actualLiters} Liter\n\n"
            . "Silakan buka tautan berikut untuk melakukan *Verifikasi Akhir (Final Approve)* bahwa pengisian telah sesuai:\n"
            . "{$directUrl}\n\n"
            . "Terima kasih.";

        return $this->sendMessage($requester->phone, $message);
    }
}
