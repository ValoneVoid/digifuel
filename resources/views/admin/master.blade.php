@extends('layouts.app')

@section('title', 'Manajemen Master Data & Audit Trail')

@section('content')
<div class="digi-card">
    <div class="digi-table-top" style="margin-bottom: 18px;">
        <div>
            <h1 class="digi-table-title">Manajemen Master Data & Konfigurasi DigiFuel</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Kelola Data Unit, Employee (DIGISIM Ready), Area, Vendor BBM, Penugasan Approver per Area, Standar Konsumsi BBM, Harga BBM per Vendor, Akun Pengguna, dan Audit Trail.
            </p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    @php
        $getCount = fn($col) => method_exists($col, 'total') ? $col->total() : (is_countable($col) ? count($col) : 0);
        $tabs = [
            'units' => 'Data Unit (' . $getCount($vehicles) . ')',
            'employees' => 'Data Employee (' . $getCount($employees) . ')',
            'areas' => 'Data Area (' . $getCount($areas) . ')',
            'vendors' => 'Data Vendor BBM (' . $getCount($vendors) . ')',
            'approvers' => 'Approver Area (' . $getCount($areaApprovers) . ')',
            'standards' => 'Standar BBM (' . $getCount($fuelStandards) . ')',
            'prices' => 'Harga BBM (' . $getCount($fuelPrices) . ')',
            'users' => 'User & Role (' . $getCount($users) . ')',
            'backgrounds' => 'Background Sistem (' . $getCount($backgrounds ?? collect()) . ')',
            'audit' => 'Audit Trail (' . $getCount($auditLogs) . ')',
        ];
    @endphp
    <div style="display: flex; gap: 8px; flex-wrap: wrap; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 22px;">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.master', array_merge(request()->query(), ['tab' => $key, 'page' => 1])) }}" class="btn-digi {{ $tab === $key ? 'btn-digi-blue' : 'btn-digi-gray' }}" style="padding: 7px 14px; font-size: 12.5px;">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- ============================================================ -->
    <!-- TAB 1: UNITS / VEHICLES                                      -->
    <!-- ============================================================ -->
    @if($tab === 'units')
        <form id="unitForm" action="{{ route('admin.master.vehicle') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="unit_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="unitFormTitle" style="margin: 0;">Tambah Data Unit Kendaraan & Alat Berat</h3>
                <button type="button" id="unitCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetUnitForm()">
                    Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Unit Code (Unik) <span class="digi-required">*</span></label>
                    <input type="text" name="unit_code" id="unit_code" class="digi-input" placeholder="Contoh: UT 208 DA 8191" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nomor Polisi <span class="digi-required">*</span></label>
                    <input type="text" name="police_no" id="unit_police_no" class="digi-input" placeholder="Contoh: DA 8191 UT" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Model / Tipe Unit <span class="digi-required">*</span></label>
                    <input type="text" name="model" id="unit_model" class="digi-input" placeholder="Contoh: B/U FMC JB HDX TRITON" required>
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Tipe Meter (KM / HM)</label>
                    <select name="meter_type" id="unit_meter_type" class="digi-select">
                        <option value="km">KM (Light Vehicle / Bus / Truck)</option>
                        <option value="hm">HM (Heavy Equipment / Alat Berat)</option>
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kapasitas Tangki (Liter)</label>
                    <input type="number" name="tank_capacity" id="unit_tank_capacity" class="digi-input" value="80">
                </div>
                <div>
                    <button type="submit" id="unitSubmitBtn" class="btn-digi btn-digi-blue">Simpan Unit</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $vehicles])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Unit Code</th>
                        <th>Nomor Polisi</th>
                        <th>Tipe / Model Unit</th>
                        <th>Meter</th>
                        <th>Kapasitas Tangki</th>
                        <th>Standar Aktif</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $idx => $v)
                        <tr>
                            <td>{{ $vehicles->firstItem() ? ($vehicles->firstItem() + $idx) : ($idx + 1) }}</td>
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
                            <td>
                                <span class="digi-badge {{ $v->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">
                                    {{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editUnit(@json($v))'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $v->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('unit', {{ $v->id }}, 'units', '{{ addslashes($v->unit_code) }}', {{ $v->is_active ? 'true' : 'false' }})">
                                        {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.units.destroy', $v->id) }}', 'Data Unit / Kendaraan', '{{ addslashes($v->unit_code . ' (' . $v->police_no . ')') }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $vehicles])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 2: EMPLOYEES (DIGISIM READY)                             -->
    <!-- ============================================================ -->
    @if($tab === 'employees')
        <form id="employeeForm" action="{{ route('admin.master.employee') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="emp_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="empFormTitle" style="margin: 0;">Tambah Data Employee (Lokal & Integrasi DIGISIM)</h3>
                <button type="button" id="empCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetEmployeeForm()">
                    Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">NRP / Nomor Karyawan <span class="digi-required">*</span></label>
                    <input type="text" name="nrp" id="emp_nrp" class="digi-input" placeholder="Contoh: 80124024" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Lengkap <span class="digi-required">*</span></label>
                    <input type="text" name="name" id="emp_name" class="digi-input" placeholder="Nama Karyawan" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jabatan & Departemen</label>
                    <input type="text" name="position" id="emp_position" class="digi-input" placeholder="Driver / Operator">
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Perusahaan Asal</label>
                    <input type="text" name="company" id="emp_company" class="digi-input" value="PT UNITED TRACTORS Tbk - SITE SATUI" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Sumber Data & Ref Eksternal (DIGISIM)</label>
                    <div style="display:flex; gap:6px;">
                        <select name="source" id="emp_source" class="digi-select" style="width:120px;">
                            <option value="local">Lokal</option>
                            <option value="digisim">DIGISIM</option>
                        </select>
                        <input type="text" name="external_reference" id="emp_ext_ref" class="digi-input" placeholder="Ref ID simpers">
                    </div>
                </div>
                <div>
                    <button type="submit" id="empSubmitBtn" class="btn-digi btn-digi-blue">Simpan Employee</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $employees])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>NRP</th>
                        <th>Nama Employee</th>
                        <th>Jabatan</th>
                        <th>Perusahaan</th>
                        <th>Sumber Integrasi</th>
                        <th>Referensi Eksternal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $idx => $emp)
                        <tr>
                            <td>{{ $employees->firstItem() ? ($employees->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $emp->nrp }}</td>
                            <td style="font-weight:600;">{{ $emp->name }}</td>
                            <td>{{ $emp->position }}</td>
                            <td>{{ $emp->company }}</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($emp->source) }}</span></td>
                            <td>{{ $emp->external_reference ?? '-' }}</td>
                            <td>
                                <span class="digi-badge {{ $emp->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">
                                    {{ $emp->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editEmployee(@json($emp))'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $emp->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('employee', {{ $emp->id }}, 'employees', '{{ addslashes($emp->name) }}', {{ $emp->is_active ? 'true' : 'false' }})">
                                        {{ $emp->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.employees.destroy', $emp->id) }}', 'Data Employee', '{{ addslashes($emp->nrp . ' - ' . $emp->name) }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $employees])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 3: AREAS                                                 -->
    <!-- ============================================================ -->
    @if($tab === 'areas')
        <form id="areaForm" action="{{ route('admin.master.area') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="area_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="areaFormTitle" style="margin: 0;">Tambah Area Operasional</h3>
                <button type="button" id="areaCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetAreaForm()">
                    Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Kode Area</label>
                    <input type="text" name="code" id="area_code" class="digi-input" placeholder="Contoh: SDU-01">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Area Operasional <span class="digi-required">*</span></label>
                    <input type="text" name="name" id="area_name" class="digi-input" placeholder="Contoh: SDU (Sungai Danau)" required>
                </div>
                <div>
                    <button type="submit" id="areaSubmitBtn" class="btn-digi btn-digi-blue">Simpan Area</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $areas])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kode Area</th>
                        <th>Nama Area Operasional</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($areas as $idx => $a)
                        <tr>
                            <td>{{ $areas->firstItem() ? ($areas->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $a->code ?? 'AREA-' . $a->id }}</td>
                            <td style="font-weight:600;">{{ $a->name }}</td>
                            <td>
                                <span class="digi-badge {{ $a->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">
                                    {{ $a->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editArea(@json($a))'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $a->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('area', {{ $a->id }}, 'areas', '{{ addslashes($a->name) }}', {{ $a->is_active ? 'true' : 'false' }})">
                                        {{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.areas.destroy', $a->id) }}', 'Area Operasional', '{{ addslashes(($a->code ? $a->code . ' - ' : '') . $a->name) }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $areas])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 4: VENDORS                                               -->
    <!-- ============================================================ -->
    @if($tab === 'vendors')
        <form id="vendorForm" action="{{ route('admin.master.vendor') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="vendor_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="vendorFormTitle" style="margin: 0;">Tambah Perusahaan Vendor BBM</h3>
                <button type="button" id="vendorCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetVendorForm()">
                    Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Kode & Nama Vendor BBM <span class="digi-required">*</span></label>
                    <div style="display: flex; gap: 6px;">
                        <input type="text" name="code" id="vendor_code" class="digi-input" placeholder="Kode" style="width: 105px;">
                        <input type="text" name="name" id="vendor_name" class="digi-input" placeholder="Contoh: Cv. Sudan Mandiri" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kontak Petugas & No. Telepon</label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="contact_person" id="vendor_contact" class="digi-input" placeholder="Nama Kontak">
                        <input type="text" name="phone" id="vendor_phone" class="digi-input" placeholder="0812xxxx">
                    </div>
                </div>
                <div>
                    <button type="submit" id="vendorSubmitBtn" class="btn-digi btn-digi-blue">Simpan Vendor BBM</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $vendors])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kode Vendor BBM</th>
                        <th>Nama Vendor BBM</th>
                        <th>Kontak Petugas</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $idx => $ven)
                        <tr>
                            <td>{{ $vendors->firstItem() ? ($vendors->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $ven->code ?? 'VND-0' . $ven->id }}</td>
                            <td style="font-weight:600;">{{ $ven->name }}</td>
                            <td>{{ $ven->contact_person ?? 'Petugas Operasional' }}</td>
                            <td>{{ $ven->phone ?? '-' }}</td>
                            <td>
                                <span class="digi-badge {{ $ven->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">
                                    {{ $ven->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editVendor(@json($ven))'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $ven->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('vendor', {{ $ven->id }}, 'vendors', '{{ addslashes($ven->name) }}', {{ $ven->is_active ? 'true' : 'false' }})">
                                        {{ $ven->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.vendors.destroy', $ven->id) }}', 'Data Vendor BBM', '{{ addslashes(($ven->code ? $ven->code . ' - ' : '') . $ven->name) }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $vendors])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 5: AREA APPROVERS                                        -->
    <!-- ============================================================ -->
    @if($tab === 'approvers')
        <form id="approverForm" action="{{ route('admin.master.approver') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="aa_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="aaFormTitle" style="margin: 0;">Penugasan Approver & Petugas Berdasarkan Area</h3>
                <button type="button" id="aaCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetApproverForm()">
                    Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Area Operasional <span class="digi-required">*</span></label>
                    <select name="area_id" id="aa_area_id" class="digi-select" required>
                        @foreach(($allAreas ?? $areas) as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Pengguna & Peran Tanggung Jawab <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <select name="user_id" id="aa_user_id" class="digi-select" required>
                            @foreach(($allUsers ?? $users) as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->jabatan }})</option>
                            @endforeach
                        </select>
                        <select name="role_type" id="aa_role_type" class="digi-select" style="width: 145px;" required>
                            <option value="spv">SPV</option>
                            <option value="manager">Dept Head/Mgr</option>
                            <option value="vendor">Vendor BBM</option>
                            <option value="fuelman">Fuel Man</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit" id="aaSubmitBtn" class="btn-digi btn-digi-blue">Simpan Penugasan</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $areaApprovers])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Area Operasional</th>
                        <th>Nama Petugas / Approver</th>
                        <th>Tanggung Jawab</th>
                        <th>Mulai Penugasan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($areaApprovers as $idx => $aa)
                        <tr>
                            <td>{{ $areaApprovers->firstItem() ? ($areaApprovers->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $aa->area?->name }}</td>
                            <td style="font-weight:600;">{{ $aa->user?->name }} ({{ $aa->user?->username }})</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($aa->role_type) }}</span></td>
                            <td>{{ $aa->start_date?->format('d/m/Y') ?? '01/01/2025' }}</td>
                            <td>
                                <span class="digi-badge {{ $aa->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">
                                    {{ $aa->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editApprover(@json($aa))'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $aa->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('approver', {{ $aa->id }}, 'approvers', '{{ addslashes(($aa->area?->name ?? "") . " - " . ($aa->user?->name ?? "")) }}', {{ $aa->is_active ? 'true' : 'false' }})">
                                        {{ $aa->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.approvers.destroy', $aa->id) }}', 'Penugasan Approver Area', '{{ addslashes(($aa->area?->name ?? "") . " - " . ($aa->user?->name ?? "") . " (" . strtoupper($aa->role_type) . ")") }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $areaApprovers])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 6: FUEL STANDARDS                                        -->
    <!-- ============================================================ -->
    @if($tab === 'standards')
        <!-- Penjelasan Singkat Standar Konsumsi BBM Sesuai Aturan Perusahaan -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 20px; line-height: 1.2;">ℹ️</span>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0f172a;">Tentang Standar Konsumsi BBM</h4>
                    <p style="margin: 0 0 8px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                        Standar konsumsi BBM merupakan nilai acuan yang digunakan DigiFuel untuk mengevaluasi penggunaan BBM setiap unit. Nilai standar diperoleh dari ketentuan perusahaan dan dimasukkan oleh Admin secara manual. Standar cukup dimasukkan satu kali untuk setiap unit dan hanya perlu diperbarui apabila terdapat perubahan ketentuan perusahaan atau penambahan unit baru.
                    </p>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px; color: #64748b; line-height: 1.4;">
                        <span>• <strong>KM/Liter:</strong> Digunakan untuk kendaraan yang menggunakan Odometer (Light Vehicle & Bus).</span>
                        <span>• <strong>Liter/Jam:</strong> Digunakan untuk alat berat yang menggunakan Hour Meter (HM).</span>
                        <span>• <strong>Komparasi:</strong> Sistem membandingkan konsumsi aktual dengan standar untuk mendeteksi potensi pemborosan BBM.</span>
                        <span>• <strong>Edit vs Tambah Baru:</strong> Gunakan <em>Edit</em> untuk memperbaiki salah ketik. Gunakan <em>Tambah Baru</em> jika perusahaan menetapkan standar baru agar histori periode lama tetap tersimpan utuh.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulir Sederhana Pengaturan Standar BBM -->
        <form id="standardForm" action="{{ route('admin.master.standard') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="std_id" value="">
            <input type="hidden" name="standard_type" id="std_type" value="km_per_liter">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h3 class="digi-section-title" id="stdFormTitle" style="margin: 0;">Pengaturan Standar Konsumsi BBM Unit</h3>
                    <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0 0;">
                        Pilih unit, masukkan nilai standar konsumsi, dan tentukan tanggal mulai berlaku.
                    </p>
                </div>
                <button type="button" id="stdCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetStandardForm()">
                    Batal Edit
                </button>
            </div>

            <div class="digi-form-grid-3">
                <!-- 1. Unit Code Dropdown (Hanya unit aktif untuk standar baru) -->
                <div class="digi-form-group">
                    <label class="digi-form-label">Pilih Unit Kendaraan / Alat Berat <span class="digi-required">*</span></label>
                    <select name="vehicle_id" id="std_vehicle_id" class="digi-select" onchange="onVehicleSelectChange(this)" required>
                        <option value="" disabled selected>-- Pilih Unit --</option>
                        @foreach(($allVehicles ?? $vehicles) as $v)
                            <option value="{{ $v->id }}"
                                data-type="{{ $v->unit_type ?? $v->model }}"
                                data-meter="{{ $v->meter_type }}"
                                data-code="{{ $v->unit_code }}"
                                data-active="{{ $v->is_active ? '1' : '0' }}">
                                {{ $v->unit_code }} ({{ $v->unit_type ?? $v->model }}){{ !$v->is_active ? ' - [Nonaktif]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <!-- Indikator otomatis jenis unit & tipe meter -->
                    <div id="std_vehicle_info" style="font-size: 12px; color: #0284c7; margin-top: 5px; display: none;">
                        Jenis Unit: <strong id="std_info_type">-</strong> | Tipe Meter: <strong id="std_info_meter">-</strong>
                    </div>
                </div>

                <!-- 2. Nilai Standar Konsumsi dengan Satuan Dinamis -->
                <div class="digi-form-group">
                    <label class="digi-form-label">Nilai Standar Konsumsi <span class="digi-required">*</span></label>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <input type="number" step="0.01" min="0.01" name="standard_value" id="std_value" class="digi-input" placeholder="Contoh: 8.0" required>
                        <span id="std_unit_badge" style="background: #f1f5f9; padding: 7px 14px; border-radius: 6px; font-weight: 700; color: #334155; font-size: 13px; border: 1px solid #cbd5e1; white-space: nowrap;">
                            KM/L
                        </span>
                    </div>
                    <div id="std_type_hint" style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                        Target efisiensi konsumsi BBM unit operasional.
                    </div>
                </div>

                <!-- 3. Tanggal Mulai Berlaku -->
                <div class="digi-form-group">
                    <label class="digi-form-label">Berlaku Mulai <span class="digi-required">*</span></label>
                    <input type="date" name="effective_start" id="std_start" class="digi-input" value="{{ now()->toDateString() }}" required>
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                        Standar sebelumnya akan ditutup otomatis per tanggal ini.
                    </div>
                </div>
            </div>

            <!-- Pengaturan Toleransi Khusus Alat Berat (HM) - Ditampilkan otomatis jika relevan -->
            <div id="std_tolerance_box" style="display: none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Toleransi Konsumsi Alat Berat (HM):</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 6px;">Batas toleransi deviasi konsumsi maksimum untuk alat berat (default 5.0%).</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <input type="number" step="0.1" min="0" max="100" name="tolerance_percentage" id="std_tolerance" class="digi-input" value="5.0" style="width: 80px; text-align: center;">
                        <span style="font-size: 13px; font-weight: 600; color: #475569;">%</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 4px;">
                <div style="flex: 1; min-width: 260px;">
                    <input type="text" name="notes" id="std_notes" class="digi-input" placeholder="Catatan opsional (misal: penyesuaian rasio operasional tambang 2026)">
                </div>
                <div>
                    <button type="submit" id="stdSubmitBtn" class="btn-digi btn-digi-blue">
                        Simpan Standar BBM
                    </button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $fuelStandards])

        <!-- Tabel Sederhana Standar Konsumsi BBM -->
        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Unit Code</th>
                        <th>Jenis Unit</th>
                        <th style="text-align: right;">Standar Konsumsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fuelStandards as $idx => $std)
                        <tr>
                            <td>{{ $fuelStandards->firstItem() ? ($fuelStandards->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight: 700; color: #0284c7;">
                                {{ $std->vehicle?->unit_code ?? ('Unit #' . $std->vehicle_id) }}
                            </td>
                            <td>
                                {{ $std->vehicle?->unit_type ?? ($std->vehicle?->model ?? 'Light Vehicle') }}
                            </td>
                            <td style="text-align: right;">
                                <span style="font-weight: 700; color: #0f172a; font-size: 13.5px;">{{ (float)$std->standard_value }}</span>
                                <span style="font-size: 12px; font-weight: 600; color: #64748b; margin-left: 2px;">
                                    {{ $std->standard_type === 'liter_per_hour' ? 'Liter/Jam' : 'KM/L' }}
                                </span>
                            </td>
                            <td>
                                <span class="digi-badge {{ $std->is_active ? 'digi-badge-green' : 'digi-badge-gray' }}">
                                    {{ $std->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editStandard(@json($std), "{{ $std->effective_start?->format("Y-m-d") }}")'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $std->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('standard', {{ $std->id }}, 'standards', '{{ addslashes(($std->vehicle?->unit_code ?? "") . " (" . (float)$std->standard_value . ")") }}', {{ $std->is_active ? 'true' : 'false' }})">
                                        {{ $std->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.standards.destroy', $std->id) }}', 'Standar Konsumsi BBM', '{{ addslashes(($std->vehicle?->unit_code ?? "") . " (" . (float)$std->standard_value . ")") }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $fuelStandards])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 7: FUEL PRICES                                           -->
    <!-- ============================================================ -->
    @if($tab === 'prices')
        <form id="priceForm" action="{{ route('admin.master.price') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="price_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="priceFormTitle" style="margin: 0;">Tambah Riwayat Harga BBM per Liter</h3>
                <button type="button" id="priceCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetPriceForm()">
                    Batal Edit
                </button>
            </div>
            <div style="display: grid; grid-template-columns: 1.2fr 1.5fr 1fr auto; gap: 12px; margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Vendor BBM <span class="digi-required">*</span></label>
                    <select name="vendor_id" id="price_vendor_id" class="digi-select" required>
                        <option value="">- Pilih Vendor BBM -</option>
                        @foreach(($allVendors ?? $vendors) as $ven)
                            <option value="{{ $ven->id }}">{{ ($ven->code ? $ven->code . ' - ' : '') . $ven->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jenis BBM & Harga per Liter (IDR) <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="fuel_type" id="price_fuel_type" class="digi-input" value="Solar Industri B35" required>
                        <input type="number" step="1" name="price_per_liter" id="price_per_liter" class="digi-input" value="27000" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Tanggal Mulai Berlaku <span class="digi-required">*</span></label>
                    <input type="date" name="effective_start" id="price_start" class="digi-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <button type="submit" id="priceSubmitBtn" class="btn-digi btn-digi-blue">Simpan Harga BBM</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $fuelPrices])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Vendor BBM</th>
                        <th>Jenis BBM</th>
                        <th>Harga per Liter</th>
                        <th>Mulai Berlaku</th>
                        <th>Akhir Berlaku</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fuelPrices as $idx => $fp)
                        <tr>
                            <td>{{ $fuelPrices->firstItem() ? ($fuelPrices->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td>
                                <span class="digi-badge digi-badge-blue" style="font-weight:600;">
                                    {{ $fp->vendor?->name ?? 'Semua Vendor' }}
                                </span>
                            </td>
                            <td style="font-weight:700; color: #0284c7;">{{ $fp->fuel_type }}</td>
                            <td><strong>Rp {{ number_format($fp->price_per_liter, 0, ',', '.') }}</strong> / Liter</td>
                            <td>{{ $fp->effective_start?->format('d/m/Y') }}</td>
                            <td>{{ $fp->effective_end ? $fp->effective_end->format('d/m/Y') : 'Sekarang' }}</td>
                            <td>
                                <span class="digi-badge {{ $fp->is_active ? 'digi-badge-green' : 'digi-badge-gray' }}">
                                    {{ $fp->is_active ? 'Aktif' : 'Nonaktif / Historis' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editPrice(@json($fp), "{{ $fp->effective_start?->format("Y-m-d") }}")'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $fp->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('price', {{ $fp->id }}, 'prices', '{{ addslashes($fp->fuel_type . " (Rp " . number_format($fp->price_per_liter, 0, ",", ".") . ")") }}', {{ $fp->is_active ? 'true' : 'false' }})">
                                        {{ $fp->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        onclick="openDeleteModal('{{ route('admin.master.prices.destroy', $fp->id) }}', 'Harga BBM per Liter', '{{ addslashes($fp->fuel_type . " (Rp " . number_format($fp->price_per_liter, 0, ",", ".") . "/L)") }}')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $fuelPrices])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 8: USERS & ROLES                                         -->
    <!-- ============================================================ -->
    @if($tab === 'users')
        <form id="userForm" action="{{ route('admin.master.user') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="usr_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="usrFormTitle" style="margin: 0;">Tambah / Kelola Akun Pengguna & Role</h3>
                <button type="button" id="usrCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetUserForm()">
                    Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Lengkap & NRP <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="name" id="usr_name" class="digi-input" placeholder="Nama" required>
                        <input type="text" name="nrp" id="usr_nrp" class="digi-input" placeholder="NRP" style="width:110px;">
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Username & Email <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="username" id="usr_username" class="digi-input" placeholder="Username" required>
                        <input type="email" name="email" id="usr_email" class="digi-input" placeholder="Email" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Role & Jabatan <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <select name="role" id="usr_role" class="digi-select" required>
                            <option value="driver">Employee / Driver</option>
                            <option value="spv">SPV / Dept Head</option>
                            <option value="depthead">Dept Head / Manager</option>
                            <option value="vendor">Vendor BBM</option>
                            <option value="fuelman">Fuel Man</option>
                            <option value="admin">Admin</option>
                        </select>
                        <input type="text" name="jabatan" id="usr_jabatan" class="digi-input" placeholder="Jabatan" required>
                    </div>
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom:0; align-items:flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Perusahaan Vendor BBM Terkait (Khusus Role Vendor BBM)</label>
                    <select name="vendor_id" id="usr_vendor_id" class="digi-select">
                        <option value="">- Internal PT United Tractors -</option>
                        @foreach(($allVendors ?? $vendors) as $ven)
                            <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Password Baru (Default: password123)</label>
                    <input type="password" name="password" id="usr_password" class="digi-input" placeholder="Kosongkan jika tidak diubah">
                </div>
                <div>
                    <button type="submit" id="usrSubmitBtn" class="btn-digi btn-digi-blue">Simpan Akun Pengguna</button>
                </div>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $users])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th>NRP</th>
                        <th>Role Sistem</th>
                        <th>Jabatan</th>
                        <th>Vendor BBM Terkait</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $idx => $u)
                        <tr>
                            <td>{{ $users->firstItem() ? ($users->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $u->name }}</td>
                            <td style="font-weight:600;">{{ $u->username }}</td>
                            <td>{{ $u->nrp ?? '-' }}</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($u->role) }}</span></td>
                            <td>{{ $u->jabatan }}</td>
                            <td>{{ $u->vendor?->name ?? 'Internal UT' }}</td>
                            <td>
                                <span class="digi-badge {{ $u->is_active ? 'digi-badge-green' : 'digi-badge-red' }}">
                                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editUser(@json($u))'>
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $u->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        onclick="openToggleModal('user', {{ $u->id }}, 'users', '{{ addslashes($u->name . " (" . $u->username . ")") }}', {{ $u->is_active ? 'true' : 'false' }})">
                                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    @if((int)$u->id !== (int)$user->id)
                                        <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                            onclick="openDeleteModal('{{ route('admin.master.users.destroy', $u->id) }}', 'Akun Pengguna', '{{ addslashes($u->name . " (" . $u->username . ")") }}')">
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $users])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 8B: SYSTEM BACKGROUNDS (CRUD BACKGROUND UT)              -->
    <!-- ============================================================ -->
    @if($tab === 'backgrounds')
        <form id="bgForm" action="{{ route('admin.master.background') }}" method="POST" enctype="multipart/form-data" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="bg_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="digi-section-title" id="bgFormTitle" style="margin: 0;">Upload / Kelola Gambar Background Sistem Sesuai Ketentuan UT</h3>
                <button type="button" id="bgCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetBgForm()">
                    Batal Edit
                </button>
            </div>
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: #0369a1;">
                <strong>Ketentuan Gambar Background PT United Tractors Tbk:</strong> Gunakan foto area operasional tambang / alat berat resolusi tinggi (rasio landscape 16:9, format <strong>JPG, PNG, atau WEBP</strong>, ukuran maksimal <strong>5 MB</strong>) dengan bagian atas cerah agar navigasi utama tetap terbaca jelas.
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Judul / Nama Background <span class="digi-required">*</span></label>
                    <input type="text" name="title" id="bg_title" class="digi-input" placeholder="Contoh: Dokumentasi Alat Berat Site Satui 2026" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">File Gambar Background <span class="digi-required" id="bg_file_req">*</span></label>
                    <input type="file" name="image" id="bg_image" accept="image/jpeg,image/png,image/webp" class="digi-input" style="padding: 6px;">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Keterangan / Ketentuan UT</label>
                    <input type="text" name="notes" id="bg_notes" class="digi-input" placeholder="Catatan tema operasional / periode penggunaan">
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="set_active" id="bg_set_active" value="1" checked style="width: 16px; height: 16px;">
                    <span>Langsung terapkan sebagai Background Aktif di seluruh halaman DigiFuel</span>
                </label>
                <button type="submit" id="bgSubmitBtn" class="btn-digi btn-digi-blue">
                    Simpan & Terapkan Background
                </button>
            </div>
        </form>

        @include('partials.pagination_top', ['paginator' => $backgrounds])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Pratinjau Gambar</th>
                        <th>Judul Background</th>
                        <th>Keterangan / Ketentuan UT</th>
                        <th>Waktu Diunggah</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backgrounds as $idx => $bg)
                        <tr>
                            <td>{{ method_exists($backgrounds, 'firstItem') && $backgrounds->firstItem() ? ($backgrounds->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td>
                                <a href="{{ asset($bg->image_path) }}" target="_blank" title="Klik untuk melihat ukuran penuh">
                                    <img src="{{ asset($bg->image_path) }}" alt="{{ $bg->title }}" style="width: 125px; height: 68px; object-fit: cover; border-radius: 8px; border: 2px solid {{ $bg->is_active ? '#22c55e' : '#cbd5e1' }}; display: block;">
                                </a>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0284c7;">{{ $bg->title }}</div>
                                <div style="font-size: 11px; color: #64748b;"><code>{{ $bg->image_path }}</code></div>
                            </td>
                            <td>{{ $bg->notes ?? '-' }}</td>
                            <td>
                                <div>{{ $bg->created_at?->format('d/m/Y H:i') }}</div>
                                <div style="font-size: 11px; color: #64748b;">Oleh: {{ $bg->uploader?->name ?? 'Admin Sistem' }}</div>
                            </td>
                            <td>
                                @if($bg->is_active)
                                    <span class="digi-badge digi-badge-green">Sedang Digunakan (Aktif)</span>
                                @else
                                    <span class="digi-badge digi-badge-gray">Tersimpan (Nonaktif)</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        onclick='editBackground(@json($bg))'>
                                        Edit
                                    </button>
                                    @if(!$bg->is_active)
                                        <button type="button" class="btn-digi btn-digi-green" style="padding: 5px 12px; font-size: 12px;"
                                            onclick="openToggleModal('background', {{ $bg->id }}, 'backgrounds', '{{ addslashes($bg->title) }}', false)">
                                            Gunakan Background Ini
                                        </button>
                                    @endif
                                    @if($bg->image_path !== 'images/mining_bg.jpg')
                                        <form action="{{ route('admin.master.background.delete', $bg->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus gambar background ini?')">
                                            @csrf
                                            <button type="submit" class="btn-digi btn-digi-red" style="padding: 5px 12px; font-size: 12px;">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 28px; color: #94a3b8;">
                                Belum ada data gambar background yang tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $backgrounds])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 9: AUDIT TRAIL                                           -->
    <!-- ============================================================ -->
    @if($tab === 'audit')
        <h3 class="digi-section-title">Log Aktivitas & Audit Trail Sistem</h3>

        @include('partials.pagination_top', ['paginator' => $auditLogs])

        <div style="overflow-x: auto;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>No. Pengajuan</th>
                        <th>Transisi Status</th>
                        <th>Keterangan Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $idx => $log)
                        <tr>
                            <td>{{ $auditLogs->firstItem() ? ($auditLogs->firstItem() + $idx) : ($idx + 1) }}</td>
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
                            <td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Belum ada catatan audit trail.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $auditLogs])
    @endif
</div>

<!-- MODAL KONFIRMASI AKTIFKAN / NONAKTIFKAN STATUS MASTER DATA -->
<div id="toggleStatusModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 460px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title" id="toggleModalTitle">Konfirmasi Perubahan Status</h3>
            <button type="button" class="digi-modal-close" onclick="closeToggleModal()">&times;</button>
        </div>
        <form action="{{ route('admin.master.toggle') }}" method="POST" style="margin: 0;">
            @csrf
            <input type="hidden" name="entity" id="toggle_entity" value="">
            <input type="hidden" name="id" id="toggle_id" value="">
            <input type="hidden" name="tab" id="toggle_tab" value="">
            <div class="digi-modal-body">
                <p id="toggleModalMessage" style="margin: 0; font-size: 14px; color: #334155; line-height: 1.5;"></p>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeToggleModal()">Batal</button>
                <button type="submit" id="toggleConfirmBtn" class="btn-digi btn-digi-red">Ya, Ubah Status</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VERIFIKASI 2 LANGKAH PENGHAPUSAN MASTER DATA -->
<div id="deleteMasterModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 510px;">
        <div class="digi-modal-header" style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span id="deleteStepBadge" class="digi-badge digi-badge-red" style="font-size: 11px; padding: 3px 9px;">LANGKAH 1 / 2</span>
                <h3 class="digi-modal-title" id="deleteModalTitle" style="color: #991b1b;">Konfirmasi Hapus Master Data</h3>
            </div>
            <button type="button" class="digi-modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>

        <form id="deleteMasterForm" action="" method="POST" style="margin: 0;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="verification_step" id="delete_verification_step" value="1">

            <!-- STEP 1: PERINGATAN AWAL -->
            <div id="deleteStep1Body" class="digi-modal-body">
                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 13px; color: #9a3412; line-height: 1.5;">
                    <strong>Peringatan Penghapusan Data:</strong><br>
                    Anda akan menghapus <strong id="deleteEntityCategory">Master Data</strong> berikut dari sistem DigiFuel:
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
                    <div style="font-size: 11.5px; color: #64748b; text-transform: uppercase; font-weight: 700;">Target Data yang Akan Dihapus</div>
                    <div id="deleteTargetLabel" style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;">-</div>
                </div>
                <p style="margin: 0; font-size: 13px; color: #475569; line-height: 1.5;">
                    Jika Anda hanya ingin menghentikan penggunaan data ini untuk pengajuan baru tanpa menghapusnya, disarankan menggunakan tombol <strong>Nonaktifkan</strong>. Apakah Anda tetap ingin melanjutkan ke tahap verifikasi penghapusan?
                </p>
            </div>

            <!-- STEP 2: VERIFIKASI KATA KUNCI & CHECKBOX -->
            <div id="deleteStep2Body" class="digi-modal-body" style="display: none;">
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 13px; color: #991b1b; line-height: 1.5;">
                    <strong>Verifikasi Keamanan Tahap Akhir (Langkah 2 / 2):</strong><br>
                    Untuk mencegah ketidaksengajaan penghapusan pada <strong id="deleteTargetLabelStep2">-</strong>, silakan centang pernyataan di bawah dan ketik kata kunci <strong>HAPUS</strong>.
                </div>

                <label style="display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: #1e293b; cursor: pointer; margin-bottom: 14px; user-select: none;">
                    <input type="checkbox" id="deleteConfirmCheckbox" onchange="validateDeleteStep2()" style="margin-top: 3px; width: 16px; height: 16px;">
                    <span>Saya memahami konsekuensi penghapusan data ini dan bertanggung jawab atas perubahan pada Master Data.</span>
                </label>

                <div class="digi-form-group" style="margin-bottom: 4px;">
                    <label class="digi-form-label" style="color: #991b1b;">
                        Ketik kata kunci <code style="background: #fee2e2; padding: 2px 6px; border-radius: 4px; font-weight: 800; color: #b91c1c;">HAPUS</code> untuk membuka kunci tombol:
                    </label>
                    <input type="text" name="confirm_keyword" id="deleteConfirmKeyword" class="digi-input"
                        placeholder="Ketik HAPUS dengan huruf kapital" autocomplete="off"
                        oninput="validateDeleteStep2()" style="border-color: #fca5a5; font-weight: 700; letter-spacing: 1px;">
                </div>
            </div>

            <!-- FOOTER STEP 1 -->
            <div id="deleteStep1Footer" class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeDeleteModal()">Batal</button>
                <button type="button" class="btn-digi btn-digi-yellow" onclick="proceedToDeleteStep2()" style="font-weight: 700;">
                    Lanjut ke Verifikasi Langkah 2 &rarr;
                </button>
            </div>

            <!-- FOOTER STEP 2 -->
            <div id="deleteStep2Footer" class="digi-modal-footer" style="display: none; justify-content: space-between;">
                <button type="button" class="btn-digi btn-digi-gray" onclick="backToDeleteStep1()">&larr; Kembali</button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-digi btn-digi-gray" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" id="deleteFinalSubmitBtn" class="btn-digi btn-digi-red" disabled style="opacity: 0.5; cursor: not-allowed;">
                        Hapus Data Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 1. Unit Form Auto-Fill & Reset
function editUnit(v) {
    document.getElementById('unit_id').value = v.id;
    document.getElementById('unit_code').value = v.unit_code || '';
    document.getElementById('unit_police_no').value = v.police_no || '';
    document.getElementById('unit_model').value = v.model || v.unit_type || '';
    document.getElementById('unit_meter_type').value = v.meter_type || 'km';
    document.getElementById('unit_tank_capacity').value = v.tank_capacity || 80;
    document.getElementById('unitFormTitle').textContent = 'Edit Data Unit: ' + v.unit_code;
    document.getElementById('unitSubmitBtn').textContent = 'Perbarui Unit';
    document.getElementById('unitCancelBtn').style.display = 'inline-flex';
    document.getElementById('unitForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetUnitForm() {
    document.getElementById('unitForm').reset();
    document.getElementById('unit_id').value = '';
    document.getElementById('unitFormTitle').textContent = 'Tambah Data Unit Kendaraan & Alat Berat';
    document.getElementById('unitSubmitBtn').textContent = 'Simpan Unit';
    document.getElementById('unitCancelBtn').style.display = 'none';
}

// 2. Employee Form Auto-Fill & Reset
function editEmployee(emp) {
    document.getElementById('emp_id').value = emp.id;
    document.getElementById('emp_nrp').value = emp.nrp || '';
    document.getElementById('emp_name').value = emp.name || '';
    document.getElementById('emp_position').value = emp.position || '';
    document.getElementById('emp_company').value = emp.company || 'PT UNITED TRACTORS Tbk - SITE SATUI';
    document.getElementById('emp_source').value = emp.source || 'local';
    document.getElementById('emp_ext_ref').value = emp.external_reference || '';
    document.getElementById('empFormTitle').textContent = 'Edit Data Employee: ' + emp.name;
    document.getElementById('empSubmitBtn').textContent = 'Perbarui Employee';
    document.getElementById('empCancelBtn').style.display = 'inline-flex';
    document.getElementById('employeeForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetEmployeeForm() {
    document.getElementById('employeeForm').reset();
    document.getElementById('emp_id').value = '';
    document.getElementById('empFormTitle').textContent = 'Tambah Data Employee (Lokal & Integrasi DIGISIM)';
    document.getElementById('empSubmitBtn').textContent = 'Simpan Employee';
    document.getElementById('empCancelBtn').style.display = 'none';
}

// 3. Area Form Auto-Fill & Reset
function editArea(a) {
    document.getElementById('area_id').value = a.id;
    document.getElementById('area_code').value = a.code || '';
    document.getElementById('area_name').value = a.name || '';
    document.getElementById('areaFormTitle').textContent = 'Edit Area Operasional: ' + a.name;
    document.getElementById('areaSubmitBtn').textContent = 'Perbarui Area';
    document.getElementById('areaCancelBtn').style.display = 'inline-flex';
    document.getElementById('areaForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetAreaForm() {
    document.getElementById('areaForm').reset();
    document.getElementById('area_id').value = '';
    document.getElementById('areaFormTitle').textContent = 'Tambah Area Operasional';
    document.getElementById('areaSubmitBtn').textContent = 'Simpan Area';
    document.getElementById('areaCancelBtn').style.display = 'none';
}

// 4. Vendor Form Auto-Fill & Reset
function editVendor(ven) {
    document.getElementById('vendor_id').value = ven.id;
    document.getElementById('vendor_code').value = ven.code || '';
    document.getElementById('vendor_name').value = ven.name || '';
    document.getElementById('vendor_contact').value = ven.contact_person || '';
    document.getElementById('vendor_phone').value = ven.phone || '';
    document.getElementById('vendorFormTitle').textContent = 'Edit Perusahaan Vendor: ' + ven.name;
    document.getElementById('vendorSubmitBtn').textContent = 'Perbarui Vendor';
    document.getElementById('vendorCancelBtn').style.display = 'inline-flex';
    document.getElementById('vendorForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetVendorForm() {
    document.getElementById('vendorForm').reset();
    document.getElementById('vendor_id').value = '';
    document.getElementById('vendorFormTitle').textContent = 'Tambah Perusahaan Vendor BBM';
    document.getElementById('vendorSubmitBtn').textContent = 'Simpan Vendor';
    document.getElementById('vendorCancelBtn').style.display = 'none';
}

// 5. Area Approver Form Auto-Fill & Reset
function editApprover(aa) {
    document.getElementById('aa_id').value = aa.id;
    document.getElementById('aa_area_id').value = aa.area_id;
    document.getElementById('aa_user_id').value = aa.user_id;
    document.getElementById('aa_role_type').value = aa.role_type;
    document.getElementById('aaFormTitle').textContent = 'Edit Penugasan Approver Area #' + aa.id;
    document.getElementById('aaSubmitBtn').textContent = 'Perbarui Penugasan';
    document.getElementById('aaCancelBtn').style.display = 'inline-flex';
    document.getElementById('approverForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetApproverForm() {
    document.getElementById('approverForm').reset();
    document.getElementById('aa_id').value = '';
    document.getElementById('aaFormTitle').textContent = 'Penugasan Approver & Petugas Berdasarkan Area';
    document.getElementById('aaSubmitBtn').textContent = 'Simpan Penugasan';
    document.getElementById('aaCancelBtn').style.display = 'none';
}

// 6. Fuel Standard Form Auto-Fill, Dynamic Units & Reset
function onVehicleSelectChange(selectElem) {
    if (!selectElem) return;
    const selectedOpt = selectElem.options[selectElem.selectedIndex];
    if (!selectedOpt || !selectedOpt.value) {
        const infoElem = document.getElementById('std_vehicle_info');
        if (infoElem) infoElem.style.display = 'none';
        return;
    }

    const unitType = selectedOpt.getAttribute('data-type') || 'Kendaraan Operasional';
    const meterType = (selectedOpt.getAttribute('data-meter') || 'km').toLowerCase();

    const infoElem = document.getElementById('std_vehicle_info');
    if (infoElem) {
        document.getElementById('std_info_type').textContent = unitType;
        document.getElementById('std_info_meter').textContent = meterType.toUpperCase();
        infoElem.style.display = 'block';
    }

    const unitBadge = document.getElementById('std_unit_badge');
    const stdTypeInput = document.getElementById('std_type');
    const tolBox = document.getElementById('std_tolerance_box');
    const hint = document.getElementById('std_type_hint');

    if (meterType === 'hm') {
        if (stdTypeInput) stdTypeInput.value = 'liter_per_hour';
        if (unitBadge) unitBadge.textContent = 'Liter/Jam';
        if (hint) hint.textContent = 'Target konsumsi BBM alat berat per Jam Operasi (HM).';
        if (tolBox) tolBox.style.display = 'block';
    } else {
        if (stdTypeInput) stdTypeInput.value = 'km_per_liter';
        if (unitBadge) unitBadge.textContent = 'KM/L';
        if (hint) hint.textContent = 'Target efisiensi bahan bakar (Kilometer per Liter).';
        if (tolBox) tolBox.style.display = 'none';
    }
}

function editStandard(std, startDate) {
    document.getElementById('std_id').value = std.id;
    const vehSelect = document.getElementById('std_vehicle_id');
    if (vehSelect) {
        vehSelect.value = std.vehicle_id;
    }
    document.getElementById('std_value').value = parseFloat(std.standard_value);

    const stdTypeInput = document.getElementById('std_type');
    if (stdTypeInput) {
        stdTypeInput.value = std.standard_type || 'km_per_liter';
    }
    const tolInput = document.getElementById('std_tolerance');
    if (tolInput) {
        tolInput.value = parseFloat(std.tolerance_percentage || 5.0);
    }
    const notesInput = document.getElementById('std_notes');
    if (notesInput) {
        notesInput.value = std.notes || '';
    }
    if (startDate) document.getElementById('std_start').value = startDate;

    const vCode = std.vehicle ? std.vehicle.unit_code : ('Unit #' + std.vehicle_id);
    document.getElementById('stdFormTitle').textContent = 'Edit Standar Konsumsi BBM: ' + vCode;
    document.getElementById('stdSubmitBtn').textContent = 'Perbarui Standar BBM';
    document.getElementById('stdCancelBtn').style.display = 'inline-flex';

    onVehicleSelectChange(vehSelect);
    document.getElementById('standardForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function resetStandardForm() {
    document.getElementById('standardForm').reset();
    document.getElementById('std_id').value = '';
    const infoElem = document.getElementById('std_vehicle_info');
    if (infoElem) infoElem.style.display = 'none';
    const unitBadge = document.getElementById('std_unit_badge');
    if (unitBadge) unitBadge.textContent = 'KM/L';
    const stdTypeInput = document.getElementById('std_type');
    if (stdTypeInput) stdTypeInput.value = 'km_per_liter';
    const tolBox = document.getElementById('std_tolerance_box');
    if (tolBox) tolBox.style.display = 'none';
    document.getElementById('stdFormTitle').textContent = 'Pengaturan Standar Konsumsi BBM Unit';
    document.getElementById('stdSubmitBtn').textContent = 'Simpan Standar BBM';
    document.getElementById('stdCancelBtn').style.display = 'none';
}

// 7. Fuel Price Form Auto-Fill & Reset
function editPrice(fp, startDate) {
    document.getElementById('price_id').value = fp.id;
    document.getElementById('price_vendor_id').value = fp.vendor_id || '';
    document.getElementById('price_fuel_type').value = fp.fuel_type || '';
    document.getElementById('price_per_liter').value = parseFloat(fp.price_per_liter);
    if (startDate) document.getElementById('price_start').value = startDate;
    document.getElementById('priceFormTitle').textContent = 'Edit Harga BBM: ' + fp.fuel_type;
    document.getElementById('priceSubmitBtn').textContent = 'Perbarui Harga BBM';
    document.getElementById('priceCancelBtn').style.display = 'inline-flex';
    document.getElementById('priceForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetPriceForm() {
    document.getElementById('priceForm').reset();
    document.getElementById('price_id').value = '';
    document.getElementById('price_vendor_id').value = '';
    document.getElementById('priceFormTitle').textContent = 'Tambah Riwayat Harga BBM per Liter';
    document.getElementById('priceSubmitBtn').textContent = 'Simpan Harga BBM';
    document.getElementById('priceCancelBtn').style.display = 'none';
}

// 8. User Form Auto-Fill & Reset
function editUser(u) {
    document.getElementById('usr_id').value = u.id;
    document.getElementById('usr_name').value = u.name || '';
    document.getElementById('usr_nrp').value = u.nrp || '';
    document.getElementById('usr_username').value = u.username || '';
    document.getElementById('usr_email').value = u.email || '';
    document.getElementById('usr_role').value = u.role || 'driver';
    document.getElementById('usr_jabatan').value = u.jabatan || '';
    document.getElementById('usr_vendor_id').value = u.vendor_id || '';
    document.getElementById('usr_password').value = '';
    document.getElementById('usrFormTitle').textContent = 'Edit Akun Pengguna: ' + u.name;
    document.getElementById('usrSubmitBtn').textContent = 'Perbarui Akun Pengguna';
    document.getElementById('usrCancelBtn').style.display = 'inline-flex';
    document.getElementById('userForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetUserForm() {
    document.getElementById('userForm').reset();
    document.getElementById('usr_id').value = '';
    document.getElementById('usrFormTitle').textContent = 'Tambah / Kelola Akun Pengguna & Role';
    document.getElementById('usrSubmitBtn').textContent = 'Simpan Akun Pengguna';
    document.getElementById('usrCancelBtn').style.display = 'none';
}

// 8B. Background Form Auto-Fill & Reset
function editBackground(bg) {
    document.getElementById('bg_id').value = bg.id;
    document.getElementById('bg_title').value = bg.title || '';
    document.getElementById('bg_notes').value = bg.notes || '';
    document.getElementById('bg_set_active').checked = !!bg.is_active;
    document.getElementById('bg_file_req').style.display = 'none';
    document.getElementById('bgFormTitle').textContent = 'Edit Background Sistem: ' + bg.title;
    document.getElementById('bgSubmitBtn').textContent = 'Perbarui Background';
    document.getElementById('bgCancelBtn').style.display = 'inline-flex';
    document.getElementById('bgForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetBgForm() {
    document.getElementById('bgForm').reset();
    document.getElementById('bg_id').value = '';
    document.getElementById('bg_file_req').style.display = 'inline';
    document.getElementById('bgFormTitle').textContent = 'Upload / Kelola Gambar Background Sistem Sesuai Ketentuan UT';
    document.getElementById('bgSubmitBtn').textContent = 'Simpan & Terapkan Background';
    document.getElementById('bgCancelBtn').style.display = 'none';
}

// 9. Confirmation Modal for Activate / Deactivate Status
function openToggleModal(entity, id, tab, label, isCurrentlyActive) {
    document.getElementById('toggle_entity').value = entity;
    document.getElementById('toggle_id').value = id;
    document.getElementById('toggle_tab').value = tab;

    const actionWord = isCurrentlyActive ? 'menonaktifkan' : 'mengaktifkan kembali';
    const btn = document.getElementById('toggleConfirmBtn');

    document.getElementById('toggleModalTitle').textContent = isCurrentlyActive
        ? 'Konfirmasi Nonaktifkan Data'
        : 'Konfirmasi Aktifkan Data';

    document.getElementById('toggleModalMessage').innerHTML =
        `Apakah Anda yakin ingin <strong>${actionWord}</strong> data <strong>${label}</strong>?<br><span style="font-size:12.5px; color:#64748b;">Perubahan status ini akan dicatat pada Audit Trail sistem.</span>`;

    btn.textContent = isCurrentlyActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan';
    btn.className = isCurrentlyActive ? 'btn-digi btn-digi-red' : 'btn-digi btn-digi-green';

    document.getElementById('toggleStatusModal').style.display = 'flex';
}

function closeToggleModal() {
    document.getElementById('toggleStatusModal').style.display = 'none';
}

// 10. Two-Step Verification Modal for Deleting Master Data
function openDeleteModal(actionUrl, categoryLabel, targetLabel) {
    const form = document.getElementById('deleteMasterForm');
    form.action = actionUrl;

    document.getElementById('deleteEntityCategory').textContent = categoryLabel;
    document.getElementById('deleteTargetLabel').textContent = targetLabel;
    document.getElementById('deleteTargetLabelStep2').textContent = targetLabel;

    backToDeleteStep1();
    document.getElementById('deleteMasterModal').style.display = 'flex';
}

function proceedToDeleteStep2() {
    document.getElementById('delete_verification_step').value = '2';
    document.getElementById('deleteStepBadge').textContent = 'LANGKAH 2 / 2';
    document.getElementById('deleteModalTitle').textContent = 'Verifikasi Akhir Penghapusan Data';

    document.getElementById('deleteStep1Body').style.display = 'none';
    document.getElementById('deleteStep1Footer').style.display = 'none';
    document.getElementById('deleteStep2Body').style.display = 'block';
    document.getElementById('deleteStep2Footer').style.display = 'flex';

    document.getElementById('deleteConfirmCheckbox').checked = false;
    const kwInput = document.getElementById('deleteConfirmKeyword');
    kwInput.value = '';
    validateDeleteStep2();
    setTimeout(() => kwInput.focus(), 80);
}

function backToDeleteStep1() {
    document.getElementById('delete_verification_step').value = '1';
    document.getElementById('deleteStepBadge').textContent = 'LANGKAH 1 / 2';
    document.getElementById('deleteModalTitle').textContent = 'Konfirmasi Hapus Master Data';

    document.getElementById('deleteStep1Body').style.display = 'block';
    document.getElementById('deleteStep1Footer').style.display = 'flex';
    document.getElementById('deleteStep2Body').style.display = 'none';
    document.getElementById('deleteStep2Footer').style.display = 'none';

    document.getElementById('deleteConfirmCheckbox').checked = false;
    document.getElementById('deleteConfirmKeyword').value = '';
    validateDeleteStep2();
}

function validateDeleteStep2() {
    const isChecked = document.getElementById('deleteConfirmCheckbox').checked;
    const keyword = (document.getElementById('deleteConfirmKeyword').value || '').trim().toUpperCase();
    const submitBtn = document.getElementById('deleteFinalSubmitBtn');

    if (isChecked && keyword === 'HAPUS') {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    } else {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
    }
}

function closeDeleteModal() {
    document.getElementById('deleteMasterModal').style.display = 'none';
}

function updatePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
@endsection
