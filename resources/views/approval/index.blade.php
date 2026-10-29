@extends('layouts.app')

@section('title', 'Daftar Pengajuan Fuel')

@section('content')
<div class="digi-card">
    <div class="digi-table-top">
        <div>
            <h1 class="digi-table-title">Daftar Pengajuan Bahan Bakar</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Alur berjenjang: <strong>Employee → SPV/Dept Head → Dept Head/Manager → Vendor → Realisasi Fuel Man</strong>
                @if($user && $user->isVendor() && $user->vendor)
                    | Vendor Aktif: <strong>{{ $user->vendor->name }}</strong>
                @endif
            </p>
        </div>

        <form action="{{ route('approval.index') }}" method="GET" class="digi-table-filters">
            @if(isset($areas) && $areas->count() > 0)
                <div class="digi-filter-group">
                    <label class="digi-filter-label">Area Operasional</label>
                    <select name="area" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 150px;">
                        <option value="">Semua Area</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->name }}" {{ request('area') === $a->name ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="digi-filter-group">
                <label class="digi-filter-label">Status</label>
                <select name="status" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 130px;">
                    <option value="">Semua Status</option>
                    <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu..</option>
                    <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <button type="submit" class="btn-digi btn-digi-blue" style="padding: 7px 16px; font-size: 13px;">
                Filter
            </button>

            <a href="{{ route('history.export', request()->query()) }}" class="btn-digi btn-digi-green" style="padding: 7px 18px; font-size: 13px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Export to Excel
            </a>
        </form>
    </div>

    <!-- Compact Role-Aware Summary Strip -->
    @if(isset($stats))
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 16px; margin-bottom: 18px; font-size: 12.5px;">
            <span style="font-weight: 700; color: #334155;">Status Antrean:</span>
            <span class="digi-badge digi-badge-yellow">Menunggu SPV: {{ $stats['pending_spv'] }}</span>
            <span class="digi-badge digi-badge-yellow">Menunggu DeptHead: {{ $stats['pending_depthead'] }}</span>
            <span class="digi-badge digi-badge-yellow">Menunggu Vendor: {{ $stats['pending_vendor'] }}</span>
            <span class="digi-badge digi-badge-blue">Siap Diisi BBM: {{ $stats['ready_fueling'] }}</span>
            <span class="digi-badge digi-badge-green">Disetujui / Selesai: {{ $stats['approved_total'] }}</span>
            @if($stats['rejected_total'] > 0)
                <span class="digi-badge digi-badge-red">Ditolak: {{ $stats['rejected_total'] }}</span>
            @endif
        </div>
    @endif

    <!-- Table -->
    <div style="overflow-x: auto;">
        <table class="digi-table">
            <thead>
                <tr>
                    <th>No <span class="sort-icon">↕</span></th>
                    <th>No. Surat & Tanggal <span class="sort-icon">↕</span></th>
                    <th>Nama / Area <span class="sort-icon">↕</span></th>
                    <th>Unit <span class="sort-icon">↕</span></th>
                    <th>Jumlah (L) <span class="sort-icon">↕</span></th>
                    <th>Status SPV <span class="sort-icon">↕</span></th>
                    <th>Status DeptHead <span class="sort-icon">↕</span></th>
                    <th>Status Vendor/Fuelman <span class="sort-icon">↕</span></th>
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
                                <!-- Review / Detail Action Button -->
                                <a href="{{ route('approval.show', $req->id) }}" class="digi-action-btn digi-action-purple" title="Periksa / Setujui / Catat Realisasi">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </a>

                                <!-- Print Doc Link -->
                                <a href="{{ route('fuel.document', $req->id) }}" target="_blank" class="digi-action-btn digi-action-green" title="Cetak Surat Fuel Request">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 36px;">
                            Tidak ada data pengajuan yang memerlukan tindakan saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
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
@endsection
