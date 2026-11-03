@extends('layouts.app')

@section('title', 'History Pengajuan Fuel')

@section('content')
<div class="digi-card">
    <!-- Header & Filter Row -->
    <div class="digi-table-top">
        <h1 class="digi-table-title">Daftar Fuel</h1>

        <form action="{{ route('history.index') }}" method="GET" class="digi-table-filters">
            <div class="digi-filter-group">
                <label class="digi-filter-label">Pilih Bulan</label>
                <input type="month" name="month" class="digi-input" style="padding: 7px 12px; font-size: 13px;" value="{{ request('month') }}">
            </div>

            @if(isset($areas) && $areas->count() > 0)
                <div class="digi-filter-group">
                    <label class="digi-filter-label">Pilih Area</label>
                    <select name="area" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 140px;">
                        <option value="">Semua Area</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->name }}" {{ request('area') === $a->name ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="digi-filter-group">
                <label class="digi-filter-label">Pilih Status</label>
                <select name="status" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 140px;">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu..</option>
                    <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <button type="submit" class="btn-digi btn-digi-blue" style="padding: 7px 18px; font-size: 13px;">
                Terapkan Filter
            </button>

            <a href="{{ route('history.export', request()->query()) }}" class="btn-digi btn-digi-green" style="padding: 7px 18px; font-size: 13px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="8" y1="13" x2="16" y2="13"></line>
                    <line x1="8" y1="17" x2="16" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Export to Excel
            </a>
        </form>
    </div>

    <!-- Controls: Show & Search -->
    <div class="digi-table-controls">
        <div>
            Show 
            <select onchange="updatePerPage(this.value)">
                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>

        <div>
            Search: 
            <input type="text" id="searchInput" placeholder="Cari No, Nama, Unit..." value="{{ request('search') }}" onkeyup="handleSearch(event)">
        </div>
    </div>

    <!-- Table -->
    <div style="overflow-x: auto;">
        <table class="digi-table">
            <thead>
                <tr>
                    <th>No <span class="sort-icon">↕</span></th>
                    <th>No. Surat & Tanggal <span class="sort-icon">↕</span></th>
                    <th>Nama <span class="sort-icon">↕</span></th>
                    <th>Unit <span class="sort-icon">↕</span></th>
                    <th>Jumlah <span class="sort-icon">↕</span></th>
                    <th>Status SPV <span class="sort-icon">↕</span></th>
                    <th>Status DeptHead <span class="sort-icon">↕</span></th>
                    <th>Status Vendor BBM <span class="sort-icon">↕</span></th>
                    <th>Action <span class="sort-icon">↕</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $idx => $req)
                    <tr>
                        <td>{{ $requests->firstItem() + $idx }}</td>
                        <td>
                            <div style="font-weight: 700; color: #0284c7;">{{ $req->request_no }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $req->request_date->format('d-m-Y H:i') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $req->driver_name }}</div>
                            <div style="font-size: 11.5px; color: #64748b;">{{ $req->area }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $req->unit_code }}</div>
                            <div style="font-size: 11.5px; color: #64748b;">{{ $req->police_no }}</div>
                        </td>
                        <td>
                            <div><strong>{{ (float)$req->total_liters }}</strong> L</div>
                            @if($req->actual_liters !== null)
                                <div style="font-size: 11px; color: #15803d; font-weight: 600;">Aktual: {{ (float)$req->actual_liters }} L</div>
                            @endif
                        </td>
                        <td>
                            @if($req->status_spv === 'disetujui')
                                <span class="digi-badge digi-badge-green">Disetujui</span>
                            @elseif($req->status_spv === 'ditolak')
                                <span class="digi-badge digi-badge-red">Ditolak</span>
                            @elseif($req->status_spv === 'menunggu')
                                <span class="digi-badge digi-badge-yellow">Menunggu..</span>
                            @elseif($req->status_spv === 'draft')
                                <span class="digi-badge digi-badge-gray">Draft</span>
                            @else
                                <span style="color:#94a3b8;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($req->status_depthead === 'disetujui')
                                <span class="digi-badge digi-badge-green">Disetujui</span>
                            @elseif($req->status_depthead === 'ditolak')
                                <span class="digi-badge digi-badge-red">Ditolak</span>
                            @elseif($req->status_depthead === 'menunggu')
                                <span class="digi-badge digi-badge-yellow">Menunggu..</span>
                            @else
                                <span style="color:#94a3b8;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($req->status_fuelman === 'disetujui' || $req->status_vendor === 'disetujui')
                                <span class="digi-badge digi-badge-green">
                                    {{ $req->current_stage === 'completed' ? 'Selesai Isi' : 'Disetujui' }}
                                </span>
                            @elseif($req->status_fuelman === 'ditolak' || $req->status_vendor === 'ditolak')
                                <span class="digi-badge digi-badge-red">Ditolak</span>
                            @elseif($req->status_fuelman === 'menunggu' || $req->status_vendor === 'menunggu')
                                <span class="digi-badge digi-badge-yellow">Menunggu..</span>
                            @else
                                <span style="color:#94a3b8;">-</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap: 6px;">
                                <!-- Purple Document View Modal -->
                                <button type="button" class="digi-action-btn digi-action-purple" title="Pratinjau Surat Fuel Request" onclick="openDocModal({{ $req->id }})">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </button>

                                <!-- Direct Printable Link -->
                                <a href="{{ route('fuel.document', $req->id) }}" target="_blank" class="digi-action-btn digi-action-green" title="Cetak / Unduh PDF Surat Fuel Request">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </a>

                                <!-- Detail & Timeline Link -->
                                <a href="{{ route('approval.show', $req->id) }}" class="digi-action-btn digi-action-orange" title="Detail Pengajuan & Timeline Approval">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 14 14"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 36px;">
                            Tidak ada data Fuel Request yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination & Record count -->
    <div class="digi-pagination-bar">
        <div>
            Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} records
        </div>

        <div class="digi-page-btns">
            @if($requests->onFirstPage())
                <span class="digi-page-btn" style="opacity: 0.5; cursor: default;">&lt;</span>
            @else
                <a href="{{ $requests->previousPageUrl() }}" class="digi-page-btn">&lt;</a>
            @endif

            @foreach(range(1, max(1, $requests->lastPage())) as $page)
                <a href="{{ $requests->url($page) }}" class="digi-page-btn {{ $page == $requests->currentPage() ? 'active' : '' }}">
                    {{ $page }}
                </a>
            @endforeach

            @if($requests->hasMorePages())
                <a href="{{ $requests->nextPageUrl() }}" class="digi-page-btn">&gt;</a>
            @else
                <span class="digi-page-btn" style="opacity: 0.5; cursor: default;">&gt;</span>
            @endif
        </div>
    </div>
