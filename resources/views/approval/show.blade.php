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
        $fuelingDone = $fuelRequest->current_stage === 'completed' || $fuelRequest->transactions->count() > 0;
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
            <div style="color: #475569;">{{ $fuelRequest->spv?->name ?? 'Aklis' }}</div>
            <span class="digi-badge {{ $spvDone ? 'digi-badge-green' : ($fuelRequest->status_spv === 'ditolak' ? 'digi-badge-red' : 'digi-badge-yellow') }}" style="margin-top:4px;">
                {{ ucfirst($fuelRequest->status_spv) }}
            </span>
        </div>
        <div>
            <div style="font-weight: 700; color: {{ $dhDone ? '#15803d' : ($fuelRequest->status_depthead === 'ditolak' ? '#b91c1c' : '#475569') }};">3. Dept Head / Mgr</div>
            <div style="color: #475569;">{{ $fuelRequest->depthead?->name ?? 'Garius' }}</div>
            <span class="digi-badge {{ $dhDone ? 'digi-badge-green' : ($fuelRequest->status_depthead === 'ditolak' ? 'digi-badge-red' : ($fuelRequest->status_depthead === 'menunggu' ? 'digi-badge-yellow' : 'digi-badge-gray')) }}" style="margin-top:4px;">
                {{ $fuelRequest->status_depthead === '-' ? 'Menunggu Tahap 2' : ucfirst($fuelRequest->status_depthead) }}
            </span>
        </div>
        <div>
            <div style="font-weight: 700; color: {{ $vendorDone ? '#15803d' : ($fuelRequest->status_fuelman === 'ditolak' ? '#b91c1c' : '#475569') }};">4. Persetujuan Vendor</div>
            <div style="color: #475569;">{{ $fuelRequest->vendor_name }}</div>
            <span class="digi-badge {{ $vendorDone ? 'digi-badge-green' : ($fuelRequest->status_fuelman === 'ditolak' ? 'digi-badge-red' : ($fuelRequest->status_fuelman === 'menunggu' ? 'digi-badge-yellow' : 'digi-badge-gray')) }}" style="margin-top:4px;">
                {{ $vendorDone ? 'Approved' : ($fuelRequest->status_fuelman === 'menunggu' ? 'Menunggu Vendor' : 'Menunggu Tahap 3') }}
            </span>
        </div>
        <div>
            <div style="font-weight: 700; color: {{ $fuelingDone ? '#15803d' : '#475569' }};">5. Realisasi Fuel Man</div>
            <div style="color: #475569;">{{ $fuelRequest->fuelman?->name ?? 'ADYA' }}</div>
            <span class="digi-badge {{ $fuelingDone ? 'digi-badge-green' : ($vendorDone ? 'digi-badge-blue' : 'digi-badge-gray') }}" style="margin-top:4px;">
                {{ $fuelingDone ? 'Selesai (' . (float)($fuelRequest->actual_liters ?? $fuelRequest->total_liters) . ' L)' : ($vendorDone ? 'Siap Diisi' : 'Belum Siap') }}
            </span>
        </div>
    </div>

    @php
        $currentUser = auth()->user();
        $canApprove = false;
        $activeStageLabel = '';

        if ($currentUser && !$isRejected && $fuelRequest->current_stage !== 'completed') {
            // Stage 1: SPV
            if (($currentUser->isSpv() || $currentUser->isAdmin()) && $fuelRequest->status_spv === 'menunggu') {
                $canApprove = true;
                $activeStageLabel = 'Tahap 1: SPV';
            }
            // Stage 2: Dept Head / Manager (only after SPV approved!)
            elseif (($currentUser->isManager() || $currentUser->isAdmin()) && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead !== 'disetujui') {
                $canApprove = true;
                $activeStageLabel = 'Tahap 2: Dept Head/Manager';
            }
            // Stage 3: Vendor (only after SPV & Dept Head approved!)
            elseif (($currentUser->isVendor() || $currentUser->isFuelman() || $currentUser->isAdmin()) && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead === 'disetujui' && !$fuelRequest->isVendorApproved()) {
                $canApprove = true;
                $activeStageLabel = 'Tahap 3: Vendor Final Approval';
            }
        }
    @endphp

    <!-- Warning banner if approver / driver / fuelman has no signature -->
    @if($currentUser && !$currentUser->hasActiveSignature() && ($canApprove || ($fuelRequest->current_stage === 'draft' && $currentUser->id === $fuelRequest->user_id) || ($vendorDone && ($currentUser->isFuelman() || $currentUser->isAdmin()))))
        <div class="digi-warning-banner" style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">⚠️</span>
                <div>
                    <div style="font-weight: 700; font-size: 14px;">Tanda Tangan Digital Wajib Diisi!</div>
                    <div style="font-size: 13px; color: #78350f; margin-top: 2px;">
                        Anda belum memiliki tanda tangan digital aktif. Sesuai kebijakan sistem DigiFuel, seluruh aksi persetujuan (Approval) dan pengajuan BBM mewajibkan tanda tangan digital yang sah.
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="btn-digi btn-digi-yellow" style="padding: 8px 16px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap;">
                Unggah Tanda Tangan Sekarang &rarr;
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
                @if($fuelRequest->status_spv === 'menunggu' && ($currentUser->isSpv() || $currentUser->isAdmin()))
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
                <button type="button" class="btn-digi btn-digi-red" style="padding: 9px 16px;" onclick="confirmDeleteRequest({{ $fuelRequest->id }}, '{{ $fuelRequest->request_no }}')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Hapus Pengajuan
                </button>
            @endif

            @if($currentUser?->isDriver() && !$currentUser?->isAdmin())
                <a href="{{ route('history.index') }}" class="btn-digi btn-digi-gray" style="padding: 9px 16px;">
                    &larr; Riwayat Saya
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
                    @if($fuelRequest->status_spv === 'menunggu' && ($currentUser?->isSpv() || $currentUser?->isAdmin()))
                        <button type="button" onclick="openUpdateLitersModal()" class="btn-digi btn-digi-yellow" style="padding: 2px 10px; font-size: 11.5px; height: auto;">
                            ✎ Ubah Liter
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
                        Jumlah liter BBM yang diajukan oleh driver. Supervisor berhak memeriksa dan menyesuaikan jumlah liter sebelum persetujuan.
                    @else
                        Jumlah liter BBM yang telah disetujui untuk pengisian unit ini.
                    @endif
                </span>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">KM</label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->hm_km ?? 79 }} KM" readonly disabled>
            </div>
        </div>

        <!-- Memilih Approval Section -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
            <h3 class="digi-section-title" style="margin-bottom: 8px;">Status Berjenjang Approval</h3>
            <span class="digi-approval-badge">Approval</span>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Area Operasional <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->area }}" readonly disabled>
            </div>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Tahap 1 — Approval SPV (Acknowledge) <span class="digi-required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" class="digi-input" value="{{ $fuelRequest->spv_name }} {{ $fuelRequest->spv_action_at ? '(' . $fuelRequest->spv_action_at->format('d/m/Y H:i') . ')' : '' }}" readonly disabled>
                    @if($fuelRequest->status_spv === 'disetujui')
                        <span class="digi-badge digi-badge-green">Disetujui</span>
                    @elseif($fuelRequest->status_spv === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @else
                        <span class="digi-badge digi-badge-yellow">Menunggu..</span>
                    @endif
                </div>
                @if($fuelRequest->spv_note)
                    <span style="font-size: 12px; color: #475569; margin-top: 4px;">Catatan SPV: {{ $fuelRequest->spv_note }}</span>
                @endif
            </div>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Tahap 2 — Approval Dept Head / Manager (Approve) <span class="digi-required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" class="digi-input" value="{{ $fuelRequest->depthead_name }} {{ $fuelRequest->depthead_action_at ? '(' . $fuelRequest->depthead_action_at->format('d/m/Y H:i') . ')' : '' }}" readonly disabled>
                    @if($fuelRequest->status_depthead === 'disetujui')
                        <span class="digi-badge digi-badge-green">Disetujui</span>
                    @elseif($fuelRequest->status_depthead === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @elseif($fuelRequest->status_depthead === 'menunggu')
                        <span class="digi-badge digi-badge-yellow">Menunggu..</span>
                    @else
                        <span style="color:#94a3b8; font-size:13px; font-weight:600;">(Menunggu Tahap 1)</span>
                    @endif
                </div>
                @if($fuelRequest->depthead_note)
                    <span style="font-size: 12px; color: #475569; margin-top: 4px;">Catatan Dept Head: {{ $fuelRequest->depthead_note }}</span>
                @endif
            </div>

            <div class="digi-form-group" style="margin-bottom: 16px;">
                <label class="digi-form-label">Tahap 3 — Persetujuan Akhir Vendor & Petugas Fuel Man <span class="digi-required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" class="digi-input" value="{{ $fuelRequest->vendor_name }} — {{ $fuelRequest->fuelman_name }} {{ $fuelRequest->fuelman_action_at ? '(' . $fuelRequest->fuelman_action_at->format('d/m/Y H:i') . ')' : '' }}" readonly disabled>
                    @if($fuelRequest->status_fuelman === 'disetujui' || $fuelRequest->status_vendor === 'disetujui')
                        <span class="digi-badge digi-badge-green">Disetujui</span>
                    @elseif($fuelRequest->status_fuelman === 'ditolak' || $fuelRequest->status_vendor === 'ditolak')
                        <span class="digi-badge digi-badge-red">Ditolak</span>
                    @elseif($fuelRequest->status_fuelman === 'menunggu' || $fuelRequest->status_vendor === 'menunggu')
                        <span class="digi-badge digi-badge-yellow">Menunggu..</span>
                    @else
                        <span style="color:#94a3b8; font-size:13px; font-weight:600;">(Menunggu Tahap 2)</span>
                    @endif
                </div>
                @if($fuelRequest->vendor_note || $fuelRequest->fuelman_note)
                    <span style="font-size: 12px; color: #475569; margin-top: 4px;">Catatan Vendor/Fuel Man: {{ $fuelRequest->vendor_note ?? $fuelRequest->fuelman_note }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- SECTION: PENCATATAN REALISASI PENGISIAN BBM OLEH FUEL MAN (Requirement 13) -->
    @if($fuelRequest->isVendorApproved())
        <div class="digi-form-box" style="border-color: #22c55e; background: #f0fdf4;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <h3 class="digi-section-title" style="color: #15803d; margin: 0;">
                    ⛽ Pencatatan Realisasi Pengisian BBM (Fuel Man)
                </h3>
                <span class="digi-badge digi-badge-green">
                    {{ $fuelRequest->transactions->count() > 0 ? 'Realisasi Tercatat' : 'Approved - Ready for Fueling' }}
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
                        <input type="text" class="digi-input" value="{{ (float)$trx->meter_at_fueling }} KM (Oleh: {{ $trx->fuelman?->name ?? 'Fuel Man' }})" readonly disabled>
                    </div>
                </div>
                @if($trx->notes)
                    <div style="font-size: 13px; color: #166534; margin-top: 6px;">
                        <strong>Catatan Transaksi:</strong> {{ $trx->notes }}
                    </div>
                @endif
            @endif

            @if($currentUser && ($currentUser->isFuelman() || $currentUser->isAdmin()))
                <form action="{{ route('approval.realization', $fuelRequest->id) }}" method="POST" style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #86efac;">
                    @csrf
                    <div style="font-size: 13px; font-weight: 700; color: #166534; margin-bottom: 10px;">
                        {{ $fuelRequest->transactions->count() > 0 ? 'Perbarui / Tambah Catatan Realisasi Pengisian:' : 'Input Realisasi Pengisian BBM Aktual:' }}
                    </div>
                    <div class="digi-form-grid-3">
                        <div class="digi-form-group">
                            <label class="digi-form-label">Jumlah Liter Aktual <span class="digi-required">*</span></label>
                            <input type="number" step="0.1" min="0.1" name="actual_liters" class="digi-input" value="{{ old('actual_liters', (float)($fuelRequest->actual_liters ?? $fuelRequest->total_liters)) }}" required>
                        </div>
                        <div class="digi-form-group">
                            <label class="digi-form-label">Kilometer (KM) Aktual Saat Pengisian <span class="digi-required">*</span></label>
                            <input type="number" step="0.1" min="0" name="meter_at_fueling" class="digi-input" value="{{ old('meter_at_fueling', (float)($fuelRequest->actual_hm_km ?? $fuelRequest->hm_km)) }}" required>
                        </div>
                        <div class="digi-form-group">
                            <label class="digi-form-label">Tanggal & Waktu Aktual <span class="digi-required">*</span></label>
                            <input type="datetime-local" name="fueled_at" class="digi-input" value="{{ old('fueled_at', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: flex-end; justify-content: space-between; flex-wrap: wrap;">
                        <div class="digi-form-group" style="flex: 1; min-width: 240px;">
                            <label class="digi-form-label">Catatan Transaksi Pengisian</label>
                            <input type="text" name="notes" class="digi-input" placeholder="Contoh: Pengisian penuh sesuai SOP stasiun BBM" value="{{ old('notes') }}">
                        </div>
                        <button type="submit" class="btn-digi btn-digi-green">
                            Simpan Realisasi Pengisian
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    <!-- SECTION: TIMELINE RIWAYAT AKTIVITAS (Requirement 9 & 16) -->
    @if($fuelRequest->histories && $fuelRequest->histories->count() > 0)
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <h3 class="digi-section-title" style="margin-bottom: 12px;">Riwayat Aktivitas & Audit Trail Pengajuan</h3>
            <div style="border-left: 2px solid #0284c7; margin-left: 10px; padding-left: 18px; display: flex; flex-direction: column; gap: 12px;">
                @foreach($fuelRequest->histories as $hist)
                    <div style="position: relative; font-size: 13px;">
                        <span style="position: absolute; left: -24px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #0284c7;"></span>
                        <div style="font-weight: 700; color: #1e293b;">
                            {{ strtoupper(str_replace('_', ' ', $hist->action)) }}
                            <span style="font-weight: 400; color: #64748b; font-size: 12px; margin-left: 6px;">
                                — {{ $hist->created_at?->format('d/m/Y H:i:s') }} oleh <strong>{{ $hist->user?->name ?? 'Sistem' }}</strong>
                            </span>
                        </div>
                        <div style="color: #475569; margin-top: 2px;">{{ $hist->note }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@if($canApprove)
<!-- MODAL KONFIRMASI PERSETUJUAN (Slide 9) -->
<div id="approveModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Konfirmasi Persetujuan</h3>
            <button type="button" class="digi-modal-close" onclick="closeApproveModal()">&times;</button>
        </div>
        <form action="{{ route('approval.approve', $fuelRequest->id) }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <div class="digi-modal-body">
                <p style="margin: 0 0 12px 0;">Apakah Anda yakin ingin menyetujui permintaan <strong>{{ $fuelRequest->request_no }}</strong>?</p>

                @if($fuelRequest->status_spv === 'menunggu' && ($currentUser?->isSpv() || $currentUser?->isAdmin()))
                    <div class="digi-form-group" style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; margin-bottom: 14px;">
                        <label class="digi-form-label" style="color: #166534; font-weight: 700;">
                            Pemeriksaan & Penyesuaian Jumlah Liter (BBM)
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" step="0.1" min="0.1" max="5000" name="total_liters" id="approve_total_liters" class="digi-input" value="{{ (float)$fuelRequest->total_liters }}" style="font-weight: 700; font-size: 15px;" required>
                            <span style="font-weight: 700; color: #166534;">Liter</span>
                        </div>
                        <div style="font-size: 11.5px; color: #15803d; margin-top: 5px;">
                            Kapasitas tangki unit: <strong>{{ (float)($fuelRequest->vehicle?->tank_capacity ?? 80) }} L</strong>. Anda dapat menyesuaikan jumlah liter jika kuota yang diminta driver perlu diubah.
                        </div>
                    </div>
                @endif

                <div class="digi-form-group">
                    <label class="digi-form-label">Catatan Persetujuan (Opsional)</label>
                    <textarea name="note" class="digi-input" rows="2" placeholder="Tambahkan catatan persetujuan jika diperlukan..."></textarea>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeApproveModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-green">Setuju</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SESUAIKAN JUMLAH LITER OLEH SUPERVISOR -->
@if($fuelRequest->status_spv === 'menunggu' && ($currentUser?->isSpv() || $currentUser?->isAdmin()))
<div id="updateLitersModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 480px;">
        <div class="digi-modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <h3 class="digi-modal-title" style="color: #0f172a;">Ubah Jumlah Liter Pengajuan BBM</h3>
            <button type="button" class="digi-modal-close" onclick="closeUpdateLitersModal()">&times;</button>
        </div>
        <form action="{{ route('approval.updateLiters', $fuelRequest->id) }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <div class="digi-modal-body">
                <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 12.5px; color: #0369a1; line-height: 1.5;">
                    <strong>Informasi Pengajuan Driver:</strong><br>
                    Driver <strong>{{ $fuelRequest->driver_name }}</strong> mengajukan <strong>{{ (float)$fuelRequest->total_liters }} Liter</strong> untuk unit <strong>{{ $fuelRequest->unit_code }}</strong> (Kapasitas Tangki: <strong>{{ (float)($fuelRequest->vehicle?->tank_capacity ?? 80) }} L</strong>).
                </div>

                <div class="digi-form-group">
                    <label class="digi-form-label">Jumlah Liter Baru <span class="digi-required">*</span></label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="number" step="0.1" min="0.1" max="5000" name="total_liters" class="digi-input" value="{{ (float)$fuelRequest->total_liters }}" style="font-weight: 700; font-size: 15px;" required>
                        <span style="font-weight: 700; color: #334155;">Liter</span>
                    </div>
                    <span style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: block;">
                        Masukkan jumlah liter yang disesuaikan oleh Supervisor.
                    </span>
                </div>

                <div class="digi-form-group" style="margin-bottom: 0;">
                    <label class="digi-form-label">Alasan / Catatan Penyesuaian (Opsional)</label>
                    <textarea name="reason" class="digi-input" rows="2" placeholder="Contoh: Disesuaikan dengan batas kapasitas tangki dan rute operasional shift..."></textarea>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeUpdateLitersModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-blue">Simpan Perubahan Liter</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- MODAL KONFIRMASI PENOLAKAN -->
<div id="rejectModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Konfirmasi Penolakan</h3>
            <button type="button" class="digi-modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form action="{{ route('approval.reject', $fuelRequest->id) }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            @csrf
            <div class="digi-modal-body">
                <p style="margin: 0 0 12px 0;">Apakah Anda yakin ingin menolak permintaan ini?</p>
                <div class="digi-form-group">
                    <label class="digi-form-label">Alasan Penolakan <span class="digi-required">*</span></label>
                    <textarea name="note" class="digi-input" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-red">Tolak</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
const userHasSignature = {{ ($currentUser && $currentUser->hasActiveSignature()) ? 'true' : 'false' }};

function requireApproverSignatureNotice(actionName) {
    const actionText = actionName || 'menyetujui pengajuan';
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Tanda Tangan Digital Wajib Diisi!',
            html: `Anda belum memiliki tanda tangan digital aktif di profil.<br><br>Sesuai kebijakan sistem DigiFuel, Anda wajib mengunggah tanda tangan digital terlebih dahulu sebelum dapat <strong>${actionText}</strong>.`,
            confirmButtonText: 'Buka Halaman Profil',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
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
                <strong>⚠️ Peringatan Awal:</strong><br>
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

document.addEventListener('DOMContentLoaded', function() {
    const realForm = document.querySelector('form[action="{{ route('approval.realization', $fuelRequest->id) }}"]');
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
