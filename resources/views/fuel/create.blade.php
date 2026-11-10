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

    <!-- Warning banner if no signature -->
    @if(!$user->signature_url)
        <div class="digi-signature-alert">
            <div class="digi-signature-alert-content">
                <div class="digi-signature-alert-icon">✍️</div>
                <div>
                    <div class="digi-signature-alert-title">
                        <span>Tanda Tangan Digital Wajib Diisi!</span>
                        <span class="digi-signature-alert-badge">Wajib</span>
                    </div>
                    <div class="digi-signature-alert-text">
                        Sesuai kebijakan sistem DigiFuel, Anda wajib memiliki tanda tangan digital aktif sebelum dapat mengirim formulir Fuel Request.
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="digi-signature-alert-btn">
                Unggah Tanda Tangan Sekarang &rarr;
            </a>
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
                    <input type="datetime-local" name="request_date" id="request_date" class="digi-input" value="{{ old('request_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('request_date') <span style="color:#ef4444; font-size:12px;">{{ $message }}</span> @enderror
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
                    <select name="unit_code" id="unit_code" class="digi-select" required onchange="handleUnitChange(this)">
                        <option value="">- Pilih Unit Kode -</option>
                        @foreach($vehicles as $veh)
                            <option value="{{ $veh->unit_code }}"
                                data-police="{{ $veh->police_no }}"
                                data-type="{{ $veh->model ?? $veh->unit_type }}"
                                data-meter="{{ strtoupper($veh->meter_type ?? 'km') }}"
                                data-default-area="{{ $veh->defaultArea?->name ?? '' }}"
                                {{ old('unit_code') === $veh->unit_code ? 'selected' : '' }}>
                                {{ $veh->unit_code }} ({{ $veh->model ?? $veh->unit_type }})
                            </option>
                        @endforeach
                    </select>
                    <span id="unitNotFoundMsg" style="display:none; color:#ef4444; font-size:11.5px; margin-top:4px;">Unit Code yang dicari tidak ditemukan.</span>
                    @error('unit_code') <span style="color:#ef4444; font-size:12px;">{{ $message }}</span> @enderror
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Police No (No. Polisi) <span class="digi-required">*</span></label>
                    <input type="text" name="police_no" id="police_no" class="digi-input" placeholder="Terisi otomatis dari Unit Code" value="{{ old('police_no') }}" readonly required>
                    @error('police_no') <span style="color:#ef4444; font-size:12px;">{{ $message }}</span> @enderror
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Company (Perusahaan)</label>
                    <input type="text" class="digi-input" value="PT UNITED TRACTORS Tbk - SITE SATUI" readonly disabled>
                </div>
            </div>

            <!-- Row 3: Total Liter & KM -->
            <div class="digi-form-grid-2">
                <div class="digi-form-group">
                    <label class="digi-form-label">Total (Jumlah) Liter <span class="digi-required">*</span></label>
                    <input type="number" step="0.1" name="total_liters" id="total_liters" class="digi-input" placeholder="Masukkan Total Liter" value="{{ old('total_liters') }}" required min="0.1">
                    @error('total_liters') <span style="color:#ef4444; font-size:12px;">{{ $message }}</span> @enderror
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">KM <span class="digi-required">*</span></label>
                    <input type="number" step="0.1" min="0" name="hm_km" id="hm_km" class="digi-input" placeholder="Masukkan KM" value="{{ old('hm_km', 79) }}" required>
                    @error('hm_km') <span style="color:#ef4444; font-size:12px;">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Memilih Approval Section -->
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
                <h3 class="digi-section-title" style="margin-bottom: 8px;">Memilih Approval</h3>
                <span class="digi-approval-badge">Approval</span>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Area <span class="digi-required">*</span></label>
                    <select name="area" id="area_select" class="digi-select" required onchange="handleAreaChange(this.value)">
                        <option value="">- Pilih Area Operasional -</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->name }}" {{ old('area', 'SDU (Sungai Danau)') === $area->name ? 'selected' : '' }}>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Approval SPV (Acknowledge) <span class="digi-required">*</span></label>
                    <select name="spv_id" id="spv_select" class="digi-select" required>
                        <option value="">- Pilih Approval SPV -</option>
                        @foreach($spvs as $spv)
                            <option value="{{ $spv->id }}" {{ old('spv_id', $spvs->first()?->id) == $spv->id ? 'selected' : '' }}>
                                Approval By {{ $spv->nrp ?? $spv->username }} - {{ $spv->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Approval Fuel Man <span class="digi-required">*</span></label>
                    <select name="fuelman_id" id="fuelman_select" class="digi-select" required>
                        <option value="">- Pilih Approval Fuel Man -</option>
                        @foreach($fuelmen as $fm)
                            <option value="{{ $fm->id }}" {{ old('fuelman_id', $fuelmen->first()?->id) == $fm->id ? 'selected' : '' }}>
                                Approval By {{ $fm->name }} ({{ $fm->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Pilih Approval Dept Head (Approve) <span class="digi-required">*</span></label>
                    <select name="depthead_id" id="depthead_select" class="digi-select" required>
                        <option value="">- Pilih Approval Dept Head -</option>
                        @foreach($deptheads as $dh)
                            <option value="{{ $dh->id }}" {{ old('depthead_id', $deptheads->first()?->id) == $dh->id ? 'selected' : '' }}>
                                Approval By {{ $dh->username }} ({{ $dh->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Perusahaan BBM <span class="digi-required">*</span></label>
                    <select name="vendor_name" id="vendor_select" class="digi-select" required>
                        <option value="">- Pilih Perusahaan Pengisian -</option>
                        @foreach($vendors as $ven)
                            <option value="{{ $ven->name }}" {{ old('vendor_name', 'Cv. Sudan Mandiri') === $ven->name ? 'selected' : '' }}>
                                {{ $ven->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-form-group" style="margin-bottom: 20px;">
                    <label class="digi-form-label">Catatan Tambahan (Opsional)</label>
                    <input type="text" name="notes" id="notes_input" class="digi-input" placeholder="Catatan operasional pengisian (jika diperlukan)" value="{{ old('notes') }}">
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
    <div class="digi-modal" style="max-width: 540px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Pratinjau Pengajuan Fuel Request</h3>
            <button type="button" class="digi-modal-close" onclick="closePreviewModal()">&times;</button>
        </div>
        <div class="digi-modal-body" style="font-size: 13.5px;">
            <p style="margin: 0 0 12px 0; color: #475569;">Periksa kembali ringkasan data pengajuan Anda sebelum dikirim ke alur persetujuan berjenjang:</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 6px 0; color:#64748b; width: 170px;">Employee</td><td style="font-weight:600;">: {{ $user->name }} ({{ $user->nrp ?? '-' }})</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">Perusahaan</td><td style="font-weight:600;">: PT UNITED TRACTORS Tbk - SITE SATUI</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">Unit Code & No. Polisi</td><td style="font-weight:600;" id="prev_unit">: -</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">KM & Jumlah BBM</td><td style="font-weight:600;" id="prev_liters">: -</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">Area Operasional</td><td style="font-weight:600;" id="prev_area">: -</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">Tahap 1 (SPV)</td><td style="font-weight:600;" id="prev_spv">: -</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">Tahap 2 (Dept Head)</td><td style="font-weight:600;" id="prev_depthead">: -</td></tr>
                <tr><td style="padding: 6px 0; color:#64748b;">Tahap 3 (Perusahaan BBM)</td><td style="font-weight:600;" id="prev_vendor">: -</td></tr>
            </table>
        </div>
        <div class="digi-modal-footer">
            <button type="button" class="btn-digi btn-digi-gray" onclick="closePreviewModal()">Periksa Kembali</button>
            <button type="button" class="btn-digi btn-digi-blue" onclick="confirmSubmitForm()">Ajukan Fuel Request</button>
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
    if (selectedOption && selectedOption.dataset.police) {
        policeInput.value = selectedOption.dataset.police;
    } else {
        policeInput.value = '';
    }

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
    if (!areaName) return;

    // Immediate update from preloaded map
    if (areaApproverMap[areaName]) {
        const data = areaApproverMap[areaName];
        populateSelect('spv_select', data.spvs, 'id', 'label', '- Pilih Approval SPV -');
        populateSelect('fuelman_select', data.fuelmen, 'id', 'label', '- Pilih Approval Fuel Man -');
        populateSelect('depthead_select', data.deptheads, 'id', 'label', '- Pilih Approval Dept Head -');
        populateSelect('vendor_select', data.vendors, 'name', 'name', '- Pilih Perusahaan Pengisian -');
    }

    // Also synchronize dynamically via Fetch API
    fetch(`/api/area-approvers?area=${encodeURIComponent(areaName)}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.spvs) {
                populateSelect('spv_select', data.spvs, 'id', 'label', '- Pilih Approval SPV -');
                populateSelect('fuelman_select', data.fuelmen, 'id', 'label', '- Pilih Approval Fuel Man -');
                populateSelect('depthead_select', data.deptheads, 'id', 'label', '- Pilih Approval Dept Head -');
                populateSelect('vendor_select', data.vendors, 'name', 'name', '- Pilih Perusahaan Pengisian -');
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
            html: 'Anda belum memiliki tanda tangan digital aktif di profil.<br><br>Sesuai kebijakan sistem DigiFuel, seluruh pengajuan <strong>Fuel Request</strong> mewajibkan tanda tangan pemohon. Silakan unggah tanda tangan digital Anda terlebih dahulu.',
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
    const form = document.getElementById('fuelRequestForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const action = document.getElementById('action_type').value;
            if (action === 'submit' && !userHasSignature) {
                e.preventDefault();
                requireSignatureNotice();
                return false;
            }
        });
    }
});
</script>
@endsection
