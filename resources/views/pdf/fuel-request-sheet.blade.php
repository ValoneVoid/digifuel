<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fuel Request - {{ $fuelRequest->request_no }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 24px 30px 30px 30px;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header-logo {
            width: 180px;
            vertical-align: middle;
        }

        .header-logo img {
            max-height: 38px;
            max-width: 170px;
        }

        .header-title-box {
            text-align: center;
            vertical-align: middle;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 1.5px;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-meta {
            width: 170px;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
        }

        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: left;
        }

        .meta-row {
            margin-bottom: 2px;
        }

        .meta-label {
            color: #64748b;
            font-size: 9px;
        }

        .meta-value {
            font-weight: bold;
            color: #0f172a;
        }

        /* STATUS BADGE */
        .status-container {
            width: 100%;
            margin-bottom: 12px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* SECTION TITLES */
        .section-header {
            background-color: #f1f5f9;
            border-left: 4px solid #f59e0b;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        /* DATA TABLES */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .data-table td {
            padding: 5px 6px;
            vertical-align: top;
            font-size: 10.5px;
            border-bottom: 1px solid #f1f5f9;
        }

        .data-table .label-col {
            width: 28%;
            color: #475569;
            font-weight: 600;
        }

        .data-table .colon-col {
            width: 2%;
            text-align: center;
            color: #94a3b8;
        }

        .data-table .value-col {
            width: 70%;
            color: #0f172a;
            font-weight: 500;
        }

        .two-column-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .two-column-table td.col-half {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .two-column-table td.col-half:last-child {
            padding-right: 0;
            padding-left: 8px;
        }

        .highlight-value {
            font-weight: bold;
            color: #0f172a;
        }

        .fuel-amount-highlight {
            font-size: 13px;
            font-weight: 800;
            color: #1e3a8a;
            background-color: #eff6ff;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
        }

        /* APPROVALS & SIGNATURES GRID */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .signature-cell {
            width: 25%;
            border: 1px solid #cbd5e1;
            padding: 8px 6px 10px 6px;
            text-align: center;
            vertical-align: top;
            background-color: #ffffff;
        }

        .sig-stage-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            padding-bottom: 4px;
            border-bottom: 1px dashed #e2e8f0;
            margin-bottom: 6px;
            min-height: 24px;
        }

        .sig-image-box {
            height: 70px;
            display: block;
            text-align: center;
            vertical-align: middle;
            margin: 4px auto;
        }

        .sig-image {
            max-height: 65px;
            max-width: 110px;
            display: inline-block;
        }

        .sig-placeholder {
            height: 65px;
            line-height: 65px;
            color: #94a3b8;
            font-size: 9px;
            font-style: italic;
        }

        .sig-line {
            width: 85%;
            margin: 6px auto 4px auto;
            border-bottom: 1px solid #475569;
        }

        .sig-signer-name {
            font-weight: bold;
            font-size: 10px;
            color: #0f172a;
            margin-bottom: 2px;
            word-break: break-word;
        }

        .sig-signer-role {
            font-size: 9px;
            color: #64748b;
        }

        .sig-date {
            font-size: 8.5px;
            color: #059669;
            font-weight: 600;
            margin-top: 3px;
        }

        /* FOOTER & SECURITY NOTE */
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            margin-top: 16px;
            font-size: 8.5px;
            color: #64748b;
        }

        .footer-left {
            text-align: left;
            vertical-align: top;
        }

        .footer-right {
            text-align: right;
            vertical-align: top;
        }

        .important-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 6px 10px;
            margin-top: 8px;
            font-size: 9px;
            color: #92400e;
        }
    </style>
