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
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; text-align: center; font-size: 12px;">
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

    <!-- Title and Action Buttons (Slide 9) -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <h2 class="digi-section-title" style="margin: 0;">Isi Form Request</h2>

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

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            @if($fuelRequest->current_stage === 'draft' && ($currentUser?->id === $fuelRequest->user_id || $currentUser?->isAdmin()))
                <form action="{{ route('fuel.submit.draft', $fuelRequest->id) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-digi btn-digi-blue">Ajukan Draft Ini ke SPV</button>
                </form>
            @endif

            @if($canApprove)
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

        <!-- Row 2 -->
        <div class="digi-form-grid-3">
            <div class="digi-form-group">
                <label class="digi-form-label">Unit Code (Kode Unit) <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->unit_code }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Police No (No. Polisi) <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->police_no }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Company (Perusahaan)</label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->company }}" readonly disabled>
            </div>
        </div>

        <!-- Row 3 -->
        <div class="digi-form-grid-2">
            <div class="digi-form-group">
                <label class="digi-form-label">Total (Jumlah) Liter Disetujui <span class="digi-required">*</span></label>
                <input type="text" class="digi-input" value="{{ (float)$fuelRequest->total_liters }} Liter" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">HM / KM Pengajuan</label>
                <input type="text" class="digi-input" value="{{ $fuelRequest->hm_km ?? 79 }} {{ strtoupper($fuelRequest->meter_type ?? 'KM') }}" readonly disabled>
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
                        <label class="digi-form-label">HM / KM Aktual Saat Pengisian</label>
                        <input type="text" class="digi-input" value="{{ (float)$trx->meter_at_fueling }} {{ strtoupper($fuelRequest->meter_type ?? 'KM') }} (Oleh: {{ $trx->fuelman?->name ?? 'Fuel Man' }})" readonly disabled>
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
                            <label class="digi-form-label">HM / KM Aktual Saat Pengisian <span class="digi-required">*</span></label>
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
@endsection

@section('scripts')
<script>
function openApproveModal() {
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
</script>
@endsection
