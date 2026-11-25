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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuelRequestService
{
    public const DEFAULT_DOCUMENT_COMPANY = 'PT UNITED TRACTORS Tbk - SITE SATUI';

    protected FonnteWhatsappService $whatsappService;

    public function __construct(?FonnteWhatsappService $whatsappService = null)
    {
        $this->whatsappService = $whatsappService ?? app(FonnteWhatsappService::class);
    }

    /**
     * Create a new Fuel Request (either as Draft or Submitted to Pending SPV)
     */
    public function createRequest(User $user, array $data, bool $isDraft = false): FuelRequest
    {
        return DB::transaction(function () use ($user, $data, $isDraft) {
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'user_id' => 'Akun pengguna tidak aktif dan tidak dapat mengajukan permintaan BBM baru.',
                ]);
            }

            if ($user->employee_id) {
                $empCheck = $user->employee()->withTrashed()->first();
                if (!$empCheck || !$empCheck->is_active || $empCheck->trashed()) {
                    throw ValidationException::withMessages([
                        'employee_id' => 'Data karyawan tidak aktif dan tidak dapat digunakan untuk pengajuan baru.',
                    ]);
                }
            }

            $vehicle = Vehicle::where('unit_code', $data['unit_code'])
                ->where('is_active', true)
                ->first();

            if (!$vehicle) {
                throw ValidationException::withMessages([
                    'unit_code' => 'Unit Code tidak ditemukan atau sudah tidak aktif.',
                ]);
            }

            $area = Area::where('name', $data['area'])->where('is_active', true)->first();
            if (!$area) {
                throw ValidationException::withMessages([
                    'area' => 'Area operasional tidak ditemukan atau sudah tidak aktif.',
                ]);
            }

            $isSpbu = strtoupper(trim((string) ($data['vendor_name'] ?? ''))) === 'SPBU';
            $vendor = null;

            if ($isSpbu) {
                if (!$user->canUseSpbu()) {
                    throw ValidationException::withMessages([
                        'vendor_name' => 'Opsi pengisian SPBU hanya dapat digunakan oleh Dept Head atau Site Manager.',
                    ]);
                }
            } else {
                $vendor = Vendor::where('name', $data['vendor_name'])->where('is_active', true)->first();
                if (!$vendor) {
                    throw ValidationException::withMessages([
                        'vendor_name' => 'Vendor pengisian tidak ditemukan atau sudah tidak aktif.',
                    ]);
                }
            }

            $spv = null;
            $isDeptheadSubmitter = $user->isDepthead() || $isSpbu;

            if (!empty($data['spv_id'])) {
                $spv = User::where('id', $data['spv_id'])->where('is_active', true)->first();
            }
            if (!$isDeptheadSubmitter && !$spv) {
                throw ValidationException::withMessages([
                    'spv_id' => 'Approver SPV tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            $depthead = null;
            if ($isDeptheadSubmitter) {
                // Dept Head / SM submitter is their own approver (or use selected approver if specified)
                $depthead = !empty($data['depthead_id'])
                    ? (User::where('id', $data['depthead_id'])->where('is_active', true)->first() ?: $user)
                    : $user;
            } else {
                $depthead = !empty($data['depthead_id']) ? User::where('id', $data['depthead_id'])->where('is_active', true)->first() : null;
                if (!$depthead) {
                    throw ValidationException::withMessages([
                        'depthead_id' => 'Approver Dept Head/Manager tidak ditemukan atau sedang nonaktif.',
                    ]);
                }
            }

            $fuelman = !empty($data['fuelman_id'])
                ? User::where('id', $data['fuelman_id'])->where('is_active', true)->first()
                : null;
            if (!empty($data['fuelman_id']) && !$fuelman) {
                throw ValidationException::withMessages([
                    'fuelman_id' => 'Fuel Man tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            if (!$isDraft && !$user->hasActiveSignature()) {
                throw ValidationException::withMessages([
                    'signature' => 'Anda wajib mengunggah tanda tangan digital di menu Profil sebelum dapat mengajukan Fuel Request.',
                ]);
            }

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

            $statusVendor = '-';
            $statusFuelman = '-';
            $vendorActionAt = null;
            $vendorNote = null;
            $actualLiters = null;
            $actualHmKm = null;
            $fueledAt = null;
            $userVerifiedAt = null;
            $userVerificationNote = null;

            if ($isDraft) {
                $initialStage = 'draft';
                $initialSpvStatus = 'draft';
                $initialDeptheadStatus = 'draft';
                $initialManagerStatus = 'draft';
                $initialOverallStatus = 'draft';
                $spvActionAt = null;
                $spvNote = null;
                $deptheadActionAt = null;
                $deptheadNote = null;
                $deptheadSig = null;
            } elseif ($isSpbu) {
                // FULL BYPASS SPBU (Khusus Dept Head / Site Manager): Skip SPV, Dept Head, dan Vendor -> Langsung Completed
                $initialStage = 'completed';
                $initialSpvStatus = 'disetujui';
                $initialDeptheadStatus = 'disetujui';
                $initialManagerStatus = 'disetujui';
                $statusVendor = 'disetujui';
                $statusFuelman = 'disetujui';
                $initialOverallStatus = 'disetujui';
                $spvActionAt = now();
                $spvNote = 'Bypassed (Pengisian SPBU oleh Dept Head/SM)';
                $deptheadActionAt = now();
                $deptheadNote = 'Disetujui otomatis — Pengisian SPBU (' . ($data['spbu_location'] ?? 'SPBU') . ')';
                $deptheadSig = $user->signature_url;
                $vendorActionAt = now();
                $vendorNote = 'Bypassed — Pengisian Langsung di SPBU (' . ($data['spbu_location'] ?? 'SPBU') . ')';
                $actualLiters = (float) $data['total_liters'];
                $actualHmKm = (float) $data['hm_km'];
                $fueledAt = Carbon::parse($data['request_date']);
                $userVerifiedAt = now();
                $userVerificationNote = 'Terverifikasi otomatis (Pengisian SPBU oleh Dept Head/SM dengan bukti nota).';
            } elseif ($isDeptheadSubmitter) {
                // OPTION B: Auto-approved by Dept Head submitter -> bypass SPV, jump to pending_vendor
                $initialStage = 'pending_vendor';
                $initialSpvStatus = 'disetujui';
                $initialDeptheadStatus = 'disetujui';
                $initialManagerStatus = 'disetujui';
                $statusVendor = 'menunggu';
                $statusFuelman = 'menunggu';
                $initialOverallStatus = 'menunggu';
                $spvActionAt = now();
                $spvNote = 'Bypassed (Pengajuan langsung oleh Dept Head)';
                $deptheadActionAt = now();
                $deptheadNote = 'Disetujui otomatis (Pengajuan oleh Dept Head)';
                $deptheadSig = $user->signature_url;
            } else {
                $initialStage = 'pending_spv';
                $initialSpvStatus = 'menunggu';
                $initialDeptheadStatus = '-';
                $initialManagerStatus = '-';
                $initialOverallStatus = 'menunggu';
                $spvActionAt = null;
                $spvNote = null;
                $deptheadActionAt = null;
                $deptheadNote = null;
                $deptheadSig = null;
            }

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
                'unit_vendor_id' => $vehicle->unit_vendor_id,
                'police_no' => $vehicle->police_no,
                'unit_type' => $vehicle->unit_type ?? $vehicle->model,
                'hm_km' => (string)$data['hm_km'],
                'meter_type' => $vehicle->meter_type ?? 'km',
                'total_liters' => $data['total_liters'],
                'actual_liters' => $actualLiters,
                'actual_hm_km' => $actualHmKm,
                'fueled_at' => $fueledAt,
                'user_verified_at' => $userVerifiedAt,
                'user_verification_note' => $userVerificationNote,
                'area' => $area ? $area->name : $data['area'],
                'area_id' => $area?->id,
                'support_point_id' => !empty($data['support_point_id']) ? $data['support_point_id'] : null,
                'notes' => $data['notes'] ?? null,
                'spv_id' => $spv?->id,
                'spv_name' => $spv ? ('Approval By ' . ($spv->nrp ?? $spv->username) . ' - ' . $spv->name) : ($isSpbu ? 'Bypassed (SPBU)' : 'Bypassed (Dept Head)'),
                'spv_action_at' => $spvActionAt,
                'spv_note' => $spvNote,
                'manager_id' => $depthead->id,
                'manager_name' => 'Approval By ' . ($depthead->username ?? $depthead->name),
                'manager_action_at' => $deptheadActionAt,
                'manager_note' => $deptheadNote,
                'depthead_id' => $depthead->id,
                'depthead_name' => 'Approval By ' . ($depthead->username ?? $depthead->name),
                'depthead_action_at' => $deptheadActionAt,
                'depthead_note' => $deptheadNote,
                'depthead_signature' => $deptheadSig,
                'vendor_id' => $vendor?->id,
                'vendor_user_id' => $vendorUser?->id,
                'vendor_name' => $isSpbu ? 'SPBU' : ($vendor ? $vendor->name : $data['vendor_name']),
                'is_spbu' => $isSpbu,
                'spbu_location' => $isSpbu ? ($data['spbu_location'] ?? null) : null,
                'spbu_price_per_liter' => $isSpbu ? ($data['spbu_price_per_liter'] ?? null) : null,
                'spbu_receipt_photo' => $isSpbu ? ($data['spbu_receipt_photo'] ?? null) : null,
                'vendor_action_at' => $vendorActionAt,
                'vendor_note' => $vendorNote,
                'fuelman_id' => $fuelman?->id,
                'fuelman_name' => $fuelman ? 'Approval By ' . $fuelman->name : ($isSpbu ? 'SPBU' : null),
                'fuelman_action_at' => $vendorActionAt,
                'fuelman_note' => $vendorNote,
                'status_spv' => $initialSpvStatus,
                'status_manager' => $initialManagerStatus,
                'status_depthead' => $initialDeptheadStatus,
                'status_vendor' => $statusVendor,
                'status_fuelman' => $statusFuelman,
                'overall_status' => $initialOverallStatus,
                'current_stage' => $initialStage,
                'driver_signature' => $user->signature_url,
            ]);

            if ($isSpbu && !$isDraft) {
                FuelRequestApproval::create([
                    'fuel_request_id' => $fuelRequest->id,
                    'approver_id' => $user->id,
                    'stage' => 'manager',
                    'status' => 'approved',
                    'note' => 'Full Approved otomatis — Pengisian SPBU oleh Dept Head/SM.',
                    'signature_path' => $user->signature_url,
                    'action_at' => now(),
                ]);

                // Auto-create FuelTransaction so SPBU enters Evaluasi BBM & Excel Export
                $trxNo = sprintf('TRX-SPBU-%s-%03d', $fueledAt->format('Ymd'), FuelTransaction::count() + 1);
                while (FuelTransaction::where('transaction_no', $trxNo)->exists()) {
                    $trxNo = sprintf('TRX-SPBU-%s-%04d', $fueledAt->format('Ymd'), rand(1000, 9999));
                }

                FuelTransaction::create([
                    'transaction_no' => $trxNo,
                    'fuel_request_id' => $fuelRequest->id,
                    'fuelman_id' => $user->id,
                    'requested_liters' => (float) $data['total_liters'],
                    'actual_liters' => (float) $data['total_liters'],
                    'variance_liters' => 0,
                    'meter_at_fueling' => (float) $data['hm_km'],
                    'fueled_at' => $fueledAt,
                    'fuelman_signature' => $user->signature_url,
                    'notes' => 'Pengisian SPBU: ' . ($data['spbu_location'] ?? 'SPBU') . ' | Harga: Rp ' . number_format((float)($data['spbu_price_per_liter'] ?? 0), 0, ',', '.') . '/L',
                ]);
            } elseif ($isDeptheadSubmitter && !$isDraft) {
                FuelRequestApproval::create([
                    'fuel_request_id' => $fuelRequest->id,
                    'approver_id' => $user->id,
                    'stage' => 'manager',
                    'status' => 'approved',
                    'note' => 'Disetujui otomatis saat pengajuan dibuat oleh Dept Head.',
                    'signature_path' => $user->signature_url,
                    'action_at' => now(),
                ]);
            }

            $historyDescription = $isDraft
                ? "Fuel Request {$requestNo} disimpan sebagai Draft oleh {$user->name}."
                : ($isSpbu
                    ? "Pengisian SPBU {$requestNo} oleh {$user->name} untuk unit {$vehicle->unit_code} ({$data['total_liters']} L @ Rp " . number_format((float)($data['spbu_price_per_liter'] ?? 0), 0, ',', '.') . "/L di " . ($data['spbu_location'] ?? 'SPBU') . "). Full Approved & Selesai otomatis (Bypass SPV, Dept Head, dan Vendor)."
                    : ($isDeptheadSubmitter
                        ? "Fuel Request {$requestNo} diajukan oleh Dept Head ({$user->name}) untuk unit {$vehicle->unit_code} ({$data['total_liters']} Liter). Verifikasi SPV di-bypass dan otomatis disetujui Dept Head. Menunggu Persetujuan & Realisasi Vendor."
                        : "Fuel Request {$requestNo} diajukan oleh {$user->name} untuk unit {$vehicle->unit_code} ({$data['total_liters']} Liter) dan menunggu persetujuan SPV."
                    )
                );

            FuelRequestHistory::record(
                $fuelRequest->id,
                $user->id,
                $isDraft ? 'draft_saved' : ($isSpbu ? 'spbu_completed' : ($isDeptheadSubmitter ? 'approved_manager' : 'created')),
                null,
                $initialStage,
                $historyDescription
            );

            if (!$isDraft && !$isSpbu) {
                if ($isDeptheadSubmitter) {
                    if ($vendorUser) {
                        UserNotification::notify(
                            $vendorUser->id,
                            $fuelRequest->id,
                            "Menunggu Approval Vendor: {$requestNo}",
                            "Fuel Request {$requestNo} dari Dept Head {$user->name} ({$vehicle->unit_code} - {$data['total_liters']} L) telah disetujui dan siap untuk realisasi pengisian BBM.",
                            'warning'
                        );
                    }
                    $this->whatsappService->notifyReadyForVendor($fuelRequest, $user);
                } else {
                    if ($spv) {
                        UserNotification::notify(
                            $spv->id,
                            $fuelRequest->id,
                            "Pengajuan Baru: {$requestNo}",
                            "Fuel Request {$requestNo} dari {$user->name} ({$vehicle->unit_code} - {$data['total_liters']} L) menunggu persetujuan SPV Anda.",
                            'warning'
                        );
                        $this->whatsappService->notifyPendingSpv($fuelRequest, $user);
                    }
                }
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

            if (!$user->hasActiveSignature()) {
                throw ValidationException::withMessages([
                    'signature' => 'Anda wajib mengunggah tanda tangan digital di menu Profil sebelum dapat mengajukan Fuel Request.',
                ]);
            }

            $isSpbu = $locked->isSpbu();
            $isDeptheadSubmitter = $user->isDepthead() || $isSpbu;

            if ($isSpbu) {
                if (!$user->canUseSpbu()) {
                    throw ValidationException::withMessages(['vendor_name' => 'Opsi SPBU hanya dapat diajukan oleh Dept Head / Site Manager.']);
                }
                $fueledAt = $locked->request_date ?? now();
                $locked->update([
                    'status_spv' => 'disetujui',
                    'spv_action_at' => now(),
                    'spv_note' => 'Bypassed (Pengisian SPBU Dept Head/SM)',
                    'depthead_id' => $user->id,
                    'depthead_name' => 'Approval By ' . ($user->username ?? $user->name),
                    'status_depthead' => 'disetujui',
                    'depthead_action_at' => now(),
                    'depthead_note' => 'Disetujui otomatis — Pengisian SPBU',
                    'depthead_signature' => $user->signature_url,
                    'manager_id' => $user->id,
                    'manager_name' => 'Approval By ' . ($user->username ?? $user->name),
                    'status_manager' => 'disetujui',
                    'manager_action_at' => now(),
                    'manager_note' => 'Disetujui otomatis — Pengisian SPBU',
                    'status_vendor' => 'disetujui',
                    'vendor_action_at' => now(),
                    'vendor_note' => 'Bypassed — Pengisian Langsung di SPBU',
                    'status_fuelman' => 'disetujui',
                    'fuelman_action_at' => now(),
                    'fuelman_note' => 'Pengisian Langsung di SPBU',
                    'actual_liters' => (float) $locked->total_liters,
                    'actual_hm_km' => (float) $locked->hm_km,
                    'fueled_at' => $fueledAt,
                    'user_verified_at' => now(),
                    'user_verification_note' => 'Terverifikasi otomatis (Pengisian SPBU Dept Head/SM).',
                    'overall_status' => 'disetujui',
                    'current_stage' => 'completed',
                    'driver_signature' => $user->signature_url ?? $locked->driver_signature,
                ]);

                if (!$locked->transactions()->exists()) {
                    $trxNo = sprintf('TRX-SPBU-%s-%03d', $fueledAt->format('Ymd'), FuelTransaction::count() + 1);
                    while (FuelTransaction::where('transaction_no', $trxNo)->exists()) {
                        $trxNo = sprintf('TRX-SPBU-%s-%04d', $fueledAt->format('Ymd'), rand(1000, 9999));
                    }
                    FuelTransaction::create([
                        'transaction_no' => $trxNo,
                        'fuel_request_id' => $locked->id,
                        'fuelman_id' => $user->id,
                        'requested_liters' => (float) $locked->total_liters,
                        'actual_liters' => (float) $locked->total_liters,
                        'variance_liters' => 0,
                        'meter_at_fueling' => (float) $locked->hm_km,
                        'fueled_at' => $fueledAt,
                        'fuelman_signature' => $user->signature_url,
                        'notes' => 'Pengisian SPBU: ' . ($locked->spbu_location ?? 'SPBU'),
                    ]);
                }

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'spbu_completed',
                    'draft',
                    'completed',
                    "Draft pengisian SPBU {$locked->request_no} diajukan oleh {$user->name} dan langsung Full Approved (Selesai)."
                );

                return $locked;
            }

            $targetStage = $isDeptheadSubmitter ? 'pending_vendor' : 'pending_spv';

            $locked->update([
                'status_spv' => $isDeptheadSubmitter ? 'disetujui' : 'menunggu',
                'spv_action_at' => $isDeptheadSubmitter ? now() : null,
                'spv_note' => $isDeptheadSubmitter ? 'Bypassed (Pengajuan langsung oleh Dept Head)' : null,
                'depthead_id' => $isDeptheadSubmitter ? $user->id : $locked->depthead_id,
                'depthead_name' => $isDeptheadSubmitter ? ('Approval By ' . ($user->username ?? $user->name)) : $locked->depthead_name,
                'status_depthead' => $isDeptheadSubmitter ? 'disetujui' : '-',
                'depthead_action_at' => $isDeptheadSubmitter ? now() : null,
                'depthead_note' => $isDeptheadSubmitter ? 'Disetujui otomatis (Pengajuan oleh Dept Head)' : null,
                'depthead_signature' => $isDeptheadSubmitter ? $user->signature_url : null,
                'manager_id' => $isDeptheadSubmitter ? $user->id : $locked->manager_id,
                'manager_name' => $isDeptheadSubmitter ? ('Approval By ' . ($user->username ?? $user->name)) : $locked->manager_name,
                'status_manager' => $isDeptheadSubmitter ? 'disetujui' : '-',
                'manager_action_at' => $isDeptheadSubmitter ? now() : null,
                'manager_note' => $isDeptheadSubmitter ? 'Disetujui otomatis (Pengajuan oleh Dept Head)' : null,
                'status_vendor' => $isDeptheadSubmitter ? 'menunggu' : '-',
                'status_fuelman' => $isDeptheadSubmitter ? 'menunggu' : '-',
                'overall_status' => 'menunggu',
                'current_stage' => $targetStage,
                'request_date' => now(),
                'driver_signature' => $user->signature_url ?? $locked->driver_signature,
            ]);

            if ($isDeptheadSubmitter) {
                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'manager',
                    'status' => 'approved',
                    'note' => 'Disetujui otomatis saat draft diajukan oleh Dept Head.',
                    'signature_path' => $user->signature_url,
                    'action_at' => now(),
                ]);

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'approved_manager',
                    'draft',
                    'pending_vendor',
                    "Draft Fuel Request {$locked->request_no} diajukan oleh Dept Head ({$user->name}). Tahap SPV di-bypass dan otomatis disetujui Dept Head. Menunggu Persetujuan & Realisasi Vendor."
                );

                if ($locked->vendor_user_id) {
                    UserNotification::notify(
                        $locked->vendor_user_id,
                        $locked->id,
                        "Menunggu Approval Vendor: {$locked->request_no}",
                        "Fuel Request {$locked->request_no} dari Dept Head {$user->name} telah disetujui dan siap untuk realisasi pengisian BBM.",
                        'warning'
                    );
                }
                $this->whatsappService->notifyReadyForVendor($locked, $user);
            } else {
                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'submitted',
                    'draft',
                    'pending_spv',
                    "Draft Fuel Request {$locked->request_no} resmi diajukan dan menunggu persetujuan SPV."
                );

                if ($locked->spv_id) {
                    UserNotification::notify(
                        $locked->spv_id,
                        $locked->id,
                        "Pengajuan Baru: {$locked->request_no}",
                        "Fuel Request {$locked->request_no} menunggu persetujuan SPV Anda.",
                        'warning'
                    );
                }
                $this->whatsappService->notifyPendingSpv($locked, $user);
            }

            return $locked;
        });
    }

    /**
     * Supervisor or Dept Head/Manager inspects and updates the requested fuel liters before or during approval.
     */
    public function updateLiters(FuelRequest $fuelRequest, User $user, float $newLiters, ?string $reason = null): array
    {
        return DB::transaction(function () use ($fuelRequest, $user, $newLiters, $reason) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            $isSpvStage = $locked->status_spv === 'menunggu';
            $isDeptheadStage = $locked->status_spv === 'disetujui' && $locked->status_depthead === 'menunggu';

            if ($isSpvStage && ($user->isSpv() || $user->isAdmin())) {
                if (!$user->isAdmin() && $locked->spv_id && (int) $locked->spv_id !== (int) $user->id) {
                    return [
                        'ok' => false,
                        'message' => 'Anda bukan Supervisor yang ditugaskan pada pengajuan ini.',
                    ];
                }
                $actorRoleLabel = 'Supervisor';
            } elseif ($isDeptheadStage && ($user->isManager() || $user->isAdmin())) {
                if (!$user->isAdmin() && $locked->depthead_id && (int) $locked->depthead_id !== (int) $user->id) {
                    return [
                        'ok' => false,
                        'message' => 'Anda bukan Dept Head / Manager yang ditugaskan pada pengajuan ini.',
                    ];
                }
                $actorRoleLabel = 'Dept Head / Manager';
            } else {
                return [
                    'ok' => false,
                    'message' => 'Jumlah liter hanya dapat disesuaikan pada tahap persetujuan SPV atau Dept Head / Manager yang sedang aktif.',
                ];
            }

            $oldLiters = (float) $locked->total_liters;
            $newLiters = round($newLiters, 2);

            $locked->update([
                'total_liters' => $newLiters,
            ]);

            FuelRequestHistory::record(
                $locked->id,
                $user->id,
                'adjusted_liters',
                $locked->current_stage,
                $locked->current_stage,
                "{$actorRoleLabel} ({$user->name}) menyesuaikan total liter permintaan BBM dari {$oldLiters} L menjadi {$newLiters} L." . ($reason ? " Alasan: {$reason}" : "")
            );

            UserNotification::notify(
                $locked->user_id,
                $locked->id,
                "Penyesuaian Liter BBM: {$locked->request_no}",
                "{$actorRoleLabel} ({$user->name}) telah menyesuaikan jumlah liter permintaan BBM Anda dari {$oldLiters} L menjadi {$newLiters} L." . ($reason ? " Alasan: {$reason}" : ""),
                'info'
            );

            return [
                'ok' => true,
                'old_liters' => $oldLiters,
                'new_liters' => $newLiters,
                'message' => "Jumlah liter permintaan BBM berhasil diperbarui dari {$oldLiters} L menjadi {$newLiters} L.",
            ];
        });
    }

    /**
     * Process sequential approval:
     * Stage 1: SPV/Dept Head (pending_spv -> pending_manager)
     * Stage 2: Dept Head/Manager (pending_manager -> pending_vendor)
     * Stage 3: Vendor (pending_vendor -> approved [Ready for Fueling])
     */
    public function approve(FuelRequest $fuelRequest, User $user, ?string $note = null, ?float $adjustedLiters = null, ?array $realizationData = null): array
    {
        return DB::transaction(function () use ($fuelRequest, $user, $note, $adjustedLiters, $realizationData) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            if (in_array($locked->overall_status, ['ditolak', 'cancelled']) || in_array($locked->current_stage, ['rejected', 'cancelled', 'completed'])) {
                return ['ok' => false, 'message' => 'Pengajuan ini sudah ditolak, dibatalkan, atau selesai dan tidak dapat disetujui kembali.'];
            }

            if (!$user->hasActiveSignature()) {
                return [
                    'ok' => false,
                    'message' => 'Gagal memproses persetujuan: Anda wajib mengunggah tanda tangan digital di menu Profil terlebih dahulu.',
                ];
            }

            $sig = $user->signature_url;

            // STAGE 1: SPV / Dept Head
            if ($locked->status_spv === 'menunggu' && ($user->isSpv() || $user->isAdmin())) {
                if (!$user->isAdmin() && $locked->spv_id && (int)$locked->spv_id !== (int)$user->id) {
                    return ['ok' => false, 'message' => 'Anda bukan SPV yang ditugaskan pada pengajuan ini.'];
                }

                $fromStage = $locked->current_stage;
                $oldLiters = (float) $locked->total_liters;
                $literAdjusted = false;
                $finalLiters = $oldLiters;

                if ($adjustedLiters !== null && $adjustedLiters > 0 && abs($adjustedLiters - $oldLiters) > 0.001) {
                    $finalLiters = round($adjustedLiters, 2);
                    $literAdjusted = true;
                }

                $spvNote = $note ?: ($literAdjusted ? "Disetujui oleh SPV dengan penyesuaian liter dari {$oldLiters} L menjadi {$finalLiters} L." : 'Disetujui oleh SPV.');

                $updateData = [
                    'status_spv' => 'disetujui',
                    'spv_action_at' => now(),
                    'spv_note' => $spvNote,
                    'spv_signature' => $sig ?: 'images/sig_spv.png',
                    'status_depthead' => 'menunggu',
                    'status_manager' => 'menunggu',
                    'current_stage' => 'pending_manager',
                ];

                if ($literAdjusted) {
                    $updateData['total_liters'] = $finalLiters;
                }

                $locked->update($updateData);

                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'spv',
                    'status' => 'approved',
                    'note' => $spvNote,
                    'signature_path' => $sig ?: 'images/sig_spv.png',
                    'action_at' => now(),
                ]);

                $historyMsg = "Disetujui oleh SPV ({$user->name}). Diteruskan ke Dept Head / Manager.";
                if ($literAdjusted) {
                    $historyMsg = "Disetujui oleh SPV ({$user->name}) dengan penyesuaian liter dari {$oldLiters} L menjadi {$finalLiters} L. Diteruskan ke Dept Head / Manager.";
                }
                if ($note) {
                    $historyMsg .= " Catatan: {$note}";
                }

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'approved_spv',
                    $fromStage,
                    'pending_manager',
                    $historyMsg
                );

                if ($literAdjusted) {
                    UserNotification::notify(
                        $locked->user_id,
                        $locked->id,
                        "Pengajuan Disetujui SPV dengan Penyesuaian Liter: {$locked->request_no}",
                        "Pengajuan {$locked->request_no} telah disetujui SPV ({$user->name}) dengan penyesuaian jumlah liter menjadi {$finalLiters} L.",
                        'info'
                    );
                }

                UserNotification::notify(
                    $locked->depthead_id ?? $locked->manager_id,
                    $locked->id,
                    "Menunggu Approval Manager: {$locked->request_no}",
                    "Pengajuan {$locked->request_no} telah disetujui SPV dan menunggu persetujuan Dept Head/Manager Anda.",
                    'warning'
                );

                $this->whatsappService->notifyPendingDepthead($locked, $user);

                $successMsg = $literAdjusted
                    ? "Pengajuan {$locked->request_no} berhasil disetujui pada Tahap 1 (SPV) dengan penyesuaian {$finalLiters} L dan diteruskan ke Dept Head/Manager!"
                    : "Pengajuan {$locked->request_no} berhasil disetujui pada Tahap 1 (SPV) dan diteruskan ke Dept Head/Manager!";

                return [
                    'ok' => true,
                    'message' => $successMsg,
                ];
            }

            // STAGE 2: Dept Head / Manager (Must have SPV approved first!)
            if ($locked->status_spv === 'disetujui' && $locked->status_depthead !== 'disetujui' && ($user->isManager() || $user->isAdmin())) {
                if (!$user->isAdmin() && $locked->depthead_id && (int)$locked->depthead_id !== (int)$user->id) {
                    return ['ok' => false, 'message' => 'Anda bukan Dept Head/Manager yang ditugaskan pada pengajuan ini.'];
                }

                $fromStage = $locked->current_stage;
                $oldLiters = (float) $locked->total_liters;
                $literAdjusted = false;
                $finalLiters = $oldLiters;

                if ($adjustedLiters !== null && $adjustedLiters > 0 && abs($adjustedLiters - $oldLiters) > 0.001) {
                    $finalLiters = round($adjustedLiters, 2);
                    $literAdjusted = true;
                }

                $dhNote = $note ?: ($literAdjusted
                    ? "Disetujui oleh Dept Head/Manager dengan penyesuaian liter dari {$oldLiters} L menjadi {$finalLiters} L."
                    : 'Disetujui oleh Dept Head/Manager.');

                $updateData = [
                    'status_depthead' => 'disetujui',
                    'depthead_action_at' => now(),
                    'depthead_note' => $dhNote,
                    'depthead_signature' => $sig ?: 'images/sig_depthead.png',
                    'status_manager' => 'disetujui',
                    'manager_action_at' => now(),
                    'manager_note' => $dhNote,
                    'status_vendor' => 'menunggu',
                    'status_fuelman' => 'menunggu',
                    'current_stage' => 'pending_vendor',
                ];

                if ($literAdjusted) {
                    $updateData['total_liters'] = $finalLiters;
                }

                $locked->update($updateData);

                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'manager',
                    'status' => 'approved',
                    'note' => $dhNote,
                    'signature_path' => $sig ?: 'images/sig_depthead.png',
                    'action_at' => now(),
                ]);

                $historyMsg = $literAdjusted
                    ? "Disetujui oleh Dept Head/Manager ({$user->name}) dengan penyesuaian liter dari {$oldLiters} L menjadi {$finalLiters} L. Diteruskan ke Vendor untuk realisasi pengisian."
                    : "Disetujui oleh Dept Head/Manager ({$user->name}). Diteruskan ke Vendor untuk persetujuan & realisasi pengisian.";
                if ($note) {
                    $historyMsg .= " Catatan: {$note}";
                }

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'approved_manager',
                    $fromStage,
                    'pending_vendor',
                    $historyMsg
                );

                if ($literAdjusted) {
                    UserNotification::notify(
                        $locked->user_id,
                        $locked->id,
                        "Penyesuaian Liter oleh Dept Head: {$locked->request_no}",
                        "Pengajuan {$locked->request_no} disetujui Dept Head ({$user->name}) dengan penyesuaian jumlah liter dari {$oldLiters} L menjadi {$finalLiters} L.",
                        'info'
                    );
                }

                if ($locked->vendor_user_id) {
                    UserNotification::notify(
                        $locked->vendor_user_id,
                        $locked->id,
                        "Menunggu Approval Vendor: {$locked->request_no}",
                        "Pengajuan {$locked->request_no} telah disetujui Manager ({$finalLiters} L) dan menunggu persetujuan Vendor ({$locked->vendor_name}).",
                        'warning'
                    );
                }

                $this->whatsappService->notifyReadyForVendor($locked, $user);

                return [
                    'ok' => true,
                    'message' => $literAdjusted
                        ? "Pengajuan {$locked->request_no} berhasil disetujui pada Tahap 2 (Dept Head/Manager) dengan penyesuaian {$finalLiters} L dan diteruskan ke Vendor!"
                        : "Pengajuan {$locked->request_no} berhasil disetujui pada Tahap 2 (Dept Head/Manager) dan diteruskan ke Vendor!",
                ];
            }

            // STAGE 3: Vendor (Persetujuan & Realisasi Pengisian BBM Terpadu -> Menunggu Verifikasi Akhir Pemohon)
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
                $requestedLiters = (float) $locked->total_liters;
                $actualLiters = isset($realizationData['actual_liters']) ? (float)$realizationData['actual_liters'] : $requestedLiters;
                $varianceLiters = round($actualLiters - $requestedLiters, 2);

                // LOCK SERVER-SIDE (Item 7): Vendor/Fuelman tidak boleh mengubah KM/HM maupun Waktu Pengisian
                if ($user->isAdmin()) {
                    $meterAtFueling = isset($realizationData['meter_at_fueling']) ? (float)$realizationData['meter_at_fueling'] : (float)$locked->hm_km;
                    $fueledAt = !empty($realizationData['fueled_at']) ? Carbon::parse($realizationData['fueled_at']) : now();
                } else {
                    $meterAtFueling = (float) $locked->hm_km;
                    $fueledAt = now();
                }

                $realizationNote = !empty($realizationData['notes']) ? $realizationData['notes'] : ($note ?: 'Disetujui dan pengisian BBM telah direalisasikan oleh Vendor.');

                // Buat transaksi fisik BBM di fuel_transactions
                $trxNo = sprintf('TRX-%s-%03d', $fueledAt->format('Ymd'), FuelTransaction::count() + 1);
                while (FuelTransaction::where('transaction_no', $trxNo)->exists()) {
                    $trxNo = sprintf('TRX-%s-%04d', $fueledAt->format('Ymd'), rand(1000, 9999));
                }

                FuelTransaction::create([
                    'transaction_no' => $trxNo,
                    'fuel_request_id' => $locked->id,
                    'fuelman_id' => $user->id,
                    'requested_liters' => $requestedLiters,
                    'actual_liters' => $actualLiters,
                    'variance_liters' => $varianceLiters,
                    'meter_at_fueling' => $meterAtFueling,
                    'fueled_at' => $fueledAt,
                    'fuelman_signature' => $sig ?: 'images/sig_fuelman.png',
                    'notes' => $realizationNote,
                ]);

                // Item 8: Setelah Vendor isi liter aktual, lanjut ke tahap Verifikasi Akhir Pemohon (pending_user_verification)
                $locked->update([
                    'status_vendor' => 'disetujui',
                    'vendor_action_at' => now(),
                    'vendor_note' => $realizationNote,
                    'vendor_signature' => $sig ?: 'images/sig_fuelman.png',
                    'vendor_user_id' => $user->id,
                    'status_fuelman' => 'disetujui',
                    'fuelman_action_at' => now(),
                    'fuelman_note' => $realizationNote,
                    'fuelman_signature' => $sig ?: 'images/sig_fuelman.png',
                    'fuelman_id' => $user->id,
                    'fuelman_name' => $user->name,
                    'actual_liters' => $actualLiters,
                    'actual_hm_km' => $meterAtFueling,
                    'fueled_at' => $fueledAt,
                    'overall_status' => 'menunggu',
                    'current_stage' => 'pending_user_verification',
                ]);

                FuelRequestApproval::create([
                    'fuel_request_id' => $locked->id,
                    'approver_id' => $user->id,
                    'stage' => 'vendor',
                    'status' => 'approved',
                    'note' => $realizationNote,
                    'signature_path' => $sig ?: 'images/sig_fuelman.png',
                    'action_at' => now(),
                ]);

                $diffText = $varianceLiters == 0
                    ? 'sesuai jumlah disetujui'
                    : ($varianceLiters > 0 ? "+{$varianceLiters} L dari yang disetujui" : "{$varianceLiters} L dari yang disetujui");

                FuelRequestHistory::record(
                    $locked->id,
                    $user->id,
                    'vendor_realized',
                    $fromStage,
                    'pending_user_verification',
                    "Realisasi pengisian BBM dicatat oleh Vendor ({$user->name} - {$locked->vendor_name}): {$actualLiters} L ({$diffText}), HM/KM terkunci: {$meterAtFueling}, No. Transaksi: {$trxNo}. Menunggu verifikasi akhir pemohon."
                );

                UserNotification::notify(
                    $locked->user_id,
                    $locked->id,
                    "Verifikasi Akhir Diperlukan: {$locked->request_no}",
                    "Vendor ({$locked->vendor_name}) telah mencatat pengisian aktual sebesar {$actualLiters} Liter (dari {$requestedLiters} L). Silakan buka detail pengajuan untuk melakukan Verifikasi Akhir (Final Approve).",
                    'warning'
                );

                $this->whatsappService->notifyPendingUserVerification($locked, $user);

                return [
                    'ok' => true,
                    'message' => "Realisasi pengisian BBM ({$actualLiters} Liter) berhasil dicatat dan dikunci! Notifikasi Verifikasi Akhir telah dikirim kepada Pemohon ({$locked->driver_name}).",
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
     * Record actual fueling realization by Fuel Man / Vendor (Tahap 4)
     * Enforces Vendor field locks (KM/HM & waktu pengisian terkunci; tidak dapat diedit ulang setelah tercatat).
     */
    public function recordRealization(FuelRequest $fuelRequest, User $user, array $data): FuelTransaction
    {
        return DB::transaction(function () use ($fuelRequest, $user, $data) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            if (!$locked->isVendorApproved() && $locked->status_depthead !== 'disetujui') {
                throw ValidationException::withMessages([
                    'status' => 'Pengisian BBM tidak dapat dicatat sebelum pengajuan memperoleh persetujuan Dept Head / Vendor.',
                ]);
            }

            // Item 7: Blokir pengeditan ulang oleh Vendor / Fuelman setelah pengisian tercatat
            if (!$user->isAdmin() && ($locked->transactions()->exists() || in_array($locked->current_stage, ['pending_user_verification', 'completed'], true))) {
                throw ValidationException::withMessages([
                    'status' => 'Realisasi pengisian BBM sudah tercatat dan dikunci. Vendor tidak dapat mengubah data pengisian lagi.',
                ]);
            }

            if (!$user->hasActiveSignature()) {
                throw ValidationException::withMessages([
                    'signature' => 'Gagal mencatat pengisian BBM: Anda wajib mengunggah tanda tangan digital di menu Profil terlebih dahulu.',
                ]);
            }

            $requestedLiters = (float)$locked->total_liters;
            $actualLiters = (float)$data['actual_liters'];
            $varianceLiters = round($actualLiters - $requestedLiters, 2);

            // Item 7: Server-side lock untuk KM/HM dan waktu pengisian pada non-Admin
            if ($user->isAdmin()) {
                $meterAtFueling = isset($data['meter_at_fueling']) ? (float)$data['meter_at_fueling'] : (float)$locked->hm_km;
                $fueledAt = !empty($data['fueled_at']) ? Carbon::parse($data['fueled_at']) : now();
            } else {
                $meterAtFueling = (float)$locked->hm_km;
                $fueledAt = now();
            }

            $signature = $user->signature_url ?? $locked->fuelman_signature ?? 'images/sig_fuelman.png';

            $transaction = $locked->transactions()->latest('id')->first();
            if ($transaction) {
                $trxNo = $transaction->transaction_no;
                $transaction->update([
                    'actual_liters' => $actualLiters,
                    'variance_liters' => $varianceLiters,
                    'meter_at_fueling' => $meterAtFueling,
                    'fueled_at' => $fueledAt,
                    'fuelman_signature' => $signature,
                    'notes' => $data['notes'] ?? $transaction->notes,
                ]);
            } else {
                $trxNo = sprintf('TRX-%s-%03d', $fueledAt->format('Ymd'), FuelTransaction::count() + 1);
                while (FuelTransaction::where('transaction_no', $trxNo)->exists()) {
                    $trxNo = sprintf('TRX-%s-%04d', $fueledAt->format('Ymd'), rand(1000, 9999));
                }

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
            }

            $fromStage = $locked->current_stage;
            $nextStage = $locked->current_stage === 'completed' ? 'completed' : 'pending_user_verification';
            $nextOverall = $nextStage === 'completed' ? 'disetujui' : 'menunggu';

            $locked->update([
                'actual_liters' => $actualLiters,
                'actual_hm_km' => $meterAtFueling,
                'fueled_at' => $fueledAt,
                'status_vendor' => 'disetujui',
                'status_fuelman' => 'disetujui',
                'fuelman_action_at' => now(),
                'fuelman_signature' => $signature,
                'fuelman_note' => $data['notes'] ?? "Realisasi pengisian tercatat: {$actualLiters} Liter pada HM/KM {$meterAtFueling}.",
                'overall_status' => $nextOverall,
                'current_stage' => $nextStage,
            ]);

            $diffText = $varianceLiters == 0
                ? 'Sesuai jumlah disetujui'
                : ($varianceLiters > 0 ? "+{$varianceLiters} L dari yang disetujui" : "{$varianceLiters} L dari yang disetujui");

            FuelRequestHistory::record(
                $locked->id,
                $user->id,
                $nextStage === 'completed' ? 'completed' : 'vendor_realized',
                $fromStage,
                $nextStage,
                "Realisasi pengisian BBM dicatat oleh {$user->name}: {$actualLiters} L ({$diffText}), HM/KM: {$meterAtFueling}, No. Transaksi: {$trxNo}."
            );

            if ($nextStage === 'pending_user_verification') {
                UserNotification::notify(
                    $locked->user_id,
                    $locked->id,
                    "Verifikasi Akhir Diperlukan: {$locked->request_no}",
                    "Realisasi pengisian BBM sebesar {$actualLiters} Liter telah dicatat. Silakan lakukan Verifikasi Akhir (Final Approve) untuk menyelesaikan transaksi.",
                    'warning'
                );
                $this->whatsappService->notifyPendingUserVerification($locked, $user);
            }

            return $transaction;
        });
    }

    /**
     * Final verification (Final Approve) by the requesting Driver / Employee (Tahap 5)
     */
    public function verifyByUser(FuelRequest $fuelRequest, User $user, ?string $note = null): array
    {
        return DB::transaction(function () use ($fuelRequest, $user, $note) {
            $locked = FuelRequest::lockForUpdate()->findOrFail($fuelRequest->id);

            if ($locked->current_stage !== 'pending_user_verification') {
                return [
                    'ok' => false,
                    'message' => 'Pengajuan ini tidak sedang berada pada tahap Menunggu Verifikasi Akhir Pemohon.',
                ];
            }

            if ((int) $locked->user_id !== (int) $user->id && !$user->isAdmin()) {
                return [
                    'ok' => false,
                    'message' => 'Hanya pemohon / driver yang bersangkutan (atau Admin) yang dapat melakukan Verifikasi Akhir.',
                ];
            }

            $finalNote = $note ?: 'Pemohon menyatakan bahwa pengisian BBM aktual di lokasi telah sesuai dan dapat dipertanggungjawabkan.';

            $locked->update([
                'current_stage' => 'completed',
                'overall_status' => 'disetujui',
                'user_verified_at' => now(),
                'user_verification_note' => $finalNote,
            ]);

            FuelRequestHistory::record(
                $locked->id,
                $user->id,
                'user_verified',
                'pending_user_verification',
                'completed',
                "Verifikasi Akhir (Final Approve) dikonfirmasi oleh {$user->name}. Pengisian aktual {$locked->actual_liters} L dinyatakan sesuai. Catatan: {$finalNote}"
            );

            if ($locked->vendor_user_id && (int)$locked->vendor_user_id !== (int)$user->id) {
                UserNotification::notify(
                    $locked->vendor_user_id,
                    $locked->id,
                    "Pengisian Terverifikasi Selesai: {$locked->request_no}",
                    "Pemohon ({$user->name}) telah mengonfirmasi Verifikasi Akhir untuk pengisian {$locked->request_no} ({$locked->actual_liters} L). Transaksi Selesai.",
                    'success'
                );
            }

            return [
                'ok' => true,
                'message' => "Verifikasi Akhir berhasil! Pengajuan {$locked->request_no} telah resmi dinyatakan Selesai.",
            ];
        });
    }
}
