@extends('layouts.app')

@section('title', 'Evaluasi & Monitoring Konsumsi BBM')

@section('content')
<div class="digi-card">
    <div class="digi-table-top">
        <div>
            <h1 class="digi-table-title">Evaluasi Konsumsi BBM & Estimasi Biaya Kerugian</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Analisis perbandingan konsumsi aktual vs standar (KM/L), varian pemborosan, dan estimasi kerugian biaya BBM per unit.
            </p>
        </div>

        <form action="{{ route('evaluation.index') }}" method="GET" class="digi-table-filters">
            <div class="digi-filter-group">
                <label class="digi-filter-label">Periode Evaluasi</label>
                <input type="month" name="period" class="digi-input" style="padding: 7px 12px; font-size: 13px;" value="{{ $period }}">
            </div>

            <div class="digi-filter-group">
                <label class="digi-filter-label">Unit Code</label>
                <select name="unit_code" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 150px;">
                    <option value="">Semua Unit</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->unit_code }}" {{ request('unit_code') === $v->unit_code ? 'selected' : '' }}>{{ $v->unit_code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="digi-filter-group">
                <label class="digi-filter-label">Vendor Unit</label>
                <select name="unit_vendor_id" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 160px;">
                    <option value="">Semua Vendor Unit</option>
                    @foreach($unitVendors as $uv)
                        <option value="{{ $uv->id }}" {{ request('unit_vendor_id') == $uv->id ? 'selected' : '' }}>{{ $uv->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="digi-filter-group">
                <label class="digi-filter-label">Status Evaluasi</label>
                <select name="status" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 145px;">
                    <option value="">Semua Status</option>
                    <option value="normal" {{ request('status') === 'normal' ? 'selected' : '' }}>Normal (Wajar)</option>
                    <option value="needs_review" {{ request('status') === 'needs_review' ? 'selected' : '' }}>Needs Review (Tidak Wajar)</option>
                    <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                    <option value="invalid_data" {{ request('status') === 'invalid_data' ? 'selected' : '' }}>Invalid Data</option>
                </select>
            </div>

            <button type="submit" class="btn-digi btn-digi-blue" style="padding: 7px 16px; font-size: 13px;">
                Terapkan Filter
            </button>

            <button type="button" class="btn-digi btn-digi-yellow" style="padding: 7px 16px; font-size: 13px;" onclick="openCalcModal()">
                Hitung Evaluasi
            </button>

            <a href="{{ route('evaluation.export', array_merge(request()->query(), ['period' => $period])) }}" class="btn-digi btn-digi-green" style="padding: 7px 16px; font-size: 13px;">
                Export Excel (.xlsx)
            </a>
        </form>
    </div>

    <!-- KPI Summary Box (Matches Dashboard Monitoring BBM Reference) -->
    <div class="digi-form-box" style="margin-bottom: 22px; padding: 18px 22px; background: #e0f2fe; border-color: #7dd3fc;">
        <div class="digi-kpi-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(195px, 1fr)); gap: 14px; text-align: center;">
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 14px;">
                <div style="font-size: 11.5px; color: #334155; font-weight: 700;">TOTAL JARAK (KM)</div>
                <div style="font-size: 21px; font-weight: 800; color: #0369a1; margin-top: 4px;">
                    {{ number_format($summary['total_distance'], 0, ',', '.') }}
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 14px;">
                <div style="font-size: 11.5px; color: #334155; font-weight: 700;">TOTAL PENGISIAN FUEL (L)</div>
                <div style="font-size: 21px; font-weight: 800; color: #0369a1; margin-top: 4px;">
                    {{ number_format($summary['total_actual_liters'], 1, ',', '.') }}
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 14px;">
                <div style="font-size: 11.5px; color: #334155; font-weight: 700;">RATA-RATA RASIO (KM/L)</div>
                <div style="font-size: 21px; font-weight: 800; color: #0369a1; margin-top: 4px;">
                    {{ number_format($summary['avg_ratio'], 1, ',', '.') }}
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 14px;">
                <div style="font-size: 11.5px; color: #334155; font-weight: 700;">TOTAL BIAYA (IDR)</div>
                <div style="font-size: 20px; font-weight: 800; color: #0369a1; margin-top: 4px;">
                    Rp {{ number_format($summary['total_actual_cost'], 0, ',', '.') }}
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px;">
                <div style="font-size: 11.5px; color: #991b1b; font-weight: 700;">VARIAN PEMBOROSAN (L)</div>
                <div style="font-size: 20px; font-weight: 800; color: #dc2626; margin-top: 4px;">
                    {{ number_format($summary['total_waste_liters'], 1, ',', '.') }}
                </div>
            </div>
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px 14px;">
                <div style="font-size: 11.5px; color: #991b1b; font-weight: 700;">BIAYA KERUGIAN (IDR)</div>
                <div style="font-size: 20px; font-weight: 800; color: #b91c1c; margin-top: 4px;">
                    Rp {{ number_format($summary['total_cost_loss'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Charts Section (Chart.js) -->
    @if(count($chartData['unitLabels']) > 0)
        <div class="digi-form-grid-2" style="margin-bottom: 24px;">
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #fff;">
                <div style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 10px;">
                    Perbandingan BBM Aktual vs BBM Standar per Unit (Liter)
                </div>
                <canvas id="consumptionChart" height="135"></canvas>
            </div>
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #fff;">
                <div style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 10px;">
                    Unit dengan Estimasi Biaya Kerugian Tertinggi (IDR)
                </div>
                <canvas id="wasteCostChart" height="135"></canvas>
            </div>
        </div>
    @endif

    <!-- Table Rangkuman Performa per Unit LV (Matches Dashboard_Monitoring_BBM MEI 2026.xlsx) -->
    <h2 class="digi-section-title" style="margin-bottom: 12px;">Rangkuman Performa per Unit LV ({{ $period }})</h2>
    <div class="digi-mobile-swipe-hint">
        <span>👉 Geser tabel ke samping untuk melihat rasio, deviasi & biaya</span>
    </div>

    <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
        <table class="digi-table">
            <thead>
                <tr style="background: #1e3a8a; color: #ffffff;">
                    <th style="color: #ffffff; background: #1e3a8a;">ID Kendaraan</th>
                    <th style="color: #ffffff; background: #1e3a8a;">Tipe Unit</th>
                    <th style="color: #ffffff; background: #1e3a8a;">Vendor Unit</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Total Jarak (Km)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Total Pengisian Fuel (L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Rasio Aktual (Km/L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Rasio Standar (Km/L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: center;">Evaluasi per Unit</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Total Biaya (IDR)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Varian Pemborosan (L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Biaya Kerugian (IDR)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: center;">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluations as $idx => $eval)
                    <tr>
                        <td style="font-weight: 600;">
                            <a href="{{ route('evaluation.show', $eval->id) }}" style="color: #0284c7; text-decoration: none; font-weight: 700;">
                                {{ $eval->vehicle?->unit_code ?? '-' }}
                            </a>
                        </td>
                        <td style="font-size: 13px;">{{ $eval->vehicle?->model ?? $eval->vehicle?->unit_type ?? '-' }}</td>
                        <td>
                            <span class="digi-badge digi-badge-blue" style="font-size: 11px; white-space: nowrap;">
                                {{ $eval->vehicle?->unitVendor?->name ?? 'Internal UT' }}
                            </span>
                        </td>
                        <td style="text-align: right;">{{ number_format($eval->total_distance_or_hours, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($eval->total_actual_liters, 1, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($eval->actual_ratio, 1, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($eval->standard_used, 1, ',', '.') }}</td>
                        <td style="text-align: center;">
                            @if($eval->evaluation_status === 'normal')
                                <span class="digi-badge digi-badge-green" style="min-width: 96px; text-align: center;">Wajar</span>
                            @elseif($eval->evaluation_status === 'needs_review')
                                <span class="digi-badge digi-badge-red" style="min-width: 96px; text-align: center;">Tidak Wajar</span>
                            @elseif($eval->evaluation_status === 'incomplete')
                                <span class="digi-badge digi-badge-yellow">Incomplete</span>
                            @else
                                <span class="digi-badge digi-badge-gray">Invalid Data</span>
                            @endif
                        </td>
                        <td style="text-align: right;">Rp {{ number_format($eval->total_actual_cost, 0, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: {{ $eval->waste_liters > 0 ? '700' : '400' }}; color: {{ $eval->waste_liters > 0 ? '#dc2626' : '#1e293b' }};">
                            {{ number_format($eval->waste_liters, 1, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: {{ $eval->estimated_cost_loss > 0 ? '700' : '400' }}; color: {{ $eval->estimated_cost_loss > 0 ? '#b91c1c' : '#1e293b' }};">
                            Rp {{ number_format($eval->estimated_cost_loss, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('evaluation.show', $eval->id) }}" class="digi-action-btn digi-action-purple" title="Lihat Rincian Perhitungan Unit">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" style="text-align: center; color: #94a3b8; padding: 36px;">
                            Belum ada data evaluasi pada periode <strong>{{ $period }}</strong>. Klik tombol <strong>Hitung Evaluasi</strong> untuk menjalankan kalkulasi otomatis dari transaksi BBM.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($evaluations->count() > 0)
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
                        <td></td>
                        <td colspan="2">Total / Rata-rata Nasional</td>
                        <td style="text-align: right;">{{ number_format($summary['total_distance'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($summary['total_actual_liters'], 1, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($summary['avg_ratio'], 1, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($summary['avg_standard_ratio'] ?? 7.6, 1, ',', '.') }}</td>
                        <td></td>
                        <td style="text-align: right;">Rp {{ number_format($summary['total_actual_cost'], 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #dc2626;">{{ number_format($summary['total_waste_liters'], 1, ',', '.') }}</td>
                        <td style="text-align: right; color: #b91c1c;">Rp {{ number_format($summary['total_cost_loss'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <!-- Pagination -->
    <div class="digi-pagination-bar">
        <div>
            Showing {{ $evaluations->firstItem() ?? 0 }} to {{ $evaluations->lastItem() ?? 0 }} of {{ $evaluations->total() }} unit evaluations
        </div>
        <div class="digi-page-btns">
            @foreach(range(1, max(1, $evaluations->lastPage())) as $page)
                <a href="{{ $evaluations->url($page) }}" class="digi-page-btn {{ $page == $evaluations->currentPage() ? 'active' : '' }}">
                    {{ $page }}
                </a>
            @endforeach
        </div>
    </div>
</div>

<!-- MODAL HITUNG / SIMULASI EVALUASI UNIT -->
<div id="calcModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 520px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Hitung Evaluasi Konsumsi BBM</h3>
            <button type="button" class="digi-modal-close" onclick="closeCalcModal()">&times;</button>
        </div>
        <form action="{{ route('evaluation.calculate') }}" method="POST" style="margin: 0;">
            @csrf
            <div class="digi-modal-body">
                <p style="margin: 0 0 14px 0; font-size: 13px; color: #475569;">
                    Pilih periode untuk menghitung ulang seluruh unit secara otomatis dari transaksi realisasi, atau pilih satu unit spesifik untuk evaluasi meter awal & akhir.
                </p>
                <div class="digi-form-group" style="margin-bottom: 14px;">
                    <label class="digi-form-label">Periode Evaluasi (Bulan) <span class="digi-required">*</span></label>
                    <input type="month" name="period" class="digi-input" value="{{ $period }}" required>
                </div>
                <div class="digi-form-group" style="margin-bottom: 14px;">
                    <label class="digi-form-label">Pilih Unit (Kosongkan untuk hitung semua unit aktif)</label>
                    <select name="vehicle_id" class="digi-select">
                        <option value="">-- Hitung Seluruh Unit Aktif --</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->unit_code }} ({{ $v->police_no }} - {{ $v->model }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-grid-3" style="margin-bottom: 0;">
                    <div class="digi-form-group">
                        <label class="digi-form-label">KM Awal (Opsional)</label>
                        <input type="number" step="0.1" name="meter_start" class="digi-input" placeholder="Otomatis">
                    </div>
                    <div class="digi-form-group">
                        <label class="digi-form-label">KM Akhir (Opsional)</label>
                        <input type="number" step="0.1" name="meter_end" class="digi-input" placeholder="Otomatis">
                    </div>
                    <div class="digi-form-group">
                        <label class="digi-form-label">BBM Aktual L (Opsional)</label>
                        <input type="number" step="0.1" name="total_actual_liters" class="digi-input" placeholder="Otomatis">
                    </div>
                </div>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeCalcModal()">Batal</button>
                <button type="submit" class="btn-digi btn-digi-blue">Jalankan Perhitungan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function openCalcModal() {
    document.getElementById('calcModal').style.display = 'flex';
}
function closeCalcModal() {
    document.getElementById('calcModal').style.display = 'none';
}

const chartData = @json($chartData);
if (typeof Chart !== 'undefined' && chartData.unitLabels && chartData.unitLabels.length > 0) {
    const ctx1 = document.getElementById('consumptionChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: chartData.unitLabels,
                datasets: [
                    {
                        label: 'BBM Aktual (Liter)',
                        data: chartData.actualLiters,
                        backgroundColor: '#0284c7',
                        borderRadius: 4
                    },
                    {
                        label: 'BBM Standar (Liter)',
                        data: chartData.standardLiters,
                        backgroundColor: '#94a3b8',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    const ctx2 = document.getElementById('wasteCostChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: chartData.wasteLabels,
                datasets: [
                    {
                        label: 'Estimasi Biaya Kerugian (IDR)',
                        data: chartData.wasteCosts,
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
}
</script>
@endsection