</div>

<!-- MODAL POPUP FOR FUEL REQUEST DOCUMENT (Slide 11) -->
<div id="documentModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 820px; max-height: 90vh; overflow-y: auto;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Pratinjau Dokumen Fuel Request</h3>
            <button type="button" class="digi-modal-close" onclick="closeDocModal()">&times;</button>
        </div>
        <div class="digi-modal-body" id="modalDocContent" style="padding: 24px; background:#f8fafc;">
            <div style="text-align:center; padding: 30px; color:#64748b;">Memuat dokumen...</div>
        </div>
        <div class="digi-modal-footer">
            <button type="button" class="btn-digi btn-digi-gray" onclick="closeDocModal()">Tutup</button>
            <button type="button" class="btn-digi btn-digi-blue" onclick="printDocModal()">Cetak / Simpan PDF</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updatePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    window.location.href = url.toString();
}

function handleSearch(e) {
    if (e.key === 'Enter') {
        const url = new URL(window.location.href);
        url.searchParams.set('search', e.target.value);
        window.location.href = url.toString();
    }
}

function openDocModal(id) {
    const modal = document.getElementById('documentModal');
    const content = document.getElementById('modalDocContent');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;">Memuat dokumen Fuel Request...</div>';
    
    fetch(`/fuel/${id}/document-partial`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(err => {
            content.innerHTML = '<div style="text-align:center; color:#ef4444; padding:20px;">Gagal memuat dokumen.</div>';
        });
}

function closeDocModal() {
    document.getElementById('documentModal').style.display = 'none';
}

function printDocModal() {
    const content = document.getElementById('modalDocContent').innerHTML;
    const printWindow = window.open('', '', 'height=700,width=900');
    printWindow.document.write('<html><head><title>Fuel Request Document</title>');
    printWindow.document.write('<link rel="stylesheet" href="{{ asset("css/digifuel.css") }}">');
    printWindow.document.write('</head><body style="background:#fff; padding:20px;">');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
    }, 400);
}
</script>
@endsection
