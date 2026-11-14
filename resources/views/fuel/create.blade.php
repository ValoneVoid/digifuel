@extends('layouts.app')

@section('title', 'Fuel Request')

@section('content')
<div class="digi-card">
    <!-- Header -->
    <div class="digi-form-header">
        <img src="{{ asset('images/gas_pump_icon.svg') }}" alt="Fuel Pump" class="digi-form-icon">
        <h1 class="digi-form-title">Fuel Request</h1>
        <p class="digi-form-subtitle">
            Lengkapi seluruh data dalam form ini untuk melakukan pengisian Fuel (Pendataan Bahan Bakar kendaraan).
        </p>
    </div>

    @if($errors->has('signature'))
        <div class="digi-signature-alert" style="margin-bottom: 20px;">
            <div class="digi-signature-alert-content">
                <div class="digi-signature-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
                <div>
                    <div class="digi-signature-alert-title">
                        <span>Tanda Tangan Digital Wajib Diisi!</span>
                        <span class="digi-signature-alert-badge">Wajib</span>
                    </div>
                    <div class="digi-signature-alert-text">
                        {{ $errors->first('signature') }}
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="digi-signature-alert-btn">
                Unggah Tanda Tangan Sekarang &rarr;
            </a>
        </div>
    @elseif(!$user->signature_url)
        <div class="digi-signature-alert">
            <div class="digi-signature-alert-content">
                <div class="digi-signature-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
                <div>
                    <div class="digi-signature-alert-title">
                        <span>Tanda Tangan Digital Wajib Diisi!</span>
                        <span class="digi-signature-alert-badge">Wajib</span>
                    </div>
                    <div class="digi-signature-alert-text">
                        Sesuai kebijakan sistem FuelLink, Anda wajib memiliki tanda tangan digital aktif sebelum dapat mengirim formulir Fuel Request.
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="digi-signature-alert-btn">
                Unggah Tanda Tangan Sekarang &rarr;
            </a>
        </div>
    @endif

    @if($errors->has('user_id') || $errors->has('employee_id'))
        <div class="digi-alert digi-alert-error" style="margin-bottom: 20px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <div>
                <strong>Kendala Status Akun:</strong> {{ $errors->first('user_id') ?? $errors->first('employee_id') }}
            </div>
        </div>
    @endif

    <!-- Compact Employee Status & Progress Strip -->
    @if(isset($myStats) && $myStats['total'] > 0)
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 18px; margin-bottom: 20px; font-size: 13px;">
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <span style="font-weight: 700; color: #334155;">Ringkasan Pengajuan Anda:</span>
                <span>Total: <strong>{{ $myStats['total'] }}</strong></span>
                <span class="digi-badge digi-badge-yellow">Menunggu: {{ $myStats['pending'] }}</span>
                <span class="digi-badge digi-badge-green">Disetujui: {{ $myStats['approved'] }}</span>
                @if($myStats['rejected'] > 0)
                    <span class="digi-badge digi-badge-red">Ditolak: {{ $myStats['rejected'] }}</span>
                @endif
            </div>
            @if($myStats['latest'])
                <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px;">
                    <span style="color: #64748b;">Terbaru (<strong>{{ $myStats['latest']->request_no }}</strong>):</span>
                    <span class="digi-badge {{ $myStats['latest']->overall_status === 'disetujui' ? 'digi-badge-green' : ($myStats['latest']->overall_status === 'ditolak' ? 'digi-badge-red' : 'digi-badge-yellow') }}">
                        {{ $myStats['latest']->stage_label }}
                    </span>
                    <a href="{{ route('approval.show', $myStats['latest']->id) }}" style="color: #0284c7; font-weight: 600; text-decoration: underline;">Detail</a>
                </div>
            @endif
        </div>
    @endif

    <form id="fuelRequestForm" action="{{ route('fuel.store') }}" method="POST">
        @csrf
        <input type="hidden" name="action_type" id="action_type" value="submit">

        <h2 class="digi-section-title">Isi Form Request</h2>

        <div class="digi-form-box">
            <!-- Row 1: Tanggal & Waktu, Nama Employee (NRP & Jabatan) -->
            <div class="digi-form-grid-2">
                <div class="digi-form-group">
                    <label class="digi-form-label">Tanggal & Waktu Pengisian <span class="digi-required">*</span></label>
                    <input type="datetime-local" name="request_date" id="request_date" class="digi-input @error('request_date') is-invalid @enderror" value="{{ old('request_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('request_date')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Employee (NRP: {{ $user->nrp ?? '-' }} | {{ $user->jabatan ?? 'Driver' }}) <span class="digi-required">*</span></label>
                    <input type="text" class="digi-input" value="{{ $user->name }}" readonly disabled>
                </div>
            </div>

            <!-- Row 2: Unit Code (with search filter), Police No (Auto-filled readonly), Company -->
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Unit Code (Kode Unit) <span class="digi-required">*</span></span>
                        <input type="text" id="unitSearchInput" placeholder="Cari unit..." onkeyup="filterUnitOptions(this.value)" style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 6px; font-size: 11px; width: 105px; font-weight: 400;">
                    </label>
                    <select name="unit_code" id="unit_code" class="digi-select @error('unit_code') is-invalid @enderror" required onchange="handleUnitChange(this)">
                        <option value="">- Pilih Unit Kode -</option>
                        @foreach($vehicles as $veh)
                            <option value="{{ $veh->unit_code }}"
                                data-police="{{ $veh->police_no }}"
                                data-type="{{ $veh->model ?? $veh->unit_type }}"
                                data-meter="{{ strtoupper($veh->meter_type ?? 'km') }}"
                                data-tank="{{ (float)($veh->tank_capacity ?? 80) }}"
                                data-default-area="{{ $veh->defaultArea?->name ?? '' }}"
                                {{ old('unit_code') === $veh->unit_code ? 'selected' : '' }}>
                                {{ $veh->unit_code }} ({{ $veh->model ?? $veh->unit_type }}) — Tangki: {{ (float)($veh->tank_capacity ?? 80) }}L
                            </option>
                        @endforeach
                    </select>
                    <span id="unitNotFoundMsg" style="display:none; color:#ef4444; font-size:11.5px; margin-top:4px;">Unit Code yang dicari tidak ditemukan.</span>
                    @error('unit_code')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Police No (No. Polisi) <span class="digi-required">*</span></label>
                    <input type="text" name="police_no" id="police_no" class="digi-input @error('police_no') is-invalid @enderror" placeholder="Terisi otomatis saat memilih Unit Code" value="{{ old('police_no') }}" readonly required>
                    @error('police_no')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Company (Perusahaan)</label>
                    <input type="text" class="digi-input" value="PT UNITED TRACTORS Tbk - SITE SATUI" readonly disabled>
                </div>
            </div>

            <!-- Row 3: Total Liter & KM -->
            <div class="digi-form-grid-2">
                <div class="digi-form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                        <label class="digi-form-label" style="margin-bottom:0;">Total (Jumlah) Liter <span class="digi-required">*</span></label>
                        <span id="tankCapacityBadge" class="digi-badge digi-badge-blue" style="font-size: 11px; display:none;">
                            Kapasitas Tangki: <strong id="tankCapacityVal">80</strong> L
                        </span>
                    </div>
                    <input type="number" step="0.1" name="total_liters" id="total_liters" class="digi-input @error('total_liters') is-invalid @enderror" placeholder="Contoh: 45 atau 60" value="{{ old('total_liters') }}" required min="0.1" max="5000" oninput="checkLiterWarning(this.value)">
                    <div id="literCapacityWarning" class="digi-field-warning" style="display:none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span>Perhatian: Jumlah liter yang diminta (<strong id="warnInputLiters">0</strong> L) melebihi kapasitas standar tangki (<strong id="warnTankLiters">0</strong> L). Pastikan angka pengisian sudah benar.</span>
                    </div>
                    @error('total_liters')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="digi-form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                        <label class="digi-form-label" id="hm_km_label" style="margin-bottom:0;">KM / HM Odometer <span class="digi-required">*</span></label>
                        <span id="meterTypeBadge" class="digi-badge digi-badge-gray" style="font-size: 11px;">KM</span>
                    </div>
                    <input type="number" step="0.1" min="0" name="hm_km" id="hm_km" class="digi-input @error('hm_km') is-invalid @enderror" placeholder="Masukkan angka odometer terkini" value="{{ old('hm_km', 79) }}" required>
                    @error('hm_km')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Memilih Approval Section -->
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
                <h3 class="digi-section-title" style="margin-bottom: 8px;">Memilih Approval</h3>
                <span class="digi-approval-badge">Approval</span>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Area Operasional <span class="digi-required">*</span></label>
                    <select name="area" id="area_select" class="digi-select @error('area') is-invalid @enderror" required onchange="handleAreaChange(this.value)">
                        <option value="">- Pilih Area Operasional -</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->name }}" {{ old('area', 'SDU (Sungai Danau)') === $area->name ? 'selected' : '' }}>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('area')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Approval SPV (Acknowledge) <span class="digi-required">*</span></label>
                    <select name="spv_id" id="spv_select" class="digi-select @error('spv_id') is-invalid @enderror" required>
                        <option value="">- Pilih Approval SPV -</option>
                        @foreach($spvs as $spv)
                            <option value="{{ $spv->id }}" {{ old('spv_id', $spvs->first()?->id) == $spv->id ? 'selected' : '' }}>
                                Approval By {{ $spv->nrp ?? $spv->username }} - {{ $spv->name }}
                            </option>
                        @endforeach
                    </select>
                    <span id="spvEmptyMsg" style="display:none; color:#b45309; font-size:12px; margin-top:4px;">⚠️ Tidak ada Approver SPV yang aktif pada area ini. Silakan hubungi Admin.</span>
                    @error('spv_id')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Approval Dept Head (Approve) <span class="digi-required">*</span></label>
                    <select name="depthead_id" id="depthead_select" class="digi-select @error('depthead_id') is-invalid @enderror" required>
                        <option value="">- Pilih Approval Dept Head -</option>
                        @foreach($deptheads as $dh)
                            <option value="{{ $dh->id }}" {{ old('depthead_id', $deptheads->first()?->id) == $dh->id ? 'selected' : '' }}>
                                Approval By {{ $dh->username }} ({{ $dh->name }})
                            </option>
                        @endforeach
                    </select>
                    <span id="dhEmptyMsg" style="display:none; color:#b45309; font-size:12px; margin-top:4px;">⚠️ Tidak ada Approver Dept Head yang aktif pada area ini. Silakan hubungi Admin.</span>
                    @error('depthead_id')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Perusahaan Vendor BBM <span class="digi-required">*</span></label>
                    <select name="vendor_name" id="vendor_select" class="digi-select @error('vendor_name') is-invalid @enderror" required>
                        <option value="">- Pilih Perusahaan Vendor BBM -</option>
                        @foreach($vendors as $ven)
                            <option value="{{ $ven->name }}" {{ old('vendor_name', 'Cv. Sudan Mandiri') === $ven->name ? 'selected' : '' }}>
                                {{ $ven->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_name')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="digi-form-group" style="margin-bottom: 20px;">
                    <label class="digi-form-label">Catatan Tambahan (Opsional)</label>
                    <input type="text" name="notes" id="notes_input" class="digi-input @error('notes') is-invalid @enderror" placeholder="Catatan operasional pengisian (jika diperlukan)" value="{{ old('notes') }}">
                    @error('notes')
                        <div class="digi-invalid-feedback">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons at bottom right -->
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; flex-wrap: wrap;">
                <button type="button" class="btn-digi btn-digi-gray" onclick="submitAsDraft()">
                    Simpan Draft
                </button>
                <button type="button" class="btn-digi btn-digi-yellow" onclick="openPreviewModal()">
                    Pratinjau
                </button>
                <button type="submit" class="btn-digi btn-digi-blue" style="min-width: 140px;" onclick="document.getElementById('action_type').value='submit'">
                    Kirim Form
                </button>
            </div>
        </div>
    </form>
</div>

<!-- MODAL PRATINJAU RINGKASAN SEBELUM KIRIM -->
<div id="previewModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal digi-modal-lg">
        <div class="digi-modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <h3 class="digi-modal-title">Pratinjau Pengajuan Fuel Request</h3>
            </div>
            <button type="button" class="digi-modal-close" onclick="closePreviewModal()">&times;</button>
        </div>
        <div class="digi-modal-body" style="font-size: 13.5px;">
            <p style="margin: 0 0 14px 0; color: #475569;">Periksa kembali ringkasan data permohonan bahan bakar Anda sebelum diajukan ke alur persetujuan:</p>
            
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b; width: 180px;">Employee (Pemohon)</td><td style="padding: 9px 14px; font-weight:700; color: #0f172a;">: {{ $user->name }} ({{ $user->nrp ?? '-' }})</td></tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b;">Perusahaan</td><td style="padding: 9px 14px; font-weight:600; color: #334155;">: PT UNITED TRACTORS Tbk - SITE SATUI</td></tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b;">Unit Code & No. Polisi</td><td style="padding: 9px 14px; font-weight:700; color: #0284c7;" id="prev_unit">: -</td></tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b;">Odometer & Jumlah BBM</td><td style="padding: 9px 14px; font-weight:700; color: #166534;" id="prev_liters">: -</td></tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b;">Area Operasional</td><td style="padding: 9px 14px; font-weight:600; color: #334155;" id="prev_area">: -</td></tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b;">Verifikasi SPV (Tahap 2)</td><td style="padding: 9px 14px; font-weight:600; color: #334155;" id="prev_spv">: -</td></tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 9px 14px; color:#64748b;">Approval Dept Head (Tahap 3)</td><td style="padding: 9px 14px; font-weight:600; color: #334155;" id="prev_depthead">: -</td></tr>
                    <tr><td style="padding: 9px 14px; color:#64748b;">Penyedia BBM (Tahap 4)</td><td style="padding: 9px 14px; font-weight:700; color: #15803d;" id="prev_vendor">: -</td></tr>
                </table>
            </div>
        </div>
        <div class="digi-modal-footer">
            <button type="button" class="btn-digi btn-digi-gray" onclick="closePreviewModal()">Periksa Kembali</button>
            <button type="button" class="btn-digi btn-digi-blue" style="font-weight: 700;" onclick="confirmSubmitForm()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                Ajukan Fuel Request
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const areaApproverMap = @json($areaApproverMap ?? []);

function handleUnitChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const policeInput = document.getElementById('police_no');
    const tankBadge = document.getElementById('tankCapacityBadge');
    const tankVal = document.getElementById('tankCapacityVal');
    const meterBadge = document.getElementById('meterTypeBadge');
    const hmKmLabel = document.getElementById('hm_km_label');

    if (selectedOption && selectedOption.dataset.police) {
        policeInput.value = selectedOption.dataset.police;
        policeInput.classList.remove('is-invalid');
    } else {
        policeInput.value = '';
    }

    if (select.value) {
        select.classList.remove('is-invalid');
    }

    // Update Meter Type Badge
    if (selectedOption && selectedOption.dataset.meter) {
        const meter = selectedOption.dataset.meter;
        if (meterBadge) meterBadge.textContent = meter;
        if (hmKmLabel) hmKmLabel.innerHTML = `${meter} Odometer <span class="digi-required">*</span>`;
    }

    // Update Tank Capacity Badge
    if (selectedOption && selectedOption.dataset.tank) {
        const tank = parseFloat(selectedOption.dataset.tank);
        if (tankBadge && tankVal) {
            tankVal.textContent = tank;
            tankBadge.style.display = 'inline-block';
        }
    } else {
        if (tankBadge) tankBadge.style.display = 'none';
    }

    checkLiterWarning(document.getElementById('total_liters').value);

    // Auto-select Default Area if assigned to the Unit
    if (selectedOption && selectedOption.dataset.defaultArea) {
        const areaSelect = document.getElementById('area_select');
        if (areaSelect) {
            for (let i = 0; i < areaSelect.options.length; i++) {
                if (areaSelect.options[i].value === selectedOption.dataset.defaultArea) {
                    areaSelect.selectedIndex = i;
                    handleAreaChange(selectedOption.dataset.defaultArea);
                    break;
                }
            }
        }
    }
}

function checkLiterWarning(val) {
    const inputLiters = parseFloat(val);
    const unitSel = document.getElementById('unit_code');
    const opt = unitSel ? unitSel.options[unitSel.selectedIndex] : null;
    const warnBox = document.getElementById('literCapacityWarning');
    const warnInput = document.getElementById('warnInputLiters');
    const warnTank = document.getElementById('warnTankLiters');

    if (!opt || !opt.dataset.tank || isNaN(inputLiters) || inputLiters <= 0) {
        if (warnBox) warnBox.style.display = 'none';
        return;
    }

    const tankCapacity = parseFloat(opt.dataset.tank);
    if (inputLiters > tankCapacity && tankCapacity > 0) {
        if (warnInput) warnInput.textContent = inputLiters;
        if (warnTank) warnTank.textContent = tankCapacity;
        if (warnBox) warnBox.style.display = 'flex';
    } else {
        if (warnBox) warnBox.style.display = 'none';
    }
}

function filterUnitOptions(keyword) {
    const select = document.getElementById('unit_code');
    const q = keyword.trim().toLowerCase();
    let matchCount = 0;

    for (let i = 1; i < select.options.length; i++) {
        const opt = select.options[i];
        const text = (opt.text + ' ' + (opt.dataset.police || '')).toLowerCase();
        const visible = !q || text.includes(q);
        opt.style.display = visible ? '' : 'none';
        if (visible) matchCount++;
    }

    const msg = document.getElementById('unitNotFoundMsg');
    if (msg) {
        msg.style.display = (q && matchCount === 0) ? 'block' : 'none';
    }
}

function populateSelect(selectId, items, valueKey, labelKey, placeholder) {
    const select = document.getElementById(selectId);
    if (!select) return;
    const prevValue = select.value;
    select.innerHTML = `<option value="">${placeholder}</option>`;

    let stillValid = false;
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valueKey];
        opt.textContent = item[labelKey];
        if (String(item[valueKey]) === String(prevValue)) {
            opt.selected = true;
            stillValid = true;
        }
        select.appendChild(opt);
    });

    if (!stillValid && items.length === 1) {
        select.selectedIndex = 1;
    }
}

