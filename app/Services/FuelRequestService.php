<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\FuelRequest;
use App\Models\FuelRequestApproval;
use App\Models\FuelRequestHistory;
use App\Models\FuelTransaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Vehicle;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuelRequestService
{
    public const DEFAULT_DOCUMENT_COMPANY = 'PT UNITED TRACTORS Tbk - SITE SATUI';

    /**
     * Create a new Fuel Request (either as Draft or Submitted to Pending SPV)
     */
    public function createRequest(User $user, array $data, bool $isDraft = false): FuelRequest
    {
        return DB::transaction(function () use ($user, $data, $isDraft) {
            $vehicle = Vehicle::where('unit_code', $data['unit_code'])
                ->where('is_active', true)
                ->first();

            if (!$vehicle) {
                throw ValidationException::withMessages([
                    'unit_code' => 'Unit Code tidak ditemukan atau sudah tidak aktif.',
                ]);
            }

            $area = Area::where('name', $data['area'])->where('is_active', true)->first();
            $vendor = Vendor::where('name', $data['vendor_name'])->where('is_active', true)->first();

            $spv = User::findOrFail($data['spv_id']);
            $depthead = User::findOrFail($data['depthead_id']);
            $fuelman = !empty($data['fuelman_id']) ? User::find($data['fuelman_id']) : null;

            // Find dedicated Vendor user for this vendor company (if any)
            $vendorUser = null;
            if ($vendor) {
                $vendorUser = User::where('vendor_id', $vendor->id)->where('is_active', true)->first();
            }

            // Generate collision-safe unique request_no
            $datePrefix = date('Y-m', strtotime($data['request_date'] ?? 'now'));
            $latestRequest = FuelRequest::where('request_no', 'like', "FR-{$datePrefix}-%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $nextSeq = 1;
            if ($latestRequest && preg_match('/-(\d+)$/', $latestRequest->request_no, $m)) {
                $nextSeq = ((int)$m[1]) + 1;
            }
            $requestNo = sprintf('FR-%s-%03d', $datePrefix, $nextSeq);
            while (FuelRequest::where('request_no', $requestNo)->exists()) {
                $nextSeq++;
                $requestNo = sprintf('FR-%s-%03d', $datePrefix, $nextSeq);
            }

            $employee = $user->employee;
            $sourceCompany = $employee ? $employee->company : ($user->company ?? self::DEFAULT_DOCUMENT_COMPANY);

            $initialStage = $isDraft ? 'draft' : 'pending_spv';
            $initialSpvStatus = $isDraft ? 'draft' : 'menunggu';
            $initialOverallStatus = $isDraft ? 'draft' : 'menunggu';

            $fuelRequest = FuelRequest::create([
                'request_no' => $requestNo,
                'user_id' => $user->id,
                'employee_id' => $employee?->id,
                'driver_name' => $employee?->name ?? $user->name,
                'driver_nrp' => $employee?->nrp ?? $user->nrp,
                'driver_position' => $employee?->position ?? $user->jabatan,
                'driver_department' => $employee?->department ?? 'Operational Site Satui',
                'source_company' => $sourceCompany,
                'company' => self::DEFAULT_DOCUMENT_COMPANY,
                'request_date' => $data['request_date'],
                'planned_fuel_time' => $data['planned_fuel_time'] ?? $data['request_date'],
                'unit_code' => $vehicle->unit_code,
                'vehicle_id' => $vehicle->id,
                'police_no' => $vehicle->police_no,
                'unit_type' => $vehicle->unit_type ?? $vehicle->model,
                'hm_km' => (string)$data['hm_km'],
                'meter_type' => $vehicle->meter_type ?? 'km',
                'total_liters' => $data['total_liters'],
                'area' => $area ? $area->name : $data['area'],
                'area_id' => $area?->id,
                'notes' => $data['notes'] ?? null,
                'spv_id' => $spv->id,
                'spv_name' => 'Approval By ' . ($spv->nrp ?? $spv->username) . ' - ' . $spv->name,
                'manager_id' => $depthead->id,
                'manager_name' => 'Approval By ' . ($depthead->username ?? $depthead->name),
                'depthead_id' => $depthead->id,
                'depthead_name' => 'Approval By ' . ($depthead->username ?? $depthead->name),
                'vendor_id' => $vendor?->id,
                'vendor_user_id' => $vendorUser?->id,
                'vendor_name' => $vendor ? $vendor->name : $data['vendor_name'],
                'fuelman_id' => $fuelman?->id,
                'fuelman_name' => $fuelman ? 'Approval By ' . $fuelman->name : null,
                'status_spv' => $initialSpvStatus,
                'status_manager' => '-',
                'status_depthead' => '-',
                'status_vendor' => '-',
                'status_fuelman' => '-',
                'overall_status' => $initialOverallStatus,
                'current_stage' => $initialStage,
                'driver_signature' => $user->signature_url,
            ]);

            FuelRequestHistory::record(
                $fuelRequest->id,
                $user->id,
                $isDraft ? 'draft_saved' : 'created',
                null,
                $initialStage,
                $isDraft
                    ? "Fuel Request {$requestNo} disimpan sebagai Draft oleh {$user->name}."
                    : "Fuel Request {$requestNo} diajukan oleh {$user->name} untuk unit {$vehicle->unit_code} ({$data['total_liters']} Liter) dan menunggu persetujuan SPV."
            );

            if (!$isDraft) {
                UserNotification::notify(
                    $spv->id,
                    $fuelRequest->id,
                    "Pengajuan Baru: {$requestNo}",
                    "Fuel Request {$requestNo} dari {$user->name} ({$vehicle->unit_code} - {$data['total_liters']} L) menunggu persetujuan SPV Anda.",
                    'warning'
                );
            }

            return $fuelRequest;
        });
    }

    /**
     * Submit an existing draft Fuel Request to Pending SPV
     */
    public function submitDraft(FuelRequest $fuelRequest, User $user): FuelRequest
    {
        return DB::transaction(function () use ($fuelRequest, $user) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);
            if ($locked->current_stage !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Hanya pengajuan berstatus Draft yang dapat diajukan.']);
            }

            $locked->update([
                'status_spv' => 'menunggu',
                'overall_status' => 'menunggu',
                'current_stage' => 'pending_spv',
                'request_date' => now(),
                'driver_signature' => $user->signature_url ?? $locked->driver_signature,
            ]);

            FuelRequestHistory::record(
                $locked->id,
                $user->id,
                'submitted',
                'draft',
                'pending_spv',
                "Draft Fuel Request {$locked->request_no} resmi diajukan dan menunggu persetujuan SPV."
            );

            UserNotification::notify(
                $locked->spv_id,
                $locked->id,
                "Pengajuan Baru: {$locked->request_no}",
                "Fuel Request {$locked->request_no} menunggu persetujuan SPV Anda.",
                'warning'
            );

            return $locked;
        });
    }

    /**
     * Process sequential approval:
     * Stage 1: SPV/Dept Head (pending_spv -> pending_manager)
     * Stage 2: Dept Head/Manager (pending_manager -> pending_vendor)
     * Stage 3: Vendor (pending_vendor -> approved [Ready for Fueling])
     */
    public function approve(FuelRequest $fuelRequest, User $user, ?string $note = null): array
    {
        return DB::transaction(function () use ($fuelRequest, $user, $note) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            if (in_array($locked->overall_status, ['ditolak', 'cancelled']) || in_array($locked->current_stage, ['rejected', 'cancelled', 'completed'])) {
                return ['ok' => false, 'message' => 'Pengajuan ini sudah ditolak, dibatalkan, atau selesai dan tidak dapat disetujui kembali.'];
            }

            $sig = $user->signature_url;

            // STAGE 1: SPV / Dept Head
            if ($locked->status_spv === 'menunggu' && ($user->isSpv() || $user->isAdmin())) {
                if (!$user->isAdmin() && $locked->spv_id && (int)$locked->spv_id !== (int)$user->id) {
                    return ['ok' => false, 'message' => 'Anda bukan SPV yang ditugaskan pada pengajuan ini.'];
                }

                $fromStage = $locked->current_stage;
                $locked->update([
                    'status_spv' => 'disetujui',
                    'spv_action_at' => now(),
                    'spv_note' => $note ?: 'Disetujui oleh SPV.',
                    'spv_signature' => $sig ?: 'images/sig_spv.png',
                    'status_depthead' => 'menunggu',
                    'status_manager' => 'menunggu',
                    'current_stage' => 'pending_manager',
                ]);

                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'spv',
                    'status' => 'approved',
                    'note' => $note ?: 'Disetujui oleh SPV.',
                    'signature_path' => $sig ?: 'images/sig_spv.png',
                    'action_at' => now(),
                ]);

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'approved_spv',
                    $fromStage,
                    'pending_manager',
                    "Disetujui oleh SPV ({$user->name}). Diteruskan ke Dept Head / Manager." . ($note ? " Catatan: {$note}" : '')
                );

                UserNotification::notify(
                    $locked->depthead_id ?? $locked->manager_id,
                    $locked->id,
                    "Menunggu Approval Manager: {$locked->request_no}",
                    "Pengajuan {$locked->request_no} telah disetujui SPV dan menunggu persetujuan Dept Head/Manager Anda.",
                    'warning'
                );

                return [
                    'ok' => true,
                    'message' => "Pengajuan {$locked->request_no} berhasil disetujui pada Tahap 1 (SPV) dan diteruskan ke Dept Head/Manager!",
                ];
            }

            // STAGE 2: Dept Head / Manager (Must have SPV approved first!)
            if ($locked->status_spv === 'disetujui' && $locked->status_depthead !== 'disetujui' && ($user->isManager() || $user->isAdmin())) {
                if (!$user->isAdmin() && $locked->depthead_id && (int)$locked->depthead_id !== (int)$user->id) {
                    return ['ok' => false, 'message' => 'Anda bukan Dept Head/Manager yang ditugaskan pada pengajuan ini.'];
                }

                $fromStage = $locked->current_stage;
                $locked->update([
                    'status_depthead' => 'disetujui',
                    'depthead_action_at' => now(),
                    'depthead_note' => $note ?: 'Disetujui oleh Dept Head/Manager.',
                    'depthead_signature' => $sig ?: 'images/sig_depthead.png',
                    'status_manager' => 'disetujui',
                    'manager_action_at' => now(),
                    'manager_note' => $note ?: 'Disetujui oleh Dept Head/Manager.',
                    'status_vendor' => 'menunggu',
                    'status_fuelman' => 'menunggu',
                    'current_stage' => 'pending_vendor',
                ]);

                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'manager',
                    'status' => 'approved',
                    'note' => $note ?: 'Disetujui oleh Dept Head/Manager.',
                    'signature_path' => $sig ?: 'images/sig_depthead.png',
                    'action_at' => now(),
                ]);

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'approved_manager',
                    $fromStage,
                    'pending_vendor',
                    "Disetujui oleh Dept Head/Manager ({$user->name}). Diteruskan ke Vendor untuk persetujuan akhir sebelum pengisian." . ($note ? " Catatan: {$note}" : '')
                );

                if ($locked->vendor_user_id) {
                    UserNotification::notify(
                        $locked->vendor_user_id,
                        $locked->id,
                        "Menunggu Approval Vendor: {$locked->request_no}",
                        "Pengajuan {$locked->request_no} telah disetujui Manager dan menunggu persetujuan Vendor ({$locked->vendor_name}).",
                        'warning'
                    );
                }

                return [
                    'ok' => true,
                    'message' => "Pengajuan {$locked->request_no} berhasil disetujui pada Tahap 2 (Dept Head/Manager) dan diteruskan ke Vendor!",
                ];
            }

            // STAGE 3: Vendor (Final approval before fueling - must have SPV and Manager approved first!)
            if (
                $locked->status_spv === 'disetujui'
                && $locked->status_depthead === 'disetujui'
                && $locked->status_vendor !== 'disetujui'
                && ($user->isVendor() || $user->isFuelman() || $user->isAdmin())
            ) {
                // Verify vendor isolation if user is a dedicated Vendor account
                if ($user->isVendor() && !$user->isAdmin() && $user->vendor_id && $locked->vendor_id && (int)$user->vendor_id !== (int)$locked->vendor_id) {
                    return ['ok' => false, 'message' => 'Anda hanya dapat menyetujui pengajuan yang ditujukan untuk perusahaan Vendor Anda.'];
                }

                $fromStage = $locked->current_stage;
                $locked->update([
                    'status_vendor' => 'disetujui',
                    'vendor_action_at' => now(),
                    'vendor_note' => $note ?: 'Disetujui oleh Vendor - Siap Pengisian BBM.',
                    'vendor_signature' => $sig ?: 'images/sig_fuelman.png',
                    'status_fuelman' => 'disetujui',
                    'fuelman_action_at' => now(),
                    'fuelman_note' => $note ?: 'Disetujui Vendor - Siap Pengisian.',
                    'fuelman_signature' => $sig ?: 'images/sig_fuelman.png',
                    'overall_status' => 'disetujui',
                    'current_stage' => 'approved', // Approved - Ready for Fueling
                ]);

                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'vendor',
                    'status' => 'approved',
                    'note' => $note ?: 'Disetujui oleh Vendor - Siap Pengisian BBM.',
                    'signature_path' => $sig ?: 'images/sig_fuelman.png',
                    'action_at' => now(),
                ]);

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'approved_vendor',
                    $fromStage,
                    'approved',
                    "Disetujui oleh Vendor ({$user->name} - {$locked->vendor_name}). Status: Approved - Ready for Fueling. Surat Fuel Request siap dicetak & siap diisi oleh Fuel Man." . ($note ? " Catatan: {$note}" : '')
                );

                UserNotification::notify(
                    $locked->user_id,
                    $locked->id,
                    "Fuel Request Disetujui: {$locked->request_no}",
                    "Pengajuan {$locked->request_no} telah memperoleh persetujuan Vendor. Surat Fuel Request kini dapat dicetak.",
                    'success'
                );

                if ($locked->fuelman_id) {
                    UserNotification::notify(
                        $locked->fuelman_id,
                        $locked->id,
                        "Siap Pengisian BBM: {$locked->request_no}",
                        "Pengajuan {$locked->request_no} ({$locked->unit_code} - {$locked->total_liters} L) siap dilakukan pengisian BBM aktual.",
                        'info'
                    );
                }

                return [
                    'ok' => true,
                    'message' => "Pengajuan {$locked->request_no} telah disetujui oleh Vendor! Status kini Approved - Ready for Fueling dan surat Fuel Request siap dicetak.",
                ];
            }

            return [
                'ok' => false,
                'message' => 'Tahap persetujuan tidak sesuai dengan role Anda atau urutan persetujuan sebelumnya belum selesai.',
            ];
        });
    }

    /**
     * Reject a Fuel Request at the active stage
     */
    public function reject(FuelRequest $fuelRequest, User $user, string $note): array
    {
        return DB::transaction(function () use ($fuelRequest, $user, $note) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            if (in_array($locked->overall_status, ['ditolak', 'cancelled']) || $locked->current_stage === 'completed') {
                return ['ok' => false, 'message' => 'Pengajuan ini sudah tidak dapat ditolak.'];
            }

            $fromStage = $locked->current_stage;
            $stage = 'spv';

            if ($locked->status_spv === 'menunggu' && ($user->isSpv() || $user->isAdmin())) {
                $stage = 'spv';
                $locked->update([
                    'status_spv' => 'ditolak',
                    'spv_action_at' => now(),
                    'spv_note' => $note,
                    'overall_status' => 'ditolak',
                    'current_stage' => 'rejected',
                ]);
            } elseif ($locked->status_spv === 'disetujui' && $locked->status_depthead !== 'disetujui' && ($user->isManager() || $user->isAdmin())) {
                $stage = 'manager';
                $locked->update([
                    'status_depthead' => 'ditolak',
                    'depthead_action_at' => now(),
                    'depthead_note' => $note,
                    'status_manager' => 'ditolak',
                    'manager_action_at' => now(),
                    'manager_note' => $note,
                    'overall_status' => 'ditolak',
                    'current_stage' => 'rejected',
                ]);
            } elseif ($locked->status_spv === 'disetujui' && $locked->status_depthead === 'disetujui' && ($user->isVendor() || $user->isFuelman() || $user->isAdmin())) {
                $stage = 'vendor';
                $locked->update([
                    'status_vendor' => 'ditolak',
                    'vendor_action_at' => now(),
                    'vendor_note' => $note,
                    'status_fuelman' => 'ditolak',
                    'fuelman_action_at' => now(),
                    'fuelman_note' => $note,
                    'overall_status' => 'ditolak',
                    'current_stage' => 'rejected',
                ]);
            } else {
                return ['ok' => false, 'message' => 'Anda tidak memiliki wewenang untuk menolak pengajuan pada tahap ini.'];
            }

            FuelRequestApproval::create([
                'fuel_request_id' => $locked->id,
                'approver_id' => $user->id,
                'stage' => $stage,
                'status' => 'rejected',
                'note' => $note,
                'signature_path' => $user->signature_url,
                'action_at' => now(),
            ]);

            FuelRequestHistory::record(
                $locked->id,
                $user->id,
                'rejected',
                $fromStage,
                'rejected',
                "Pengajuan ditolak oleh {$user->name} (" . strtoupper($stage) . "). Alasan: {$note}"
            );

            UserNotification::notify(
                $locked->user_id,
                $locked->id,
                "Pengajuan Ditolak: {$locked->request_no}",
                "Pengajuan {$locked->request_no} ditolak oleh {$user->name}. Alasan: {$note}",
                'danger'
            );

            return [
                'ok' => true,
                'message' => "Pengajuan {$locked->request_no} telah ditolak.",
            ];
        });
    }

    /**
     * Record actual fueling realization by Fuel Man (Tahap 5)
     * Enforces that Vendor approval is completed first.
     */
    public function recordRealization(FuelRequest $fuelRequest, User $user, array $data): FuelTransaction
    {
        return DB::transaction(function () use ($fuelRequest, $user, $data) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            if (!$locked->isVendorApproved()) {
                throw ValidationException::withMessages([
                    'status' => 'Pengisian BBM tidak dapat dicatat sebelum pengajuan memperoleh persetujuan Vendor.',
                ]);
            }

            $requestedLiters = (float)$locked->total_liters;
            $actualLiters = (float)$data['actual_liters'];
            $varianceLiters = round($actualLiters - $requestedLiters, 2);
            $meterAtFueling = (float)$data['meter_at_fueling'];
            $fueledAt = $data['fueled_at'] ?? now();

            $trxNo = sprintf('TRX-%s-%03d', date('Ymd', strtotime($fueledAt)), FuelTransaction::count() + 1);
            while (FuelTransaction::where('transaction_no', $trxNo)->exists()) {
                $trxNo = sprintf('TRX-%s-%04d', date('Ymd', strtotime($fueledAt)), rand(1000, 9999));
            }

            $signature = $user->signature_url ?? $locked->fuelman_signature ?? 'images/sig_fuelman.png';

            $transaction = FuelTransaction::create([
                'transaction_no' => $trxNo,
                'fuel_request_id' => $locked->id,
                'fuelman_id' => $user->id,
                'requested_liters' => $requestedLiters,
                'actual_liters' => $actualLiters,
                'variance_liters' => $varianceLiters,
                'meter_at_fueling' => $meterAtFueling,
                'fueled_at' => $fueledAt,
                'fuelman_signature' => $signature,
                'notes' => $data['notes'] ?? null,
            ]);

            $fromStage = $locked->current_stage;
            $locked->update([
                'actual_liters' => $actualLiters,
                'actual_hm_km' => $meterAtFueling,
                'fueled_at' => $fueledAt,
                'status_fuelman' => 'disetujui',
                'fuelman_action_at' => now(),
                'fuelman_signature' => $signature,
                'fuelman_note' => $data['notes'] ?? "Realisasi pengisian tercatat: {$actualLiters} Liter pada HM/KM {$meterAtFueling}.",
                'overall_status' => 'disetujui',
                'current_stage' => 'completed',
            ]);

            $diffText = $varianceLiters == 0
                ? 'Sesuai jumlah disetujui'
                : ($varianceLiters > 0 ? "+{$varianceLiters} L dari yang disetujui" : "{$varianceLiters} L dari yang disetujui");

            FuelRequestHistory::record(
                $locked->id,
                $user->id,
                'completed',
                $fromStage,
                'completed',
                "Realisasi pengisian BBM dicatat oleh Fuel Man ({$user->name}): {$actualLiters} L ({$diffText}), HM/KM Aktual: {$meterAtFueling}, Nomor Transaksi: {$trxNo}."
            );

            UserNotification::notify(
                $locked->user_id,
                $locked->id,
                "Pengisian BBM Selesai: {$locked->request_no}",
                "Realisasi pengisian BBM untuk {$locked->request_no} telah selesai dicatat ({$actualLiters} Liter).",
                'success'
            );

            return $transaction;
        });
    }
}
