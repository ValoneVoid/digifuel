@extends('layouts.app')

@section('title', 'Manajemen Master Data & Audit Trail')

@section('content')
<div class="digi-card">
    <div class="digi-table-top" style="margin-bottom: 18px;">
        <div>
            <h1 class="digi-table-title">Manajemen Master Data & Konfigurasi DigiFuel</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Kelola Data Unit, Employee (DIGISIM Ready), Area, Vendor, Penugasan Approver per Area, Standar Konsumsi BBM, Harga BBM, Akun Pengguna, dan Audit Trail.
            </p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    @php
        $tabs = [
            'units' => 'Data Unit (' . $vehicles->count() . ')',
            'employees' => 'Data Employee (' . $employees->count() . ')',
            'areas' => 'Data Area (' . $areas->count() . ')',
            'vendors' => 'Data Vendor (' . $vendors->count() . ')',
            'approvers' => 'Approver per Area (' . $areaApprovers->count() . ')',
            'standards' => 'Standar BBM (' . $fuelStandards->count() . ')',
            'prices' => 'Harga BBM (' . $fuelPrices->count() . ')',
            'users' => 'User & Role (' . $users->count() . ')',
            'audit' => 'Audit Trail (' . $auditLogs->count() . ')',
        ];
    @endphp
    <div style="display: flex; gap: 8px; flex-wrap: wrap; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 22px;">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.master', ['tab' => $key]) }}" class="btn-digi {{ $tab === $key ? 'btn-digi-blue' : 'btn-digi-gray' }}" style="padding: 7px 14px; font-size: 12.5px;">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- TAB 1: UNITS / VEHICLES -->
    @if($tab === 'units')
        <form action="{{ route('admin.master.vehicle') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah / Perbarui Data Unit Kendaraan & Alat Berat</h3>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Unit Code (Unik) <span class="digi-required">*</span></label>
                    <input type="text" name="unit_code" class="digi-input" placeholder="Contoh: UT 208 DA 8191" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nomor Polisi <span class="digi-required">*</span></label>
                    <input type="text" name="police_no" class="digi-input" placeholder="Contoh: DA 8191 UT" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Model / Tipe Unit <span class="digi-required">*</span></label>
                    <input type="text" name="model" class="digi-input" placeholder="Contoh: B/U FMC JB HDX TRITON" required>
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Tipe Meter (KM / HM)</label>
                    <select name="meter_type" class="digi-select">
                        <option value="km">KM (Light Vehicle / Bus / Truck)</option>
                        <option value="hm">HM (Heavy Equipment / Alat Berat)</option>
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kapasitas Tangki (Liter)</label>
                    <input type="number" name="tank_capacity" class="digi-input" value="80">
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Unit</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Unit Code</th>
                    <th>Nomor Polisi</th>
                    <th>Tipe / Model Unit</th>
                    <th>Meter</th>
                    <th>Kapasitas Tangki</th>
                    <th>Standar Aktif</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vehicles as $v)
                    <tr>
                        <td style="font-weight: 700; color: #0284c7;">{{ $v->unit_code }}</td>
                        <td>{{ $v->police_no }}</td>
                        <td>{{ $v->model ?? $v->unit_type }}</td>
                        <td>{{ strtoupper($v->meter_type) }}</td>
                        <td>{{ (float)($v->tank_capacity ?? 80) }} L</td>
                        <td>
                            @if($v->activeFuelStandard)
                                <strong>{{ (float)$v->activeFuelStandard->standard_value }}</strong> {{ $v->activeFuelStandard->standard_type === 'liter_per_hour' ? 'L/Jam' : 'Km/L' }}
                            @else
                                -
                            @endif
                        </td>
                        <td><span class="digi-badge {{ $v->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">{{ $v->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 2: EMPLOYEES (DIGISIM READY) -->
    @if($tab === 'employees')
        <form action="{{ route('admin.master.employee') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah / Perbarui Data Employee (Lokal & Kesiapan Integrasi DIGISIM)</h3>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">NRP / Nomor Karyawan <span class="digi-required">*</span></label>
                    <input type="text" name="nrp" class="digi-input" placeholder="Contoh: 80124024" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Lengkap <span class="digi-required">*</span></label>
                    <input type="text" name="name" class="digi-input" placeholder="Nama Karyawan" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jabatan & Departemen</label>
                    <input type="text" name="position" class="digi-input" placeholder="Driver / Operator">
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Perusahaan Asal</label>
                    <input type="text" name="company" class="digi-input" value="PT UNITED TRACTORS Tbk - SITE SATUI" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Sumber Data & Ref Eksternal (DIGISIM)</label>
                    <div style="display:flex; gap:6px;">
                        <select name="source" class="digi-select" style="width:120px;">
                            <option value="local">Lokal</option>
                            <option value="digisim">DIGISIM</option>
                        </select>
                        <input type="text" name="external_reference" class="digi-input" placeholder="Ref ID simpers">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Employee</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>NRP</th>
                    <th>Nama Employee</th>
                    <th>Jabatan</th>
                    <th>Perusahaan</th>
                    <th>Sumber Integrasi</th>
                    <th>Referensi Eksternal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr>
                        <td style="font-weight:700;">{{ $emp->nrp }}</td>
                        <td>{{ $emp->name }}</td>
                        <td>{{ $emp->position }}</td>
                        <td>{{ $emp->company }}</td>
                        <td><span class="digi-badge digi-badge-blue">{{ strtoupper($emp->source) }}</span></td>
                        <td>{{ $emp->external_reference ?? '-' }}</td>
                        <td><span class="digi-badge {{ $emp->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">{{ $emp->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 3: AREAS -->
    @if($tab === 'areas')
        <form action="{{ route('admin.master.area') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah / Perbarui Area Operasional</h3>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Kode Area</label>
                    <input type="text" name="code" class="digi-input" placeholder="Contoh: SDU-01">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Area Operasional <span class="digi-required">*</span></label>
                    <input type="text" name="name" class="digi-input" placeholder="Contoh: SDU (Sungai Danau)" required>
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Area</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Kode Area</th>
                    <th>Nama Area Operasional</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areas as $a)
                    <tr>
                        <td style="font-weight:700;">{{ $a->code ?? 'AREA-' . $a->id }}</td>
                        <td>{{ $a->name }}</td>
                        <td><span class="digi-badge digi-badge-green">{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 4: VENDORS -->
    @if($tab === 'vendors')
        <form action="{{ route('admin.master.vendor') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah / Perbarui Perusahaan Vendor BBM</h3>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Perusahaan Vendor <span class="digi-required">*</span></label>
                    <input type="text" name="name" class="digi-input" placeholder="Contoh: Cv. Sudan Mandiri" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kontak Petugas & No. Telepon</label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="contact_person" class="digi-input" placeholder="Nama Kontak">
                        <input type="text" name="phone" class="digi-input" placeholder="0812xxxx">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Vendor</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Kode Vendor</th>
                    <th>Nama Perusahaan Vendor</th>
                    <th>Kontak Petugas</th>
                    <th>Telepon</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendors as $ven)
                    <tr>
                        <td style="font-weight:700;">{{ $ven->code ?? 'VND-0' . $ven->id }}</td>
                        <td>{{ $ven->name }}</td>
                        <td>{{ $ven->contact_person ?? 'Petugas Operasional' }}</td>
                        <td>{{ $ven->phone ?? '-' }}</td>
                        <td><span class="digi-badge digi-badge-green">{{ $ven->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 5: AREA APPROVERS -->
    @if($tab === 'approvers')
        <form action="{{ route('admin.master.approver') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Penugasan Approver & Petugas Berdasarkan Area (Dependent Dropdown)</h3>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Area Operasional <span class="digi-required">*</span></label>
                    <select name="area_id" class="digi-select" required>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Pengguna & Peran Tanggung Jawab <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <select name="user_id" class="digi-select" required>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->jabatan }})</option>
                            @endforeach
                        </select>
                        <select name="role_type" class="digi-select" style="width: 140px;" required>
                            <option value="spv">SPV</option>
                            <option value="manager">Dept Head/Mgr</option>
                            <option value="vendor">Vendor</option>
                            <option value="fuelman">Fuel Man</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Penugasan</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Area Operasional</th>
                    <th>Nama Petugas / Approver</th>
                    <th>Tanggung Jawab</th>
                    <th>Mulai Penugasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areaApprovers as $aa)
                    <tr>
                        <td style="font-weight:700;">{{ $aa->area?->name }}</td>
                        <td>{{ $aa->user?->name }} ({{ $aa->user?->username }})</td>
                        <td><span class="digi-badge digi-badge-blue">{{ strtoupper($aa->role_type) }}</span></td>
                        <td>{{ $aa->start_date?->format('d/m/Y') ?? '2025-01-01' }}</td>
                        <td><span class="digi-badge digi-badge-green">Aktif</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 6: FUEL STANDARDS -->
    @if($tab === 'standards')
        <form action="{{ route('admin.master.standard') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah Standar Konsumsi BBM Baru (Menyimpan Riwayat Historis)</h3>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Pilih Unit <span class="digi-required">*</span></label>
                    <select name="vehicle_id" class="digi-select" required>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->unit_code }} ({{ $v->model }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jenis Standar <span class="digi-required">*</span></label>
                    <select name="standard_type" class="digi-select" required>
                        <option value="km_per_liter">KM per Liter (Kendaraan LV / Bus)</option>
                        <option value="liter_per_hour">Liter per Jam (Alat Berat / HM)</option>
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nilai Standar & Toleransi (%) <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="number" step="0.01" name="standard_value" class="digi-input" placeholder="Contoh: 8.0" required>
                        <input type="number" step="0.1" name="tolerance_percentage" class="digi-input" value="5.0" style="width:90px;" required>
                    </div>
                </div>
            </div>
            <div class="digi-form-grid-2" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Tanggal Mulai Berlaku <span class="digi-required">*</span></label>
                    <input type="date" name="effective_start" class="digi-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Standar BBM</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Unit Code</th>
                    <th>Jenis Standar</th>
                    <th>Nilai Target</th>
                    <th>Toleransi</th>
                    <th>Periode Berlaku</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fuelStandards as $std)
                    <tr>
                        <td style="font-weight:700;">{{ $std->vehicle?->unit_code }}</td>
                        <td>{{ $std->standard_type === 'liter_per_hour' ? 'Liter per Jam (HM)' : 'KM per Liter (KM)' }}</td>
                        <td><strong>{{ (float)$std->standard_value }}</strong> {{ $std->standard_type === 'liter_per_hour' ? 'L/Jam' : 'Km/L' }}</td>
                        <td>{{ (float)$std->tolerance_percentage }}%</td>
                        <td>{{ $std->effective_start?->format('d/m/Y') }} — {{ $std->effective_end ? $std->effective_end->format('d/m/Y') : 'Sekarang' }}</td>
                        <td><span class="digi-badge {{ $std->is_active ? 'digi-badge-green' : 'digi-badge-gray' }}">{{ $std->is_active ? 'Aktif' : 'Historis' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 7: FUEL PRICES -->
    @if($tab === 'prices')
        <form action="{{ route('admin.master.price') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah Riwayat Harga BBM per Liter</h3>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Jenis BBM & Harga per Liter (IDR) <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="fuel_type" class="digi-input" value="Solar Industri B35" required>
                        <input type="number" step="1" name="price_per_liter" class="digi-input" value="27000" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Tanggal Mulai Berlaku <span class="digi-required">*</span></label>
                    <input type="date" name="effective_start" class="digi-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Harga BBM</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Jenis BBM</th>
                    <th>Harga per Liter</th>
                    <th>Mulai Berlaku</th>
                    <th>Akhir Berlaku</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fuelPrices as $fp)
                    <tr>
                        <td style="font-weight:700;">{{ $fp->fuel_type }}</td>
                        <td><strong>Rp {{ number_format($fp->price_per_liter, 0, ',', '.') }}</strong> / Liter</td>
                        <td>{{ $fp->effective_start?->format('d/m/Y') }}</td>
                        <td>{{ $fp->effective_end ? $fp->effective_end->format('d/m/Y') : 'Sekarang' }}</td>
                        <td><span class="digi-badge {{ $fp->is_active ? 'digi-badge-green' : 'digi-badge-gray' }}">{{ $fp->is_active ? 'Berlaku Aktif' : 'Historis' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 8: USERS & ROLES -->
    @if($tab === 'users')
        <form action="{{ route('admin.master.user') }}" method="POST" class="digi-form-box">
            @csrf
            <h3 class="digi-section-title">Tambah / Kelola Akun Pengguna & Role</h3>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Lengkap & NRP <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="name" class="digi-input" placeholder="Nama" required>
                        <input type="text" name="nrp" class="digi-input" placeholder="NRP" style="width:110px;">
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Username & Email <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="username" class="digi-input" placeholder="Username" required>
                        <input type="email" name="email" class="digi-input" placeholder="Email" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Role & Jabatan <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <select name="role" class="digi-select" required>
                            <option value="driver">Employee / Driver</option>
                            <option value="spv">SPV / Dept Head</option>
                            <option value="depthead">Dept Head / Manager</option>
                            <option value="vendor">Vendor</option>
                            <option value="fuelman">Fuel Man</option>
                            <option value="admin">Admin</option>
                        </select>
                        <input type="text" name="jabatan" class="digi-input" placeholder="Jabatan" required>
                    </div>
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom:0; align-items:flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Perusahaan Vendor Terkait (Khusus Role Vendor)</label>
                    <select name="vendor_id" class="digi-select">
                        <option value="">- Internal PT United Tractors -</option>
                        @foreach($vendors as $ven)
                            <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Password Baru (Default: password123)</label>
                    <input type="password" name="password" class="digi-input" placeholder="Kosongkan untuk default">
                </div>
                <div>
                    <button type="submit" class="btn-digi btn-digi-blue">Simpan Akun Pengguna</button>
                </div>
            </div>
        </form>

        <table class="digi-table">
            <thead>
                <tr>
                    <th>Nama Pengguna</th>
                    <th>Username</th>
                    <th>NRP</th>
                    <th>Role Sistem</th>
                    <th>Jabatan</th>
                    <th>Vendor Terkait</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                        <td style="font-weight:700;">{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->nrp ?? '-' }}</td>
                        <td><span class="digi-badge digi-badge-blue">{{ strtoupper($u->role) }}</span></td>
                        <td>{{ $u->jabatan }}</td>
                        <td>{{ $u->vendor?->name ?? 'Internal UT' }}</td>
                        <td><span class="digi-badge {{ $u->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TAB 9: AUDIT TRAIL -->
    @if($tab === 'audit')
        <h3 class="digi-section-title">Log Aktivitas & Audit Trail Sistem (100 Aktivitas Terbaru)</h3>
        <table class="digi-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>No. Pengajuan</th>
                    <th>Transisi Status</th>
                    <th>Keterangan Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs as $log)
                    <tr>
                        <td style="white-space:nowrap;">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td style="font-weight:600;">{{ $log->user?->name ?? 'Sistem' }}</td>
                        <td><span class="digi-badge digi-badge-blue">{{ strtoupper($log->action) }}</span></td>
                        <td>
                            @if($log->fuelRequest)
                                <a href="{{ route('approval.show', $log->fuelRequest->id) }}" style="color:#0284c7; font-weight:700;">{{ $log->fuelRequest->request_no }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $log->from_status ?? '-' }} → {{ $log->to_status ?? '-' }}</td>
                        <td>{{ $log->note }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:24px; color:#94a3b8;">Belum ada catatan audit trail.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>
@endsection