function handleAreaChange(areaName) {
    const areaSel = document.getElementById('area_select');
    if (areaSel && areaSel.value) areaSel.classList.remove('is-invalid');

    if (!areaName) return;

    // Immediate update from preloaded map
    if (areaApproverMap[areaName]) {
        const data = areaApproverMap[areaName];
        populateSelect('spv_select', data.spvs, 'id', 'label', '- Pilih Approval SPV -');
        populateSelect('depthead_select', data.deptheads, 'id', 'label', '- Pilih Approval Dept Head -');
        populateSelect('vendor_select', data.vendors, 'name', 'name', '- Pilih Perusahaan Vendor BBM -');

        const spvMsg = document.getElementById('spvEmptyMsg');
        if (spvMsg) spvMsg.style.display = (!data.spvs || data.spvs.length === 0) ? 'block' : 'none';

        const dhMsg = document.getElementById('dhEmptyMsg');
        if (dhMsg) dhMsg.style.display = (!data.deptheads || data.deptheads.length === 0) ? 'block' : 'none';
    }

    // Also synchronize dynamically via Fetch API
    fetch(`/api/area-approvers?area=${encodeURIComponent(areaName)}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.spvs) {
                populateSelect('spv_select', data.spvs, 'id', 'label', '- Pilih Approval SPV -');
                populateSelect('depthead_select', data.deptheads, 'id', 'label', '- Pilih Approval Dept Head -');
                populateSelect('vendor_select', data.vendors, 'name', 'name', '- Pilih Perusahaan Vendor BBM -');

                const spvMsg = document.getElementById('spvEmptyMsg');
                if (spvMsg) spvMsg.style.display = (!data.spvs || data.spvs.length === 0) ? 'block' : 'none';

                const dhMsg = document.getElementById('dhEmptyMsg');
                if (dhMsg) dhMsg.style.display = (!data.deptheads || data.deptheads.length === 0) ? 'block' : 'none';
            }
        })
        .catch(() => {});
}

const userHasSignature = {{ $user->signature_url ? 'true' : 'false' }};

function requireSignatureNotice() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Tanda Tangan Digital Wajib Diisi!',
            html: 'Anda belum memiliki tanda tangan digital aktif di profil.<br><br>Sesuai kebijakan sistem FuelLink, seluruh pengajuan <strong>Fuel Request</strong> mewajibkan tanda tangan pemohon. Silakan unggah tanda tangan digital Anda terlebih dahulu.',
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
        if (confirm('Anda belum memiliki tanda tangan digital aktif. Buka halaman Profil untuk mengunggah tanda tangan sekarang?')) {
            window.location.href = "{{ route('profile.index') }}";
        }
    }
}

function submitAsDraft() {
    document.getElementById('action_type').value = 'draft';
    document.getElementById('fuelRequestForm').submit();
}

function openPreviewModal() {
    if (!userHasSignature) {
        requireSignatureNotice();
        return;
    }

    const unitSel = document.getElementById('unit_code');
    const police = document.getElementById('police_no').value;
    const liters = document.getElementById('total_liters').value;
    const hmKm = document.getElementById('hm_km').value;
    const area = document.getElementById('area_select').value;
    const spvSel = document.getElementById('spv_select');
    const dhSel = document.getElementById('depthead_select');
    const vendor = document.getElementById('vendor_select').value;

    document.getElementById('prev_unit').textContent = ': ' + (unitSel.value || '-') + ' / ' + (police || '-');
    document.getElementById('prev_liters').textContent = ': ' + (hmKm ? hmKm + ' KM' : '-') + ' | ' + (liters || '0') + ' Liter';
    document.getElementById('prev_area').textContent = ': ' + (area || '-');
    document.getElementById('prev_spv').textContent = ': ' + (spvSel.options[spvSel.selectedIndex]?.text || '-');
    document.getElementById('prev_depthead').textContent = ': ' + (dhSel.options[dhSel.selectedIndex]?.text || '-');
    document.getElementById('prev_vendor').textContent = ': ' + (vendor || '-');

    document.getElementById('previewModal').style.display = 'flex';
}

function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
}

function confirmSubmitForm() {
    if (!userHasSignature) {
        closePreviewModal();
        requireSignatureNotice();
        return;
    }
    document.getElementById('action_type').value = 'submit';
    document.getElementById('fuelRequestForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    // Initial trigger for tank capacity and meter type if unit already selected (e.g. on validation reload)
    const unitSelect = document.getElementById('unit_code');
    if (unitSelect && unitSelect.value) {
        handleUnitChange(unitSelect);
    }

    // Auto-scroll to first invalid element if any
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid) {
        setTimeout(() => {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstInvalid.focus === 'function') firstInvalid.focus();
        }, 150);
    }

    // Remove is-invalid state when user modifies input
    document.querySelectorAll('.digi-input, .digi-select').forEach(input => {
        input.addEventListener('input', () => input.classList.remove('is-invalid'));
        input.addEventListener('change', () => input.classList.remove('is-invalid'));
    });

    const form = document.getElementById('fuelRequestForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const action = document.getElementById('action_type').value;
            if (action === 'submit' && !userHasSignature) {
                e.preventDefault();
                requireSignatureNotice();
                return false;
            }

            // Client-side visual check on submit to immediately show red outlines on any empty required fields
            let hasClientError = false;
            let firstErrEl = null;

            const requiredFields = [
                { id: 'request_date', name: 'Tanggal & Waktu' },
                { id: 'unit_code', name: 'Unit Code' },
                { id: 'police_no', name: 'Nomor Polisi' },
                { id: 'total_liters', name: 'Total Liter' },
                { id: 'hm_km', name: 'KM / HM' },
                { id: 'area_select', name: 'Area Operasional' },
                { id: 'spv_select', name: 'Approval SPV' },
                { id: 'depthead_select', name: 'Approval Dept Head' },
                { id: 'vendor_select', name: 'Vendor BBM' },
            ];

            requiredFields.forEach(field => {
                const el = document.getElementById(field.id);
                if (el && (!el.value || el.value.trim() === '')) {
                    el.classList.add('is-invalid');
                    hasClientError = true;
                    if (!firstErrEl) firstErrEl = el;
                }
            });

            if (hasClientError) {
                e.preventDefault();
                if (firstErrEl) {
                    firstErrEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstErrEl.focus();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kolom Wajib Belum Lengkap',
                        text: 'Silakan lengkapi seluruh kolom yang bergaris merah sebelum mengirimkan formulir.',
                        confirmButtonText: 'Periksa Formulir',
                        confirmButtonColor: '#f59e0b',
                    });
                }
                return false;
            }
        });
    }
});
</script>
@endsection
