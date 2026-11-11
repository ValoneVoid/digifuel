<div class="fuel-document-sheet">
    @if(!$fuelRequest->isVendorApproved())
        <div style="background: #fef9c3; border: 1px dashed #eab308; color: #854d0e; padding: 8px 12px; margin-bottom: 14px; font-size: 12px; font-weight: 700; text-align: center;">
            DOKUMEN PRATINJAU — BELUM MEMPEROLEH PERSETUJUAN AKHIR VENDOR ({{ strtoupper($fuelRequest->stage_label) }})
        </div>
    @endif

    <div class="fuel-doc-header">
        <div class="fuel-doc-brand">
            <img src="{{ asset('images/ut_header_logo.png') }}" alt="United Tractors" style="height: 38px; width: auto;">
        </div>
        <div class="fuel-doc-no">
            No : {{ $fuelRequest->request_no }}
        </div>
    </div>

    <div class="fuel-doc-title">
        FUEL REQUEST
    </div>

    <table class="fuel-doc-table">
        <tr>
            <td class="lbl">EMPLOYEE <em>(Nama & NRP)</em></td>
            <td class="col">:</td>
            <td class="val">{{ $fuelRequest->driver_name }} {{ ($fuelRequest->driver_nrp ?? $fuelRequest->user?->nrp) ? '(' . ($fuelRequest->driver_nrp ?? $fuelRequest->user?->nrp) . ')' : '' }}</td>
        </tr>
        <tr>
            <td class="lbl">UNIT CODE <em>(Kode Unit)</em></td>
            <td class="col">:</td>
            <td class="val">{{ $fuelRequest->unit_code }}</td>
        </tr>
        <tr>
            <td class="lbl">POLICE NO. <em>(No. Polisi)</em></td>
            <td class="col">:</td>
            <td class="val">{{ $fuelRequest->police_no }}</td>
        </tr>
        <tr>
            <td class="lbl">VENDOR UNIT <em>(Penyedia Unit)</em></td>
            <td class="col">:</td>
            <td class="val" style="font-weight: 600; color: #0284c7;">{{ $fuelRequest->unitVendor?->name ?? ($fuelRequest->vehicle?->unitVendor?->name ?? 'Internal UT') }}</td>
        </tr>
        <tr>
            <td class="lbl">COMPANY <em>(Perusahaan)</em></td>
            <td class="col">:</td>
            <td class="val">{{ $fuelRequest->company }}</td>
        </tr>
        <tr>
            <td class="lbl">AREA <em>(Lokasi Operasional)</em></td>
            <td class="col">:</td>
            <td class="val">{{ $fuelRequest->area }}</td>
        </tr>
        <tr>
            <td class="lbl">KM</td>
            <td class="col">:</td>
            <td class="val">
                {{ $fuelRequest->hm_km ?? 79 }} KM
                @if($fuelRequest->actual_hm_km !== null)
                    (Aktual Saat Pengisian: {{ (float)$fuelRequest->actual_hm_km }} KM)
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">DATE & TIME <em>(Tgl & Waktu)</em></td>
            <td class="col">:</td>
            <td class="val">{{ $fuelRequest->request_date->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="lbl">TOTAL <em>(Jumlah)</em></td>
            <td class="col">:</td>
            <td class="val">
                {{ (float)$fuelRequest->total_liters }} Liter
                @if($fuelRequest->actual_liters !== null)
                    — <strong>Realisasi Aktual: {{ (float)$fuelRequest->actual_liters }} Liter</strong> ({{ $fuelRequest->fueled_at?->format('d/m/Y H:i') }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">STATUS APPROVAL</td>
            <td class="col">:</td>
            <td class="val">
                @if($fuelRequest->isVendorApproved())
                    DISETUJUI / SELESAI ({{ strtoupper($fuelRequest->stage_label) }})
                @elseif($fuelRequest->overall_status === 'ditolak')
                    DITOLAK
                @else
                    MENUNGGU ({{ strtoupper($fuelRequest->stage_label) }})
                @endif
            </td>
        </tr>
    </table>

    <div class="fuel-doc-date">
        Satui, {{ $fuelRequest->request_date->format('d F Y') }}
    </div>

    <div class="fuel-doc-signatures">
        <!-- 1. Request by (Employee / Driver) -->
        <div class="fuel-doc-sig-box">
            <span class="fuel-doc-sig-title">Diajukan oleh,</span>
            @if($fuelRequest->driver_signature)
                <img src="{{ asset($fuelRequest->driver_signature) }}" alt="Driver Signature" class="fuel-doc-sig-img">
            @else
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px;">(Tanda Tangan)</div>
            @endif
            <div class="fuel-doc-sig-line"></div>
            <span class="fuel-doc-sig-name">{{ $fuelRequest->driver_name }}</span>
            <span class="fuel-doc-sig-role">(Employee)</span>
        </div>

        <!-- 2. Acknowledge by (SPV) -->
        <div class="fuel-doc-sig-box">
            <span class="fuel-doc-sig-title">Mengetahui (SPV),</span>
            @if($fuelRequest->spv_signature && $fuelRequest->status_spv === 'disetujui')
                <img src="{{ asset($fuelRequest->spv_signature) }}" alt="SPV Signature" class="fuel-doc-sig-img">
            @elseif($fuelRequest->status_spv === 'ditolak')
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:12px; font-weight:700;">[ DITOLAK ]</div>
            @else
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px;">(Menunggu Approval)</div>
            @endif
            <div class="fuel-doc-sig-line"></div>
            <span class="fuel-doc-sig-name">{{ $fuelRequest->spv ? $fuelRequest->spv->username : 'aklis' }}</span>
            <span class="fuel-doc-sig-role">(Spv/Dept. Head)</span>
        </div>

        <!-- 3. Approve by (Dept Head) -->
        <div class="fuel-doc-sig-box">
            <span class="fuel-doc-sig-title">Disetujui (Dept Head),</span>
            @if($fuelRequest->depthead_signature && $fuelRequest->status_depthead === 'disetujui')
                <img src="{{ asset($fuelRequest->depthead_signature) }}" alt="Dept Head Signature" class="fuel-doc-sig-img">
            @elseif($fuelRequest->status_depthead === 'ditolak')
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:12px; font-weight:700;">[ DITOLAK ]</div>
            @else
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px;">(Menunggu Approval)</div>
            @endif
            <div class="fuel-doc-sig-line"></div>
            <span class="fuel-doc-sig-name">{{ $fuelRequest->depthead ? $fuelRequest->depthead->name : 'Garius' }}</span>
            <span class="fuel-doc-sig-role">(Dept. Head/Manager)</span>
        </div>

        <!-- 4. Fuel Man (Vendor) -->
        <div class="fuel-doc-sig-box">
            <span class="fuel-doc-sig-title">Fuel Man,</span>
            @if(($fuelRequest->fuelman_signature || $fuelRequest->vendor_signature) && ($fuelRequest->status_fuelman === 'disetujui' || $fuelRequest->status_vendor === 'disetujui'))
                <img src="{{ asset($fuelRequest->vendor_signature ?? $fuelRequest->fuelman_signature) }}" alt="Vendor / Fuel Man Signature" class="fuel-doc-sig-img">
            @elseif($fuelRequest->status_fuelman === 'ditolak' || $fuelRequest->status_vendor === 'ditolak')
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:12px; font-weight:700;">[ DITOLAK ]</div>
            @else
                <div style="height: 90px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px;">(Menunggu Approval)</div>
            @endif
            <div class="fuel-doc-sig-line"></div>
            <span class="fuel-doc-sig-name">{{ $fuelRequest->vendor_name }}</span>
            <span class="fuel-doc-sig-role">(Vendor)</span>
        </div>
    </div>
</div>
