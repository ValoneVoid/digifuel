@extends('layouts.app')

@section('title', 'Detail & Persetujuan Pengajuan Fuel')

@section('content')
<div class="digi-card">
    <!-- Header -->
    <div class="digi-form-header">
        <img src="{{ asset('images/gas_pump_icon.svg') }}" alt="Fuel Pump" class="digi-form-icon">
        <h1 class="digi-form-title">Fuel Request — {{ $fuelRequest->request_no }}</h1>
        <p class="digi-form-subtitle">
            Detail Pengajuan, Status Persetujuan Berjenjang, Pencatatan Realisasi BBM, dan Riwayat Aktivitas.
        </p>
    </div>

    <!-- Visual Sequential Progress Approval Stepper -->
    @php
        $spvDone = $fuelRequest->status_spv === 'disetujui';
        $dhDone = $fuelRequest->status_depthead === 'disetujui';
        $vendorDone = $fuelRequest->isVendorApproved();
        $pendingUserVerify = $fuelRequest->current_stage === 'pending_user_verification';
        $isCompleted = $fuelRequest->current_stage === 'completed';
        $isSpbu = $fuelRequest->isSpbu();
        $isRejected = $fuelRequest->overall_status === 'ditolak';
    @endphp
    <div class="digi-stepper-scroll" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; text-align: center; font-size: 12px;">
        <div>
            <div style="font-weight: 700; color: #15803d;">1. Employee</div>
            <div style="color: #475569;">{{ $fuelRequest->driver_name }}</div>
            <span class="digi-badge digi-badge-green" style="margin-top:4px;">Diajukan</span>
        </div>
        <div>
            <div style="font-weight: 700; color: {{ $spvDone ? '#15803d' : ($fuelRequest->status_spv === 'ditolak' ? '#b91c1c' : '#854d0e') }};">2. SPV / Dept Head</div>
            <div style="color: #475569;">{{ $fuelRequest->spv?->name ?? ($fuelRequest->spv_name ?: 'Bypassed (Dept Head)') }}</div>
            <span class="digi-badge {{ $spvDone ? 'digi-badge-green' : ($fuelRequest->status_spv === 'ditolak' ? 'digi-badge-red' : 'digi-badge-yellow') }}" style="margin-top:4px;">
                {{ (!$fuelRequest->spv_id && $spvDone) ? 'Bypassed' : ucfirst($fuelRequest->status_spv) }}
            </span>
        </div>
        <div>
            <div style="font-weight: 700; color: {{ $dhDone ? '#15803d' : ($fuelRequest->status_depthead === 'ditolak' ? '#b91c1c' : '#475569') }};">3. Dept Head / Mgr</div>
            <div style="color: #475569;">{{ $fuelRequest->depthead?->name ?? ($fuelRequest->depthead_name ?: '-') }}</div>
            <span class="digi-badge {{ $dhDone ? 'digi-badge-green' : ($fuelRequest->status_depthead === 'ditolak' ? 'digi-badge-red' : ($fuelRequest->status_depthead === 'menunggu' ? 'digi-badge-yellow' : 'digi-badge-gray')) }}" style="margin-top:4px;">
                {{ $fuelRequest->status_depthead === '-' ? 'Menunggu Tahap 2' : ucfirst($fuelRequest->status_depthead) }}
            </span>
        </div>
        <div>
            <div style="font-weight: 700; color: {{ $vendorDone ? '#15803d' : ($fuelRequest->status_vendor === 'ditolak' || $fuelRequest->status_fuelman === 'ditolak' ? '#b91c1c' : '#475569') }};">4. {{ $isSpbu ? 'Pengisian SPBU' : 'Realisasi Vendor' }}</div>
            <div style="color: #475569;">{{ $isSpbu ? ($fuelRequest->spbu_location ?? 'SPBU') : $fuelRequest->vendor_name }}</div>
            <span class="digi-badge {{ $vendorDone ? 'digi-badge-green' : ($fuelRequest->status_vendor === 'ditolak' || $fuelRequest->status_fuelman === 'ditolak' ? 'digi-badge-red' : ($dhDone ? 'digi-badge-yellow' : 'digi-badge-gray')) }}" style="margin-top:4px;">
                @if($isSpbu)
                    SPBU Full Approved ({{ (float)($fuelRequest->actual_liters ?? $fuelRequest->total_liters) }} L)
                @elseif($vendorDone)
                    Terisi ({{ (float)($fuelRequest->actual_liters ?? $fuelRequest->total_liters) }} L)
                @elseif($dhDone)
                    Menunggu Vendor
                @else
                    Menunggu Tahap 3
                @endif
            </span>
        </div>
        @if(!$isSpbu)
        <div>
            <div style="font-weight: 700; color: {{ $isCompleted ? '#15803d' : ($pendingUserVerify ? '#b45309' : '#475569') }};">5. Verifikasi Pemohon</div>
            <div style="color: #475569;">{{ $fuelRequest->driver_name }}</div>
            <span class="digi-badge {{ $isCompleted ? 'digi-badge-green' : ($pendingUserVerify ? 'digi-badge-yellow' : 'digi-badge-gray') }}" style="margin-top:4px;">
                @if($isCompleted)
                    Terverifikasi Selesai
                @elseif($pendingUserVerify)
                    Menunggu Final Approve
                @else
                    Menunggu Tahap 4
                @endif
            </span>
        </div>
        @endif
    </div>

    @php
        $currentUser = auth()->user();
        $canApprove = false;
        $activeStageLabel = '';
        $isVendorStage = false;
        $canEditLiters = false;
        $canVerifyUser = $currentUser && app(\App\Policies\FuelRequestPolicy::class)->verifyUser($currentUser, $fuelRequest);

        if ($currentUser && !$isRejected && !in_array($fuelRequest->current_stage, ['completed', 'pending_user_verification'], true)) {
            // Stage 1 (Langkah 2): SPV
            if (($currentUser->isSpv() || $currentUser->isAdmin()) && $fuelRequest->status_spv === 'menunggu') {
                $canApprove = true;
                $canEditLiters = true;
                $activeStageLabel = 'Tahap 2: SPV';
            }
            // Stage 2 (Langkah 3): Dept Head / Manager (only after SPV approved!)
            elseif (($currentUser->isManager() || $currentUser->isAdmin()) && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead !== 'disetujui') {
                $canApprove = true;
                $canEditLiters = true;
                $activeStageLabel = 'Tahap 3: Dept Head/Manager';
            }
            // Stage 3 (Langkah 4): Vendor (only after SPV & Dept Head approved!)
            elseif (($currentUser->isVendor() || $currentUser->isFuelman() || $currentUser->isAdmin()) && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead === 'disetujui' && !$fuelRequest->isVendorApproved()) {
                $canApprove = true;
                $isVendorStage = true;
                $activeStageLabel = 'Tahap 4: Vendor BBM';
            }
        }
    @endphp

    <!-- Warning banner if approver / driver / vendor has no signature -->
    @if($currentUser && !$currentUser->hasActiveSignature() && ($canApprove || ($fuelRequest->current_stage === 'draft' && $currentUser->id === $fuelRequest->user_id) || ($vendorDone && ($currentUser->isVendor() || $currentUser->isFuelman() || $currentUser->isAdmin()))))
        <div class="digi-signature-alert">
            <div class="digi-signature-alert-content">
                <div class="digi-signature-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
                <div>
                    <div class="digi-signature-alert-title">
                        <span>Tanda Tangan Digital Wajib Diisi!</span>
                        <span class="digi-signature-alert-badge">Wajib</span>
                    </div>
                    <div class="digi-signature-alert-text">
                        Anda belum memiliki tanda tangan digital aktif. Sesuai kebijakan sistem FuelLink, seluruh aksi persetujuan (Approval) dan pengajuan BBM mewajibkan tanda tangan digital yang sah.
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="digi-signature-alert-btn">
                <span>Unggah Tanda Tangan Sekarang</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    @endif

    <!-- Title and Action Buttons (Slide 9) -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <h2 class="digi-section-title" style="margin: 0;">Isi Form Request</h2>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            @if($fuelRequest->current_stage === 'draft' && ($currentUser?->id === $fuelRequest->user_id || $currentUser?->isAdmin()))
                <form action="{{ route('fuel.submit.draft', $fuelRequest->id) }}" method="POST" style="margin:0;" onsubmit="return checkSubmitDraft(event)">
                    @csrf
                    <button type="submit" class="btn-digi btn-digi-blue">Ajukan Draft Ini ke SPV</button>
                </form>
            @endif

            @if($canApprove)
                @if($canEditLiters)
                    <button type="button" class="btn-digi btn-digi-yellow" onclick="openUpdateLitersModal()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Ubah Jumlah Liter
                    </button>
                @endif

                <button type="button" class="btn-digi btn-digi-green" onclick="openApproveModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Setuju ({{ $activeStageLabel }})
                </button>

                <button type="button" class="btn-digi btn-digi-red" onclick="openRejectModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Tolak
                </button>
            @else
                <div style="font-size: 13.5px; color: #64748b; font-weight: 600;">
                    Status Saat Ini: 
                    @if($fuelRequest->overall_status === 'disetujui')
                        <span class="digi-badge digi-badge-green">{{ $fuelRequest->stage_label }}</span>
                    @elseif($fuelRequest->overall_status === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @else
                        <span class="digi-badge digi-badge-yellow">{{ $fuelRequest->stage_label }}</span>
                    @endif
                </div>
            @endif

            <a href="{{ route('fuel.document', $fuelRequest->id) }}" target="_blank" class="btn-digi btn-digi-blue" style="padding: 9px 16px;">
                Dokumen Fuel
            </a>

            @if(app(\App\Policies\FuelRequestPolicy::class)->delete($currentUser, $fuelRequest))
                <button type="button" class="btn-digi btn-digi-red" style="padding: 9px 16px;" data-id="{{ $fuelRequest->id }}" data-no="{{ $fuelRequest->request_no }}" onclick="confirmDeleteRequest(this.dataset.id, this.dataset.no)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Hapus Pengajuan
                </button>
            @endif

            @if($currentUser?->isDriver() && !$currentUser?->isAdmin())
                <a href="{{ route('history.index') }}" class="btn-digi btn-digi-gray" style="padding: 9px 16px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    <span>Riwayat Saya</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Form Content (Read-only as per Slide 9) -->
    <div class="digi-form-box">
        <!-- Row 1 -->
        <div class="digi-form-grid-2">
            <div class="digi-form-group">
                <label class="digi-form-label">Tanggal & Waktu Pengajuan <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->request_date->format('Y-m-d H:i') }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Nama Employee (NRP: {{ $fuelRequest->driver_nrp ?? $fuelRequest->user?->nrp ?? '-' }}) <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->driver_name }}" readonly disabled>
            </div>
        </div>

        <!-- Row 2: Unit Information & Vendor Unit -->
        <div class="digi-form-grid-4">
            <div class="digi-form-group">
                <label class="digi-form-label">Unit Code (Kode Unit) <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->unit_code }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Police No (No. Polisi) <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->police_no }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Vendor Unit (Penyedia Unit)</label>
                <input type="text" class="digi-input" style="font-weight: 600; color: #0284c7;" value="{{ $fuelRequest->unitVendor?->name ?? ($fuelRequest->vehicle?->unitVendor?->name ?? 'Internal UT') }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Company (Perusahaan)</label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->company }}" readonly disabled>
            </div>
        </div>

        <!-- Row 3 -->
        <div class="digi-form-grid-2">
            <div class="digi-form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <label class="digi-form-label" style="margin: 0;">
                        {{ $fuelRequest->status_spv === 'menunggu' ? 'Total Liter Diajukan Driver' : 'Total (Jumlah) Liter Disetujui' }} <span class="digi-required">*</span>
                    </label>
                    @if($canEditLiters)
                        <button type="button" onclick="openUpdateLitersModal()" class="btn-digi btn-digi-yellow" style="padding: 2px 10px; font-size: 11.5px; height: auto;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Ubah Liter
                        </button>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="text" class="digi-input" value="{{ (float)$fuelRequest->total_liters }} Liter" readonly disabled style="font-weight: 700; color: #0284c7; font-size: 15px;">
                    @if($fuelRequest->vehicle?->tank_capacity)
                        <span style="font-size: 12px; color: #64748b; white-space: nowrap; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; border-radius: 6px;" title="Kapasitas Tangki Kendaraan">
                            Kapasitas Tangki: <strong>{{ (float)$fuelRequest->vehicle->tank_capacity }} L</strong>
                        </span>
                    @endif
                </div>
                <span style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: block;">
                    @if($fuelRequest->status_spv === 'menunggu')
                        Jumlah liter BBM yang diajukan oleh pemohon. Supervisor berwenang memeriksa dan menyesuaikan jumlah liter sebelum menyetujui.
                    @elseif($fuelRequest->status_depthead === 'menunggu')
                        Supervisor telah menyetujui kuota ini. Dept Head memiliki wewenang penyesuaian (adjustment) jumlah liter pada tahap persetujuannya.
                    @else
                        Jumlah liter BBM yang telah disetujui untuk pengisian unit ini.
                    @endif
                </span>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">{{ strtoupper($fuelRequest->vehicle?->meter_type ?? 'KM') }} / Odometer <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ (float)$fuelRequest->hm_km }} {{ strtoupper($fuelRequest->vehicle?->meter_type ?? 'KM') }}" readonly disabled>
            </div>
        </div>

        <!-- Memilih Approval Section -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
            <h3 class="digi-section-title" style="margin-bottom: 8px;">Status Berjenjang Approval (Alur 4 Langkah)</h3>
            <span class="digi-approval-badge">Approval</span>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Area Operasional <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->area }}{{ $fuelRequest->supportPoint ? ' (' . $fuelRequest->supportPoint->name . ')' : '' }}" readonly disabled>
            </div>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Tahap 2 — Verifikasi SPV (Acknowledge) <span class="digi-required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" class="digi-input" value="{{ $fuelRequest->spv_name }} {{ $fuelRequest->spv_action_at ? '(' . $fuelRequest->spv_action_at->format('d/m/Y H:i') . ')' : '' }}" readonly disabled>
                    @if($fuelRequest->status_spv === 'disetujui')
                        <span class="digi-badge digi-badge-green">Disetujui</span>
                    @elseif($fuelRequest->status_spv === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @else
                        <span class="digi-badge digi-badge-yellow">Menunggu SPV..</span>
                    @endif
                </div>
                @if($fuelRequest->spv_note)
                    <span style="font-size: 12px; color: #475569; margin-top: 4px;">Catatan SPV: {{ $fuelRequest->spv_note }}</span>
                @endif
            </div>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Tahap 3 — Approval Dept Head / Manager (Approve) <span class="digi-required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" class="digi-input" value="{{ $fuelRequest->depthead_name }} {{ $fuelRequest->depthead_action_at ? '(' . $fuelRequest->depthead_action_at->format('d/m/Y H:i') . ')' : '' }}" readonly disabled>
                    @if($fuelRequest->status_depthead === 'disetujui')
                        <span class="digi-badge digi-badge-green">Disetujui</span>
                    @elseif($fuelRequest->status_depthead === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @elseif($fuelRequest->status_depthead === 'menunggu')
                        <span class="digi-badge digi-badge-yellow">Menunggu Manager..</span>
                    @else
                        <span style="color:#94a3b8; font-size:13px; font-weight:600;">(Menunggu Tahap 2 Selesai)</span>
                    @endif
                </div>
                @if($fuelRequest->depthead_note)
                    <span style="font-size: 12px; color: #475569; margin-top: 4px;">Catatan Dept Head: {{ $fuelRequest->depthead_note }}</span>
                @endif
            </div>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Tahap 4 — Persetujuan & Realisasi Pengisian Vendor BBM <span class="digi-required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" class="digi-input" value="{{ $fuelRequest->vendor_name }} {{ $fuelRequest->vendor_action_at ? '(' . $fuelRequest->vendor_action_at->format('d/m/Y H:i') . ')' : '' }}" readonly disabled>
                    @if($fuelRequest->status_fuelman === 'disetujui' || $fuelRequest->status_vendor === 'disetujui')
                        <span class="digi-badge digi-badge-green">Selesai Terisi</span>
                    @elseif($fuelRequest->status_fuelman === 'ditolak' || $fuelRequest->status_vendor === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @elseif($fuelRequest->status_depthead === 'disetujui')
                        <span class="digi-badge digi-badge-yellow">Menunggu Vendor..</span>
                    @else
                        <span style="color:#94a3b8; font-size:13px; font-weight:600;">(Menunggu Tahap 3 Selesai)</span>
                    @endif
                </div>
                @if($fuelRequest->vendor_note || $fuelRequest->fuelman_note)
                    <span style="font-size: 12px; color: #475569; margin-top: 4px;">Catatan Vendor: {{ $fuelRequest->vendor_note ?? $fuelRequest->fuelman_note }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- SECTION: DETAIL PENGISIAN SPBU (KHUSUS DEPT HEAD / SM) -->
    @if($isSpbu)
        <div class="digi-form-box" style="border-color: #f59e0b; background: #fffbeb; margin-bottom: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <h3 class="digi-section-title" style="color: #92400e; margin: 0;">
                    Detail Pengisian SPBU (Full Bypass — Dept Head / Site Manager)
                </h3>
                <span class="digi-badge digi-badge-green">SPBU — Full Approved</span>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 10px;">
                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Lokasi SPBU</label>
                    <input type="text" class="digi-input" value="{{ $fuelRequest->spbu_location ?? '-' }}" readonly disabled>
                </div>
                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Harga Aktual per Liter</label>
                    <input type="text" class="digi-input" value="{{ $fuelRequest->spbu_price_per_liter ? 'Rp ' . number_format((float)$fuelRequest->spbu_price_per_liter, 0, ',', '.') . ' / Liter' : '-' }}" readonly disabled style="font-weight: 700; color: #92400e;">
                </div>
                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Total Biaya Aktual SPBU</label>
                    <input type="text" class="digi-input" value="{{ $fuelRequest->spbu_price_per_liter ? 'Rp ' . number_format((float)$fuelRequest->spbu_price_per_liter * (float)($fuelRequest->actual_liters ?? $fuelRequest->total_liters), 0, ',', '.') : '-' }}" readonly disabled style="font-weight: 700; color: #15803d;">
                </div>
            </div>
            @if($fuelRequest->spbu_receipt_photo)
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #fcd34d;">
                    <div style="font-size: 12.5px; font-weight: 700; color: #92400e; margin-bottom: 8px;">Bukti Foto Nota / Struk SPBU:</div>
                    <a href="{{ route('fuel.spbu.receipt', $fuelRequest->id) }}" target="_blank" title="Klik untuk memperbesar foto nota SPBU">
                        <img src="{{ route('fuel.spbu.receipt', $fuelRequest->id) }}" alt="Nota SPBU" style="max-height: 220px; border-radius: 10px; border: 1px solid #f59e0b; box-shadow: 0 3px 10px rgba(0,0,0,0.08);">
                    </a>
                </div>
            @endif
        </div>
    @endif

    <!-- SECTION: PENCATATAN REALISASI PENGISIAN BBM OLEH VENDOR BBM -->
    @if($fuelRequest->isVendorApproved())
        <div class="digi-form-box" style="border-color: #22c55e; background: #f0fdf4;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <h3 class="digi-section-title" style="color: #15803d; margin: 0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><rect x="3" y="2" width="12" height="20" rx="2"></rect><line x1="3" y1="10" x2="15" y2="10"></line><path d="M15 7h2a2 2 0 0 1 2 2v6a2 2 0 0 0 2 2v0a2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L20.5 6.5"></path></svg>Pencatatan Realisasi Pengisian BBM ({{ $isSpbu ? 'SPBU' : 'Vendor BBM' }})
                </h3>
                <span class="digi-badge {{ $isCompleted ? 'digi-badge-green' : ($pendingUserVerify ? 'digi-badge-yellow' : 'digi-badge-blue') }}">
                    @if($isCompleted)
                        Realisasi Terverifikasi (Selesai)
                    @elseif($pendingUserVerify)
                        Realisasi Tercatat — Menunggu Verifikasi Pemohon
                    @else
                        Approved - Ready for Fueling
                    @endif
                </span>
            </div>

            @if($fuelRequest->transactions->count() > 0)
                @php $trx = $fuelRequest->transactions->first(); @endphp
                <div class="digi-form-grid-3" style="margin-bottom: 8px;">
                    <div class="digi-form-group">
                        <label class="digi-form-label">Nomor Transaksi & Waktu Aktual</label>
                        <input type="text" class="digi-input" value="{{ $trx->transaction_no }} | {{ $trx->fueled_at->format('d/m/Y H:i') }}" readonly disabled>
                    </div>
                    <div class="digi-form-group">
                        <label class="digi-form-label">Jumlah BBM Aktual vs Disetujui</label>
                        <input type="text" class="digi-input" value="{{ (float)$trx->actual_liters }} L (Disetujui: {{ (float)$trx->requested_liters }} L | Selisih: {{ (float)$trx->variance_liters }} L)" readonly disabled>
                    </div>
                    <div class="digi-form-group">
                        <label class="digi-form-label">Kilometer (KM) Aktual Saat Pengisian</label>
                        <input type="text" class="digi-input" value="{{ (float)$trx->meter_at_fueling }} KM (Oleh: {{ $trx->fuelman?->name ?? $fuelRequest->vendor_name ?? 'Vendor' }})" readonly disabled>
                    </div>
                </div>
                @if($trx->notes)
                    <div style="font-size: 13px; color: #166534; margin-top: 6px;">
                        <strong>Catatan Transaksi:</strong> {{ $trx->notes }}
                    </div>
                @endif

                @if($currentUser && ($currentUser->isVendor() || $currentUser->isFuelman()) && !$currentUser->isAdmin())
                    <div style="margin-top: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 9px 12px; font-size: 12px; color: #1e40af;">
                        <strong>Data Realisasi Terkunci:</strong> Jumlah liter aktual, odometer KM/HM, dan waktu pengisian telah dicatat & dikunci sehingga tidak dapat diubah kembali oleh Vendor.
                    </div>
                @endif
            @elseif($currentUser && app(\App\Policies\FuelRequestPolicy::class)->recordRealization($currentUser, $fuelRequest))
                <form id="vendorRealizationForm" action="{{ route('approval.realization', $fuelRequest->id) }}" method="POST" style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #86efac;">
                    @csrf
                    <div style="font-size: 13px; font-weight: 700; color: #166534; margin-bottom: 12px;">
                        Konfirmasi Realisasi Pengisian BBM (Seluruh Data Terkunci Otomatis):
                    </div>
                    <div class="realization-grid">
                        <div class="digi-form-group" style="margin-bottom: 0;">
                            <label class="digi-form-label">Jumlah Liter Aktual <span class="digi-badge digi-badge-gray" style="font-size:10px; margin-left:4px;">Terkunci</span></label>
                            <div class="digi-input-group">
                                <input type="number" step="0.1" min="0.1" max="5000" name="actual_liters" class="digi-input" value="{{ (float)$fuelRequest->total_liters }}" {{ $currentUser->isAdmin() ? '' : 'readonly' }} required style="font-weight: 700; {{ $currentUser->isAdmin() ? '' : 'background:#f1f5f9; color:#475569; cursor:not-allowed;' }}">
                                <span class="digi-input-addon">Liter</span>
                            </div>
                        </div>
                        <div class="digi-form-group" style="margin-bottom: 0;">
                            <label class="digi-form-label">KM / HM Saat Pengisian <span class="digi-badge digi-badge-gray" style="font-size:10px; margin-left:4px;">Terkunci</span></label>
                            <div class="digi-input-group">
                                <input type="number" step="0.1" name="meter_at_fueling" class="digi-input" value="{{ (float)$fuelRequest->hm_km }}" {{ $currentUser->isAdmin() ? '' : 'readonly' }} style="font-weight: 700; {{ $currentUser->isAdmin() ? '' : 'background:#f1f5f9; color:#475569; cursor:not-allowed;' }}">
                                <span class="digi-input-addon">{{ strtoupper($fuelRequest->vehicle?->meter_type ?? 'KM') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="digi-form-group" style="margin-top: 12px; margin-bottom: 12px;">
                        <label class="digi-form-label">Tanggal & Waktu Pengisian Aktual <span class="digi-badge digi-badge-gray" style="font-size:10px; margin-left:4px;">Otomatis Waktu Server</span></label>
                        <input type="datetime-local" name="fueled_at" class="digi-input" value="{{ now()->format('Y-m-d\TH:i') }}" {{ $currentUser->isAdmin() ? '' : 'readonly' }} style="{{ $currentUser->isAdmin() ? '' : 'background:#f1f5f9; color:#475569; cursor:not-allowed;' }}">
                    </div>
                    <div style="display: flex; gap: 12px; align-items: flex-end; justify-content: space-between; flex-wrap: wrap;">
                        <div class="digi-form-group" style="flex: 1; min-width: 240px; margin-bottom: 0;">
                            <label class="digi-form-label">Catatan Transaksi Pengisian</label>
                            <input type="text" name="notes" class="digi-input" placeholder="Contoh: Pengisian penuh sesuai SOP stasiun BBM" value="{{ old('notes') }}">
                        </div>
                        <button type="submit" class="btn-digi btn-digi-green" style="font-weight: 700;">
                            Simpan Realisasi & Kunci
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    <!-- SECTION: VERIFIKASI AKHIR (FINAL APPROVE) OLEH PEMOHON / DRIVER (Item 8) -->
    @if($pendingUserVerify || ($isCompleted && $fuelRequest->user_verified_at))
        @php
            $verifyTrx = $fuelRequest->transactions->first();
            $reqLv = (float) $fuelRequest->total_liters;
            $actLv = (float) ($fuelRequest->actual_liters ?? $fuelRequest->total_liters);
            $diffLv = round($actLv - $reqLv, 2);
        @endphp
        @if($isCompleted && $fuelRequest->user_verified_at)
            {{-- Panel konfirmasi selesai --}}
            <div style="margin-top: 16px; background: #f0fdf4; border: 2px solid #86efac; border-radius: 12px; padding: 16px 18px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span style="font-weight: 700; color: #15803d; font-size: 14px;">Tahap 5 — Verifikasi Akhir Selesai</span>
                    <span class="digi-badge digi-badge-green">Transaksi Ditutup</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-bottom: 10px;">
                    <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 12px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Dikonfirmasi Oleh</div>
                        <div style="font-weight: 700; color: #15803d; font-size: 13px;">{{ $fuelRequest->driver_name }}</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 12px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Waktu Verifikasi</div>
                        <div style="font-weight: 700; color: #166534; font-size: 13px;">{{ $fuelRequest->user_verified_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 12px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Liter Terisi Aktual</div>
                        <div style="font-weight: 800; color: #15803d; font-size: 16px;">{{ $actLv }} L</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 9px 12px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Selisih dari Pengajuan</div>
                        <div style="font-weight: 800; color: {{ $diffLv == 0 ? '#15803d' : ($diffLv > 0 ? '#dc2626' : '#d97706') }}; font-size: 16px;">
                            {{ $diffLv == 0 ? 'Sesuai (0 L)' : ($diffLv > 0 ? '+' . $diffLv . ' L' : $diffLv . ' L') }}
                        </div>
                    </div>
                </div>
                @if($fuelRequest->user_verification_note)
                    <div style="font-size: 12.5px; color: #166534; padding: 8px 10px; background: #dcfce7; border-radius: 6px;">
                        <strong>Catatan Pemohon:</strong> {{ $fuelRequest->user_verification_note }}
                    </div>
                @endif
            </div>
        @elseif($pendingUserVerify)
            @if($canVerifyUser)
            {{-- Form verifikasi untuk Driver/Pemohon --}}
            <div class="digi-form-box" style="border: 2px solid #f59e0b; background: #fffbeb; margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                    <h3 class="digi-section-title" style="color: #92400e; margin: 0; font-size: 14px;">
                        Tahap 5 — Verifikasi Akhir Pengisian
                    </h3>
                    <span class="digi-badge digi-badge-yellow">Menunggu Konfirmasi Anda</span>
                </div>

                <div style="font-size: 13px; color: #78350f; margin: 0 0 14px 0; line-height: 1.6; background: #fef3c7; border-left: 3px solid #f59e0b; padding: 10px 12px; border-radius: 0 6px 6px 0;">
                    Vendor BBM (<strong>{{ $fuelRequest->vendor_name }}</strong>) telah selesai mencatat realisasi pengisian. Periksa ringkasan data di bawah ini, centang pernyataan pertanggungjawaban, lalu klik tombol konfirmasi untuk menutup transaksi secara resmi.
                </div>

                {{-- Ringkasan data realisasi vendor --}}
                <div style="margin-bottom: 14px;">
                    <div style="font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ringkasan Realisasi Vendor BBM</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px;">
                        <div style="background: #fff; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px;">
                            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 3px;">Liter Disetujui</div>
                            <div style="font-size: 17px; font-weight: 800; color: #0284c7;">{{ $reqLv }} L</div>
                        </div>
                        <div style="background: #fff; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px;">
                            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 3px;">Liter Aktual Vendor</div>
                            <div style="font-size: 17px; font-weight: 800; color: #15803d;">{{ $actLv }} L</div>
                        </div>
                        <div style="background: #fff; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px;">
                            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 3px;">Selisih</div>
                            <div style="font-size: 17px; font-weight: 800; color: {{ $diffLv == 0 ? '#15803d' : ($diffLv > 0 ? '#dc2626' : '#d97706') }};">
                                {{ $diffLv == 0 ? '= 0 L' : ($diffLv > 0 ? '+' . $diffLv . ' L' : $diffLv . ' L') }}
                            </div>
                        </div>
                        @if($verifyTrx)
                        <div style="background: #fff; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px;">
                            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 3px;">KM / HM Pengisian</div>
                            <div style="font-size: 15px; font-weight: 700; color: #334155;">{{ (float)$verifyTrx->meter_at_fueling }} {{ strtoupper($fuelRequest->vehicle?->meter_type ?? 'KM') }}</div>
                        </div>
                        <div style="background: #fff; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px;">
                            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 3px;">Waktu Pengisian</div>
                            <div style="font-size: 13px; font-weight: 700; color: #334155;">{{ $verifyTrx->fueled_at?->format('d/m/Y H:i') ?? '-' }}</div>
                        </div>
                        <div style="background: #fff; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px;">
                            <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 3px;">Dicatat Oleh</div>
                            <div style="font-size: 13px; font-weight: 700; color: #334155;">{{ $verifyTrx->fuelman?->name ?? $fuelRequest->vendor_name ?? 'Vendor' }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <form action="{{ route('approval.verifyUser', $fuelRequest->id) }}" method="POST" onsubmit="return handleVerifySubmit(this);">
                    @csrf
                    {{-- Pernyataan pertanggungjawaban --}}
                    <div style="background: #ffffff; border: 1.5px solid #fcd34d; border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="confirm_responsibility" value="1" id="checkResponsibility" required
                                style="margin-top: 3px; flex-shrink: 0; width: 18px; height: 18px; accent-color: #16a34a; cursor: pointer;">
                            <span style="font-size: 13px; color: #1e293b; line-height: 1.65;">
                                <strong style="color: #92400e;">Pernyataan Pertanggungjawaban:</strong>
                                Saya, <strong>{{ $fuelRequest->driver_name }}</strong>, menyatakan bahwa pengisian BBM aktual sebesar <strong>{{ $actLv }} Liter</strong> pada unit <strong>{{ $fuelRequest->unit_code }}</strong> (Nopol: {{ $fuelRequest->police_no }}) telah diterima dan sesuai dengan jumlah yang tercatat oleh Vendor BBM. Saya bertanggung jawab penuh atas kebenaran konfirmasi penyelesaian pengisian ini.
                            </span>
                        </label>
                    </div>

                    {{-- Catatan + Tombol submit (mobile-stacked) --}}
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div class="digi-form-group" style="margin-bottom: 0;">
                            <label class="digi-form-label">Catatan Pemohon <span style="font-weight: 400; color: #64748b;">(Opsional)</span></label>
                            <input type="text" name="note" class="digi-input" placeholder="Misal: Pengisian sesuai dispenser, terima kasih">
                        </div>
                        <button type="submit" id="btnVerifySubmit" class="btn-digi btn-digi-green" style="font-weight: 700; width: 100%; justify-content: center; padding: 13px 20px; font-size: 14px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 6px; flex-shrink: 0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Konfirmasi &amp; Selesaikan Pengisian
                        </button>
                    </div>
                </form>
            </div>
            @else
            {{-- Pemberitahuan untuk pihak lain (non-driver) --}}
            <div style="margin-top: 16px; background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 10px; padding: 14px 16px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span style="font-weight: 700; color: #92400e; font-size: 13px;">Tahap 5 — Menunggu Verifikasi Akhir Pemohon</span>
                    <span class="digi-badge digi-badge-yellow">Pending</span>
                </div>
                <div style="font-size: 13px; color: #78350f; line-height: 1.6;">
                    Realisasi pengisian sebesar <strong>{{ $actLv }} L</strong> (dari pengajuan {{ $reqLv }} L) telah tercatat oleh Vendor BBM dan sedang menunggu konfirmasi Final Approve dari pemohon <strong>{{ $fuelRequest->driver_name }}</strong>.
                </div>
                @if($verifyTrx)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 7px; margin-top: 10px;">
                    <div style="background: #fff; border: 1px solid #fde68a; border-radius: 7px; padding: 8px 10px; font-size: 12px;">
                        <div style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; margin-bottom: 2px;">Liter Aktual</div>
                        <div style="font-weight: 800; color: #15803d;">{{ $actLv }} L</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #fde68a; border-radius: 7px; padding: 8px 10px; font-size: 12px;">
                        <div style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; margin-bottom: 2px;">KM / HM</div>
                        <div style="font-weight: 700; color: #334155;">{{ (float)$verifyTrx->meter_at_fueling }} {{ strtoupper($fuelRequest->vehicle?->meter_type ?? 'KM') }}</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #fde68a; border-radius: 7px; padding: 8px 10px; font-size: 12px;">
                        <div style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; margin-bottom: 2px;">Waktu Pengisian</div>
                        <div style="font-weight: 700; color: #334155;">{{ $verifyTrx->fueled_at?->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                </div>
                @endif
            </div>
            @endif
        @endif
    @endif

    <!-- SECTION: TIMELINE RIWAYAT AKTIVITAS (Requirement 9 & 16) -->
    @if($fuelRequest->histories && $fuelRequest->histories->count() > 0)
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <h3 class="digi-section-title" style="margin-bottom: 14px;">Riwayat Aktivitas &amp; Audit Trail Pengajuan</h3>
            <div style="border-left: 2px solid #cbd5e1; margin-left: 10px; padding-left: 18px; display: flex; flex-direction: column; gap: 14px;">
                @foreach($fuelRequest->histories as $hist)
                    @php
                        $rawNote = (string) ($hist->note ?? '');
                        $auditText = $rawNote;
                        $remarkLabel = null;
                        $remarkText = null;

                        if (preg_match('/^(.*?)\s*(Alasan|Catatan)\s*:\s*(.+)$/is', $rawNote, $matches)) {
                            $auditText = trim($matches[1]);
                            $remarkLabel = ucfirst(strtolower(trim($matches[2])));
                            $remarkText = trim($matches[3]);
                        }

                        $actionKey = strtolower((string) $hist->action);
                        $dotColor = '#0284c7';
                        $badgeBg = '#e0f2fe';
                        $badgeColor = '#0369a1';
                        $remarkBg = '#f8fafc';
                        $remarkBorder = '#94a3b8';
                        $remarkTitleColor = '#334155';

                        if ($actionKey === 'rejected') {
                            $dotColor = '#dc2626';
                            $badgeBg = '#fee2e2';
                            $badgeColor = '#b91c1c';
                            $remarkBg = '#fef2f2';
                            $remarkBorder = '#ef4444';
                            $remarkTitleColor = '#991b1b';
                        } elseif ($actionKey === 'adjusted_liters') {
                            $dotColor = '#d97706';
                            $badgeBg = '#fef3c7';
                            $badgeColor = '#92400e';
                            $remarkBg = '#fffbeb';
                            $remarkBorder = '#f59e0b';
                            $remarkTitleColor = '#92400e';
                        } elseif (in_array($actionKey, ['approved_spv', 'approved_manager', 'vendor_realized', 'user_verified', 'completed', 'spbu_completed'], true)) {
                            $dotColor = '#16a34a';
                            $badgeBg = '#dcfce7';
                            $badgeColor = '#15803d';
                            $remarkBg = '#f0fdf4';
                            $remarkBorder = '#22c55e';
                            $remarkTitleColor = '#166534';
                        }
                    @endphp
                    <div style="position: relative; font-size: 13px;">
                        <span style="position: absolute; left: -24px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: {{ $dotColor }}; box-shadow: 0 0 0 3px #ffffff;"></span>

                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 4px;">
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; background: {{ $badgeBg }}; color: {{ $badgeColor }};">
                                {{ strtoupper(str_replace('_', ' ', $hist->action)) }}
                            </span>
                            <span style="font-weight: 400; color: #64748b; font-size: 12px;">
                                {{ $hist->created_at?->format('d/m/Y H:i:s') }} &middot; oleh <strong style="color: #1e293b;">{{ $hist->user?->name ?? 'Sistem' }}</strong>
                            </span>
                        </div>

                        @if($auditText !== '')
                            <div style="color: #475569; line-height: 1.55;">
                                {{ $auditText }}
                            </div>
                        @endif

                        @if($remarkText !== null && $remarkText !== '')
                            <div style="margin-top: 6px; padding: 8px 12px; background: {{ $remarkBg }}; border-left: 3px solid {{ $remarkBorder }}; border-radius: 0 6px 6px 0; font-size: 12.5px; line-height: 1.5;">
                                <span style="display: inline-block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: {{ $remarkTitleColor }}; margin-right: 6px;">
                                    {{ $remarkLabel }}:
                                </span>
                                <span style="color: #1e293b; font-weight: 500;">{{ $remarkText }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@if($canApprove)
<!-- MODAL KONFIRMASI PERSETUJUAN -->
<div id="approveModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal digi-modal-lg">
        <div class="digi-modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <h3 class="digi-modal-title">Konfirmasi Persetujuan Fuel Request</h3>
            </div>
            <button type="button" class="digi-modal-close" onclick="closeApproveModal()">&times;</button>
        </div>
        <form action="{{ route('approval.approve', $fuelRequest->id) }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <div class="digi-modal-body">
                <!-- Summary Card -->
                <div class="digi-modal-request-summary">
                    <div>
                        <div class="summary-title">Nomor Pengajuan</div>
                        <div class="summary-no">{{ $fuelRequest->request_no }}</div>
                        <div class="summary-sub">{{ $fuelRequest->driver_name }} (NRP: {{ $fuelRequest->driver_nrp ?? $fuelRequest->user?->nrp ?? '-' }})</div>
                    </div>
                    <div class="summary-badges">
                        <span class="digi-badge digi-badge-blue">{{ $fuelRequest->unit_code }} ({{ $fuelRequest->police_no }})</span>
                        <span class="digi-badge digi-badge-green">{{ (float)$fuelRequest->total_liters }} Liter</span>
                    </div>
                </div>

                @if($canEditLiters)
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px; border-radius: 10px; margin-bottom: 16px;">
                        <label class="digi-form-label" style="color: #166534; font-weight: 700; margin-bottom: 6px;">
                            Pemeriksaan & Penyesuaian Jumlah Liter BBM ({{ $fuelRequest->status_spv === 'menunggu' ? 'SPV' : 'Dept Head / Manager' }})
                        </label>
                        <div class="digi-input-group">
                            <input type="number" step="0.1" min="0.1" max="5000" name="total_liters" id="approve_total_liters" class="digi-input" value="{{ (float)$fuelRequest->total_liters }}" style="font-weight: 700; font-size: 15px;" required>
                            <span class="digi-input-addon">Liter</span>
                        </div>
                        <div style="font-size: 11.5px; color: #15803d; margin-top: 6px;">
                            Kapasitas tangki unit: <strong>{{ (float)($fuelRequest->vehicle?->tank_capacity ?? 80) }} L</strong>. Anda dapat menyesuaikan kuota liter jika diperlukan sebelum diteruskan ke tahap berikutnya.
                        </div>
                    </div>
                @endif

                @if($isVendorStage && ($currentUser?->isVendor() || $currentUser?->isFuelman() || $currentUser?->isAdmin()))
                    <div class="digi-realization-card">
                        <div class="realization-header">
                            <div class="realization-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="2" width="12" height="20" rx="2"></rect><line x1="3" y1="10" x2="15" y2="10"></line><path d="M15 7h2a2 2 0 0 1 2 2v6a2 2 0 0 0 2 2v0a2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L20.5 6.5"></path></svg>
                            </div>
                            <div>
                                <div class="realization-title">Data Fisik Realisasi Pengisian (Dispenser Vendor)</div>
                                <div class="realization-subtitle">{{ $currentUser?->isAdmin() ? 'Data realisasi pengisian BBM pada dispenser vendor' : 'Seluruh data pengisian (Liter, KM/HM, dan Waktu) terkunci otomatis — Vendor hanya melakukan approval' }}</div>
                            </div>
                        </div>

                        <div class="realization-grid">
                            <div class="digi-form-group" style="margin-bottom: 0;">
                                <label class="digi-form-label">Liter Aktual Terisi <span class="digi-badge digi-badge-gray" style="font-size:10px; margin-left:4px;">Terkunci</span></label>
                                <div class="digi-input-group">
                                    <input type="number" step="0.1" min="0.1" max="5000" name="actual_liters" class="digi-input" value="{{ (float)$fuelRequest->total_liters }}" {{ $currentUser?->isAdmin() ? '' : 'readonly' }} required style="font-weight: 700; font-size: 15px; {{ $currentUser?->isAdmin() ? '' : 'background:#f1f5f9; color:#475569; cursor:not-allowed;' }}">
                                    <span class="digi-input-addon">Liter</span>
                                </div>
                            </div>
                            <div class="digi-form-group" style="margin-bottom: 0;">
                                <label class="digi-form-label">KM / HM Saat Pengisian <span class="digi-badge digi-badge-gray" style="font-size:10px; margin-left:4px;">Terkunci</span></label>
                                <div class="digi-input-group">
                                    <input type="number" step="0.1" min="0" name="meter_at_fueling" class="digi-input" value="{{ (float)$fuelRequest->hm_km }}" {{ $currentUser?->isAdmin() ? '' : 'readonly' }} style="font-weight: 700; font-size: 15px; {{ $currentUser?->isAdmin() ? '' : 'background:#f1f5f9; color:#475569; cursor:not-allowed;' }}">
                                    <span class="digi-input-addon">{{ strtoupper($fuelRequest->vehicle?->meter_type ?? 'KM') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="digi-form-group" style="margin-top: 12px; margin-bottom: 0;">
                            <label class="digi-form-label">Waktu Pengisian Selesai <span class="digi-badge digi-badge-gray" style="font-size:10px; margin-left:4px;">Otomatis Waktu Server</span></label>
                            <input type="datetime-local" name="fueled_at" class="digi-input" value="{{ now()->format('Y-m-d\TH:i') }}" {{ $currentUser?->isAdmin() ? '' : 'readonly' }} style="font-size: 13.5px; {{ $currentUser?->isAdmin() ? '' : 'background:#f1f5f9; color:#475569; cursor:not-allowed;' }}">
                        </div>

                        <div class="realization-notice">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <span>Setelah disetujui, data realisasi <strong>dikunci permanen</strong> untuk Vendor dan notifikasi <strong>Verifikasi Akhir (Final Approve)</strong> otomatis dikirim ke Pemohon / Driver.</span>
                        </div>
                    </div>
                @endif

                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Catatan Persetujuan (Opsional)</label>
                    <textarea name="note" class="digi-input" rows="2" placeholder="Tambahkan catatan persetujuan jika diperlukan..."></textarea>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeApproveModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-green" style="padding: 10px 20px; font-weight: 700;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 6px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {{ $isVendorStage ? 'Setujui & Selesaikan Pengisian' : 'Setuju' }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SESUAIKAN JUMLAH LITER OLEH SUPERVISOR / DEPT HEAD -->
@if($canEditLiters)
<div id="updateLitersModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal digi-modal-lg">
        <div class="digi-modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <h3 class="digi-modal-title" style="color: #0f172a;">Ubah Jumlah Liter Pengajuan BBM</h3>
            </div>
            <button type="button" class="digi-modal-close" onclick="closeUpdateLitersModal()">&times;</button>
        </div>
        <form action="{{ route('approval.updateLiters', $fuelRequest->id) }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <div class="digi-modal-body">
                <div class="digi-modal-request-summary">
                    <div>
                        <div class="summary-title">Informasi Pengajuan Driver</div>
                        <div class="summary-no">{{ $fuelRequest->request_no }}</div>
                        <div class="summary-sub">{{ $fuelRequest->driver_name }} (NRP: {{ $fuelRequest->driver_nrp ?? $fuelRequest->user?->nrp ?? '-' }})</div>
                    </div>
                    <div class="summary-badges">
                        <span class="digi-badge digi-badge-blue">{{ $fuelRequest->unit_code }}</span>
                        <span class="digi-badge digi-badge-yellow">Saat Ini: {{ (float)$fuelRequest->total_liters }} L</span>
                    </div>
                </div>

                <div class="digi-form-group">
                    <label class="digi-form-label">Jumlah Liter Baru (Disesuaikan {{ $fuelRequest->status_spv === 'menunggu' ? 'SPV' : 'Dept Head' }}) <span class="digi-required">*</span></label>
                    <div class="digi-input-group">
                        <input type="number" step="0.1" min="0.1" max="5000" name="total_liters" class="digi-input" value="{{ (float)$fuelRequest->total_liters }}" style="font-weight: 700; font-size: 15px;" required>
                        <span class="digi-input-addon">Liter</span>
                    </div>
                    <span class="digi-field-hint">
                        Kapasitas tangki unit: <strong>{{ (float)($fuelRequest->vehicle?->tank_capacity ?? 80) }} L</strong>. Masukkan jumlah liter yang disesuaikan untuk rute operasional.
                    </span>
                </div>

                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Alasan / Catatan Penyesuaian (Opsional)</label>
                    <textarea name="reason" class="digi-input" rows="2" placeholder="Contoh: Disesuaikan dengan batas kapasitas tangki dan rute operasional shift..."></textarea>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeUpdateLitersModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-blue" style="font-weight: 700;">Simpan Perubahan Liter</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- MODAL KONFIRMASI PENOLAKAN -->
<div id="rejectModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal">
        <div class="digi-modal-header" style="background: #fff5f5; border-bottom: 1px solid #fecaca;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <h3 class="digi-modal-title" style="color: #991b1b;">Konfirmasi Penolakan Pengajuan</h3>
            </div>
            <button type="button" class="digi-modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form action="{{ route('approval.reject', $fuelRequest->id) }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <div class="digi-modal-body">
                <div class="digi-modal-request-summary" style="background: #fff5f5; border-color: #fecaca;">
                    <div>
                        <div class="summary-title" style="color: #991b1b;">Pengajuan yang Akan Ditolak</div>
                        <div class="summary-no">{{ $fuelRequest->request_no }}</div>
                        <div class="summary-sub">{{ $fuelRequest->driver_name }} ({{ $fuelRequest->unit_code }})</div>
                    </div>
                    <div class="summary-badges">
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    </div>
                </div>

                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Alasan Penolakan <span class="digi-required">*</span></label>
                    <textarea name="note" class="digi-input is-invalid" rows="3" placeholder="Tuliskan alasan penolakan secara jelas agar diketahui oleh pemohon..." required></textarea>
                    <span class="digi-field-hint">Alasan penolakan akan dicatat pada riwayat audit trail dan dikirimkan ke pemohon.</span>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-red" style="font-weight: 700;">Tolak Pengajuan Ini</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
const userHasSignature = Boolean({{ ($currentUser && $currentUser->hasActiveSignature()) ? 1 : 0 }});

function requireApproverSignatureNotice(actionName) {
    const actionText = actionName || 'menyetujui pengajuan';
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Tanda Tangan Digital Wajib Diisi!',
            html: `Anda belum memiliki tanda tangan digital aktif di profil.<br><br>Sesuai kebijakan sistem FuelLink, Anda wajib mengunggah tanda tangan digital terlebih dahulu sebelum dapat <strong>${actionText}</strong>.`,
            confirmButtonText: 'Buka Halaman Profil',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('profile.index') }}";
            }
        });
    } else {
        if (confirm(`Anda belum memiliki tanda tangan digital aktif. Buka halaman Profil untuk mengunggah tanda tangan sekarang?`)) {
            window.location.href = "{{ route('profile.index') }}";
        }
    }
}

function checkSubmitDraft(e) {
    if (!userHasSignature) {
        e.preventDefault();
        requireApproverSignatureNotice('mengajukan Fuel Request');
        return false;
    }
    return true;
}

function openApproveModal() {
    if (!userHasSignature) {
        requireApproverSignatureNotice('menyetujui pengajuan');
        return;
    }
    document.getElementById('approveModal').style.display = 'flex';
}
function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
}
function openRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
function openUpdateLitersModal() {
    const modal = document.getElementById('updateLitersModal');
    if (modal) modal.style.display = 'flex';
}
function closeUpdateLitersModal() {
    const modal = document.getElementById('updateLitersModal');
    if (modal) modal.style.display = 'none';
}

function confirmDeleteRequest(id, reqNo) {
    // VERIFIKASI LANGKAH 1 DARI 2
    Swal.fire({
        title: 'Verifikasi Hapus (Langkah 1 / 2)',
        html: `
            <div style="text-align: left; background: #fff7ed; border: 1px solid #fed7aa; padding: 12px; border-radius: 8px; margin-bottom: 12px; color: #9a3412; font-size: 13px; line-height: 1.5;">
                <strong><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-2px; margin-right:4px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Peringatan Awal:</strong><br>
                Anda akan menghapus pengajuan BBM <strong>${reqNo}</strong> dari sistem.
            </div>
            <p style="text-align: left; font-size: 13px; color: #475569; margin: 0; line-height: 1.5;">
                Penghapusan ini ditujukan untuk membersihkan data <strong>coba-coba / salah input</strong> dan bersifat permanen. Apakah Anda yakin ingin melanjutkan ke tahap verifikasi akhir?
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Lanjut ke Verifikasi Langkah 2 &rarr;',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((step1) => {
        if (step1.isConfirmed) {
            // VERIFIKASI LANGKAH 2 DARI 2 (Wajib ketik HAPUS)
            Swal.fire({
                title: 'Verifikasi Akhir (Langkah 2 / 2)',
                html: `
                    <div style="text-align: left; background: #fef2f2; border: 1px solid #fecaca; padding: 12px; border-radius: 8px; margin-bottom: 14px; color: #991b1b; font-size: 13px; line-height: 1.5;">
                        <strong>Konfirmasi Kata Kunci:</strong><br>
                        Untuk mencegah ketidaksengajaan pada pengajuan <strong>${reqNo}</strong>, silakan ketik kata kunci <strong style="letter-spacing:1px; background:#fee2e2; padding:2px 6px; border-radius:4px;">HAPUS</strong> di bawah ini:
                    </div>
                `,
                input: 'text',
                inputPlaceholder: 'Ketik HAPUS dengan huruf kapital',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data Sekarang',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                inputValidator: (value) => {
                    if (!value || value.trim().toUpperCase() !== 'HAPUS') {
                        return 'Kata kunci belum sesuai! Ketik HAPUS dengan huruf kapital.';
                    }
                }
            }).then((step2) => {
                if (step2.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/fuel/${id}/destroy`;
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);

                    const stepInput = document.createElement('input');
                    stepInput.type = 'hidden';
                    stepInput.name = 'verification_step';
                    stepInput.value = '2';
                    form.appendChild(stepInput);

                    const keywordInput = document.createElement('input');
                    keywordInput.type = 'hidden';
                    keywordInput.name = 'confirm_keyword';
                    keywordInput.value = 'HAPUS';
                    form.appendChild(keywordInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });
}

function handleVerifySubmit(form) {
    const checkbox = form.querySelector('#checkResponsibility');
    if (checkbox && !checkbox.checked) {
        checkbox.focus();
        return false;
    }
    const btn = form.querySelector('#btnVerifySubmit');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.75';
        btn.innerHTML = 'Memproses Verifikasi...';
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const realForm = document.getElementById('vendorRealizationForm');
    if (realForm) {
        realForm.addEventListener('submit', function(e) {
            if (!userHasSignature) {
                e.preventDefault();
                requireApproverSignatureNotice('mencatat realisasi pengisian BBM');
                return false;
            }
        });
    }
});
</script>
@endsection