</head>
<body>

    <!-- 1. HEADER SECTION -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="United Tractors">
                @else
                    <div style="font-weight:900; font-size:16px; color:#0f172a;">UNITED TRACTORS</div>
                @endif
            </td>
            <td class="header-title-box">
                <div class="company-name">{{ $companyName }}</div>
                <div class="doc-title">{{ $docTitle }}</div>
                <div class="doc-subtitle">Sistem Pengendalian dan Realisasi Konsumsi Bahan Bakar Minyak</div>
            </td>
            <td class="header-meta">
                <div class="meta-box">
                    <div class="meta-row">
                        <span class="meta-label">No. Pengajuan:</span><br>
                        <span class="meta-value">{{ $fuelRequest->request_no }}</span>
                    </div>
                    <div class="meta-row" style="margin-top: 4px;">
                        <span class="meta-label">Tanggal:</span><br>
                        <span class="meta-value">{{ $request['date'] }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 2. STATUS BADGE -->
    <div class="status-container">
        <span class="status-badge">
            STATUS: APPROVED — READY FOR FUELING (DISIKAPI SIAP ISI)
        </span>
    </div>

    <!-- 3. DATA SECTION: EMPLOYEE & UNIT INFORMATION -->
    <table class="two-column-table">
        <tr>
            <!-- Left Column: Identitas Pemohon -->
            <td class="col-half">
                <div class="section-header">A. Identitas Pemohon (Employee)</div>
                <table class="data-table">
                    <tr>
                        <td class="label-col">Nama Lengkap</td>
                        <td class="colon-col">:</td>
                        <td class="value-col highlight-value">{{ $driver['name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">NRP</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $driver['nrp'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Jabatan / Posisi</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $driver['position'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Departemen</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $driver['department'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Perusahaan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $fuelRequest->company ?? 'PT United Tractors Tbk - Site Satui' }}</td>
                    </tr>
                </table>
            </td>

            <!-- Right Column: Informasi Unit / Kendaraan -->
            <td class="col-half">
                <div class="section-header">B. Informasi Unit (Vehicle)</div>
                <table class="data-table">
                    <tr>
                        <td class="label-col">Unit Code</td>
                        <td class="colon-col">:</td>
                        <td class="value-col highlight-value">{{ $unit['code'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Nomor Polisi</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $unit['police_no'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Jenis Unit</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $unit['unit_type'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Area Unit</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $unit['area'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Tipe Meter</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">KM</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4. DATA SECTION: DETAIL PERMINTAAN BBM -->
    <div class="section-header">C. Rincian Permintaan Bahan Bakar Minyak</div>
    <table class="data-table" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 20%;" class="label-col">Nomor Pengajuan</td>
            <td class="colon-col">:</td>
            <td style="width: 30%;" class="value-col highlight-value">{{ $request['request_no'] }}</td>
            <td style="width: 20%;" class="label-col">Tanggal & Waktu</td>
            <td class="colon-col">:</td>
            <td style="width: 30%;" class="value-col">{{ $request['date'] }} {{ $request['time'] }}</td>
        </tr>
        <tr>
            <td class="label-col">KM</td>
            <td class="colon-col">:</td>
            <td class="value-col">
                <strong>{{ number_format($request['hm_km'], 0, ',', '.') }}</strong> KM
            </td>
            <td class="label-col">Vendor BBM Terpilih</td>
            <td class="colon-col">:</td>
            <td class="value-col highlight-value">{{ $request['vendor_name'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Total Volume Diminta</td>
            <td class="colon-col">:</td>
            <td class="value-col">
                <span class="fuel-amount-highlight">{{ number_format($request['total_liters'], 2, ',', '.') }} Liter</span>
            </td>
            <td class="label-col">Lokasi / Area Pengisian</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $request['area'] }}</td>
        </tr>
        @if(!empty($request['notes']))
        <tr>
            <td class="label-col">Catatan Tambahan</td>
            <td class="colon-col">:</td>
            <td colspan="4" class="value-col" style="font-style: italic;">
                {{ $request['notes'] }}
            </td>
        </tr>
        @endif
    </table>

    <!-- 5. APPROVAL & DIGITAL SIGNATURE MATRIX -->
    <div class="section-header">D. Matriks Persetujuan & Otorisasi Dokumen (Digital Signatures)</div>
    <table class="signatures-table">
        <tr>
            <!-- Stage 1: Pemohon (Employee / Driver) -->
            <td class="signature-cell">
                <div class="sig-stage-title">1. Diajukan Oleh</div>
                <div class="sig-image-box">
                    @if(!empty($driver['signature']))
                        <img src="{{ $driver['signature'] }}" alt="Driver Signature" class="sig-image">
                    @else
                        <div class="sig-placeholder">(Tanda Tangan Terdaftar)</div>
                    @endif
                </div>
                <div class="sig-line"></div>
                <div class="sig-signer-name">{{ $driver['name'] }}</div>
                <div class="sig-signer-role">Employee (Driver/Operator)</div>
                <div class="sig-date">{{ $request['date'] }} {{ $request['time'] }}</div>
            </td>

            <!-- Stage 2: SPV / Dept Head (Acknowledge) -->
            <td class="signature-cell">
                <div class="sig-stage-title">2. Diverifikasi (Acknowledge)</div>
                <div class="sig-image-box">
                    @if(!empty($approvals['spv']['signature']) && $approvals['spv']['is_approved'])
                        <img src="{{ $approvals['spv']['signature'] }}" alt="SPV Signature" class="sig-image">
                    @else
                        <div class="sig-placeholder">(Tanda Tangan Digital)</div>
                    @endif
                </div>
                <div class="sig-line"></div>
                <div class="sig-signer-name">{{ $approvals['spv']['name'] }}</div>
                <div class="sig-signer-role">SPV / Foreman Operasional</div>
                <div class="sig-date">
                    @if($approvals['spv']['action_at'])
                        {{ $approvals['spv']['action_at']->format('d/m/Y H:i') }}
                    @else
                        Status: {{ $approvals['spv']['status'] }}
                    @endif
                </div>
            </td>

            <!-- Stage 3: Dept Head / Manager (Approve) -->
            <td class="signature-cell">
                <div class="sig-stage-title">3. Disetujui (Approve)</div>
                <div class="sig-image-box">
                    @if(!empty($approvals['depthead']['signature']) && $approvals['depthead']['is_approved'])
                        <img src="{{ $approvals['depthead']['signature'] }}" alt="Dept Head Signature" class="sig-image">
                    @else
                        <div class="sig-placeholder">(Tanda Tangan Digital)</div>
                    @endif
                </div>
                <div class="sig-line"></div>
                <div class="sig-signer-name">{{ $approvals['depthead']['name'] }}</div>
                <div class="sig-signer-role">Dept. Head / Manager</div>
                <div class="sig-date">
                    @if($approvals['depthead']['action_at'])
                        {{ $approvals['depthead']['action_at']->format('d/m/Y H:i') }}
                    @else
                        Status: {{ $approvals['depthead']['status'] }}
                    @endif
                </div>
            </td>

            <!-- Stage 4: Vendor BBM (Final Approval) -->
            <td class="signature-cell">
                <div class="sig-stage-title">4. Vendor BBM (Ready for Fueling)</div>
                <div class="sig-image-box">
                    @if(!empty($approvals['vendor']['signature']) && $approvals['vendor']['is_approved'])
                        <img src="{{ $approvals['vendor']['signature'] }}" alt="Vendor Signature" class="sig-image">
                    @else
                        <div class="sig-placeholder">(Otorisasi Vendor)</div>
                    @endif
                </div>
                <div class="sig-line"></div>
                <div class="sig-signer-name">{{ $approvals['vendor']['name'] }}</div>
                <div class="sig-signer-role">{{ $approvals['vendor']['company'] }}</div>
                <div class="sig-date">
                    @if($approvals['vendor']['action_at'])
                        {{ $approvals['vendor']['action_at']->format('d/m/Y H:i') }}
                    @else
                        Approved for Fueling
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- 6. OPERATIONAL NOTICE -->
    <div class="important-box">
        <strong>PENTING:</strong> Lembar Fuel Request ini merupakan dokumen persetujuan resmi permintaan bahan bakar sebelum realisasi fisik di lapangan. Petugas Fuel Man wajib memverifikasi keaslian nomor pengajuan dan kecocokan unit sebelum pengisian dilakukan. Realisasi pengisian aktual dicatat tersendiri pada Berita Acara / Transaksi Fueling DigiFuel.
    </div>

    <!-- 7. FOOTER SECTION -->
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                Dokumen ini dihasilkan secara otomatis oleh <strong>DigiFuel Management System</strong>.<br>
                PT UNITED TRACTORS Tbk — Site Satui, Kalimantan Selatan.
            </td>
            <td class="footer-right">
                ID Dokumen: <code>{{ $fuelRequest->id }}/{{ $fuelRequest->request_no }}</code><br>
                Dicetak pada: {{ $generatedAt->format('d/m/Y H:i:s') }} WITA
            </td>
        </tr>
    </table>

</body>
</html>

