@extends('layouts.app')

@section('title', 'Evaluasi & Monitoring Konsumsi BBM')

@section('content')
<div class="digi-card">
    <div class="digi-table-top" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 16px;">
        <div style="margin-bottom: 14px;">
            <h1 class="digi-table-title">Evaluasi Konsumsi BBM & Estimasi Biaya Kerugian</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Analisis perbandingan konsumsi aktual vs standar (KM/L), varian pemborosan, dan estimasi kerugian biaya BBM per unit.
            </p>
        </div>

        <form action="{{ route('evaluation.index') }}" method="GET" style="width: 100%; margin: 0;" id="evaluationFilterForm">
            <!-- Mode Switcher (Bulanan, Rentang Bulan, Tahunan) -->
            <div class="digi-segmented-wrapper">
                <span class="digi-segmented-label">Mode Periode:</span>
                <div class="digi-segmented-group">
                    <button type="button" class="digi-segmented-btn {{ $mode === 'monthly' ? 'active' : '' }}" onclick="switchMode('monthly')">
                        Bulanan
                    </button>
                    <button type="button" class="digi-segmented-btn {{ $mode === 'range' ? 'active' : '' }}" onclick="switchMode('range')">
                        Rentang Bulan
                    </button>
                    <button type="button" class="digi-segmented-btn {{ $mode === 'yearly' ? 'active' : '' }}" onclick="switchMode('yearly')">
                        Tahunan
                    </button>
                </div>
                <input type="hidden" name="mode" id="period_mode_input" value="{{ $mode }}">
            </div>

            <!-- Filter Controls Grid -->
            <div class="digi-eval-filters-grid">
                <!-- Dynamic Input: Mode Bulanan -->
                <div class="digi-filter-group" id="group-monthly" style="{{ $mode === 'monthly' ? '' : 'display: none;' }}">
                    <label class="digi-filter-label">Periode Bulan</label>
                    <input type="month" name="period" id="input_period" class="digi-input" style="padding: 7px 12px; font-size: 13px;" value="{{ $period }}">
                </div>

                <!-- Dynamic Input: Mode Rentang Penggunaan -->
                <div class="digi-filter-group" id="group-range-start" style="{{ $mode === 'range' ? '' : 'display: none;' }}">
                    <label class="digi-filter-label">Dari Bulan</label>
                    <input type="month" name="period_start" id="input_period_start" class="digi-input" style="padding: 7px 12px; font-size: 13px;" value="{{ $periodStart }}">
                </div>
                <div class="digi-filter-group" id="group-range-end" style="{{ $mode === 'range' ? '' : 'display: none;' }}">
                    <label class="digi-filter-label">Sampai Bulan</label>
                    <input type="month" name="period_end" id="input_period_end" class="digi-input" style="padding: 7px 12px; font-size: 13px;" value="{{ $periodEnd }}">
                </div>

                <!-- Dynamic Input: Mode Tahunan -->
                <div class="digi-filter-group" id="group-yearly" style="{{ $mode === 'yearly' ? '' : 'display: none;' }}">
                    <label class="digi-filter-label">Pilih Tahun</label>
                    <select name="year" id="input_year" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 120px;">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-filter-group">
                    <label class="digi-filter-label">Unit Code</label>
                    <select name="unit_code" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 140px;">
                        <option value="">Semua Unit</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->unit_code }}" {{ request('unit_code') === $v->unit_code ? 'selected' : '' }}>{{ $v->unit_code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-filter-group">
                    <label class="digi-filter-label">Vendor Unit</label>
                    <select name="unit_vendor_id" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 150px;">
                        <option value="">Semua Vendor Unit</option>
                        @foreach($unitVendors as $uv)
                            <option value="{{ $uv->id }}" {{ request('unit_vendor_id') == $uv->id ? 'selected' : '' }}>{{ $uv->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="digi-filter-group">
                    <label class="digi-filter-label">Status Evaluasi</label>
                    <select name="status" class="digi-select" style="padding: 7px 12px; font-size: 13px; min-width: 135px;">
                        <option value="">Semua Status</option>
                        <option value="normal" {{ request('status') === 'normal' ? 'selected' : '' }}>Normal (Wajar)</option>
                        <option value="needs_review" {{ request('status') === 'needs_review' ? 'selected' : '' }}>Needs Review (Tidak Wajar)</option>
                        <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                        <option value="invalid_data" {{ request('status') === 'invalid_data' ? 'selected' : '' }}>Invalid Data</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="digi-eval-filter-actions">
                    <button type="submit" class="btn-digi btn-digi-blue btn-eval-apply" style="padding: 7px 16px; font-size: 13px;">
                        Terapkan Filter
                    </button>

                    <button type="button" class="btn-digi btn-digi-yellow btn-eval-calc" style="padding: 7px 16px; font-size: 13px;" onclick="openCalcModal()">
                        Hitung Evaluasi
                    </button>

                    <a href="{{ route('evaluation.export', array_merge(request()->query(), ['mode' => $mode, 'period' => $period, 'period_start' => $periodStart, 'period_end' => $periodEnd, 'year' => $year])) }}" class="btn-digi btn-digi-green btn-eval-export" style="padding: 7px 16px; font-size: 13px;">
                        Export Excel (.xlsx)
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Multi-month View Switcher Bar (Appears when viewing Range or Yearly) -->
    @if($mode !== 'monthly')
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
                <span style="font-weight: 700; color: #1e293b;">Pilihan Tampilan:</span>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'consolidated']) }}" class="btn-period-mode {{ $isConsolidated ? 'active' : '' }}" style="text-decoration: none;">
                    Konsolidasi per Unit (1 Baris/Unit)
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'monthly']) }}" class="btn-period-mode {{ !$isConsolidated ? 'active' : '' }}" style="text-decoration: none;">
                    Rincian Tiap Bulan
                </a>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                @if($isConsolidated)
                    Menampilkan total akumulasi dan rasio tertimbang per unit untuk seluruh <strong>{{ $periodLabel }}</strong>.
                @else
                    Menampilkan seluruh rincian transaksi bulanan unit yang tercatat pada <strong>{{ $periodLabel }}</strong>.
                @endif
            </div>
        </div>
    @endif

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
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                    <div style="font-size: 13.5px; font-weight: 700; color: #1e293b;">
                        Perbandingan BBM Aktual vs BBM Standar (Liter)
                    </div>
                    <span style="font-size: 11px; font-weight: 600; color: #0284c7; background: #e0f2fe; padding: 2px 8px; border-radius: 6px;">
                        Top Unit Konsumsi
                    </span>
                </div>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="consumptionChart"></canvas>
                </div>
            </div>
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                    <div style="font-size: 13.5px; font-weight: 700; color: #1e293b;">
                        Unit dengan Estimasi Kerugian Tertinggi (IDR)
                    </div>
                    <span style="font-size: 11px; font-weight: 600; color: #b91c1c; background: #fee2e2; padding: 2px 8px; border-radius: 6px;">
                        Top Pemborosan
                    </span>
                </div>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="wasteCostChart"></canvas>
                </div>
            </div>
        </div>
    @endif

    <!-- Table Section Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <h2 class="digi-section-title" style="margin-bottom: 0; font-size: 16px;">
                Rangkuman Performa per Unit LV
            </h2>
            <span class="digi-badge digi-badge-blue" style="font-size: 12px; font-weight: 700; vertical-align: middle;">
                {{ $periodLabel }}
            </span>
            @if($isConsolidated)
                <span class="digi-badge digi-badge-purple" style="font-size: 11px; vertical-align: middle;">
                    Konsolidasi Unit
                </span>
            @endif
        </div>
    </div>

    <div class="digi-mobile-swipe-hint">
        <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>Geser tabel ke samping untuk melihat rasio, deviasi & biaya</span>
    </div>

    <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
        <table class="digi-table">
            <thead>
                <tr style="background: #1e3a8a; color: #ffffff;">
                    <th style="color: #ffffff; background: #1e3a8a;">ID Kendaraan</th>
                    <th style="color: #ffffff; background: #1e3a8a;">Tipe Unit</th>
                    <th style="color: #ffffff; background: #1e3a8a;">Vendor Unit</th>
                    <th style="color: #ffffff; background: #1e3a8a;">Divisi / Beban Account</th>
                    @if($isConsolidated)
                        <th style="color: #ffffff; background: #1e3a8a; text-align: center;">Jml Bulan</th>
                    @elseif($mode !== 'monthly')
                        <th style="color: #ffffff; background: #1e3a8a; text-align: center;">Periode</th>
                    @endif
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Total Jarak (Km)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Total Pengisian Fuel (L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Rasio Aktual (Km/L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: right;">Standar (Km/L)</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: center;">Evaluasi Periode</th>
                    <th style="color: #ffffff; background: #1e3a8a; text-align: center;">Kumulatif (Sejak Awal)</th>
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
                            @php
                                $detailId = $isConsolidated ? ($eval->latest_id ?? $eval->id) : $eval->id;
                            @endphp
                            <a href="{{ route('evaluation.show', $detailId) }}" style="color: #0284c7; text-decoration: none; font-weight: 700;">
                                {{ $eval->vehicle?->unit_code ?? '-' }}
                            </a>
                        </td>
                        <td style="font-size: 13px;">{{ $eval->vehicle?->model ?? $eval->vehicle?->unit_type ?? '-' }}</td>
                        <td>
                            <span class="digi-badge digi-badge-blue" style="font-size: 11px; white-space: nowrap;">
                                {{ $eval->vehicle?->unitVendor?->name ?? 'Internal UT' }}
                            </span>
                        </td>
                        <td>
                            @if($eval->vehicle?->division)
                                <span class="digi-badge digi-badge-purple" style="font-size: 11px; white-space: nowrap; font-weight: 600;">
                                    {{ $eval->vehicle->division }}
                                </span>
                            @else
                                <span style="color: #94a3b8; font-size: 12px;">-</span>
                            @endif
                        </td>
                        @if($isConsolidated)
                            <td style="text-align: center;">
                                <span class="digi-badge digi-badge-blue" style="font-size: 11px;" title="Bulan: {{ $eval->period }}">
                                    {{ $eval->period_count }} Bulan
                                </span>
                            </td>
                        @elseif($mode !== 'monthly')
                            <td style="text-align: center; font-size: 12px; font-weight: 600; color: #475569;">
                                {{ $eval->period }}
                            </td>
                        @endif
                        <td style="text-align: right;">{{ number_format($eval->total_distance_or_hours, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($eval->total_actual_liters, 1, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ number_format($eval->actual_ratio, 1, ',', '.') }}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            @if(!empty($eval->standard_max_used) && (float)$eval->standard_max_used > (float)$eval->standard_used)
                                {{ number_format($eval->standard_used, 1, ',', '.') }} – {{ number_format($eval->standard_max_used, 1, ',', '.') }}
                            @else
                                {{ number_format($eval->standard_used, 1, ',', '.') }}
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($eval->evaluation_status === 'normal')
                                <span class="digi-badge digi-badge-green" style="min-width: 92px; text-align: center;">Wajar</span>
                            @elseif($eval->evaluation_status === 'needs_review')
                                <span class="digi-badge digi-badge-red" style="min-width: 92px; text-align: center;">Tidak Wajar</span>
                            @elseif($eval->evaluation_status === 'incomplete')
                                <span class="digi-badge digi-badge-yellow">Incomplete</span>
                            @else
                                <span class="digi-badge digi-badge-gray">Invalid Data</span>
                            @endif
                            @if(!empty($eval->admin_notes))
                                <div style="font-size: 10.5px; color: #0369a1; margin-top: 3px; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Catatan Admin: {{ $eval->admin_notes }}">
                                    &#9998; {{ $eval->admin_notes }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            @if(!empty($eval->cumulative_ratio) && (float)$eval->cumulative_ratio > 0)
                                <div style="font-weight: 700; font-size: 12.5px; color: #0f172a;">{{ number_format((float)$eval->cumulative_ratio, 1, ',', '.') }} Km/L</div>
                                @if($eval->cumulative_status === 'normal')
                                    <span class="digi-badge digi-badge-green" style="font-size: 10.5px; padding: 2px 7px;">Wajar</span>
                                @elseif($eval->cumulative_status === 'needs_review')
                                    <span class="digi-badge digi-badge-red" style="font-size: 10.5px; padding: 2px 7px;">Tidak Wajar</span>
                                @else
                                    <span class="digi-badge digi-badge-gray" style="font-size: 10.5px; padding: 2px 7px;">{{ ucfirst($eval->cumulative_status ?? '-') }}</span>
                                @endif
                            @else
                                <span style="color: #94a3b8; font-size: 12px;">-</span>
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
                            <a href="{{ route('evaluation.show', $detailId) }}" class="digi-action-btn digi-action-purple" title="Lihat Rincian Perhitungan Unit">
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
                        <td colspan="{{ $mode !== 'monthly' ? 15 : 14 }}" style="text-align: center; color: #94a3b8; padding: 36px;">
                            Belum ada data evaluasi pada {{ $periodLabel }}. Klik tombol <strong>Hitung Evaluasi</strong> untuk menjalankan kalkulasi otomatis dari transaksi BBM.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($evaluations->count() > 0)
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
                        <td></td>
                        <td colspan="{{ $mode !== 'monthly' ? 4 : 3 }}">Total / Rata-rata Nasional</td>
                        <td style="text-align: right;">{{ number_format($summary['total_distance'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($summary['total_actual_liters'], 1, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($summary['avg_ratio'], 1, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($summary['avg_standard_ratio'] ?? 7.6, 1, ',', '.') }}</td>
                        <td></td>
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
            Menampilkan {{ $evaluations->firstItem() ?? 0 }} sampai {{ $evaluations->lastItem() ?? 0 }} dari total {{ $evaluations->total() }} data evaluasi
        </div>
        <div class="digi-page-btns">
            @foreach(range(1, max(1, $evaluations->lastPage())) as $page)
                <a href="{{ $evaluations->url($page) }}" class="digi-page-btn {{ $page == $evaluations->currentPage() ? 'active' : '' }}" title="Halaman {{ $page }}">
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
                    Pilih cakupan periode kalkulasi evaluasi otomatis dari transaksi realisasi BBM.
                </p>

                <!-- Calc Mode Switcher -->
                <div class="digi-segmented-group" style="width: 100%; display: grid; grid-template-columns: 1fr 1fr 1fr; margin-bottom: 14px;">
                    <button type="button" class="digi-segmented-btn active" id="btn-calc-monthly" onclick="switchCalcMode('monthly')">
                        1 Bulan
                    </button>
                    <button type="button" class="digi-segmented-btn" id="btn-calc-range" onclick="switchCalcMode('range')">
                        Rentang Bulan
                    </button>
                    <button type="button" class="digi-segmented-btn" id="btn-calc-yearly" onclick="switchCalcMode('yearly')">
                        1 Tahun Penuh
                    </button>
                </div>
                <input type="hidden" name="calc_mode" id="calc_mode_input" value="monthly">

                <!-- 1 Bulan Input -->
                <div id="calc-monthly-container" class="digi-form-group" style="margin-bottom: 14px;">
                    <label class="digi-form-label">Periode Evaluasi (Bulan) <span class="digi-required">*</span></label>
                    <input type="month" name="period" class="digi-input" value="{{ $period }}">
                </div>

                <!-- Rentang Bulan Inputs -->
                <div id="calc-range-container" style="display: none; margin-bottom: 14px;">
                    <div class="digi-form-grid-2" style="margin-bottom: 0;">
                        <div class="digi-form-group">
                            <label class="digi-form-label">Dari Bulan <span class="digi-required">*</span></label>
                            <input type="month" name="calc_start" class="digi-input" value="{{ $periodStart }}">
                        </div>
                        <div class="digi-form-group">
                            <label class="digi-form-label">Sampai Bulan <span class="digi-required">*</span></label>
                            <input type="month" name="calc_end" class="digi-input" value="{{ $periodEnd }}">
                        </div>
                    </div>
                </div>

                <!-- 1 Tahun Penuh Input -->
                <div id="calc-yearly-container" class="digi-form-group" style="display: none; margin-bottom: 14px;">
                    <label class="digi-form-label">Tahun (12 Bulan Sekaligus) <span class="digi-required">*</span></label>
                    <select name="calc_year" class="digi-select">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>
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

                <div class="digi-form-grid-3" style="margin-bottom: 0;" id="calc-meter-overrides">
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
function switchMode(selectedMode) {
    document.getElementById('period_mode_input').value = selectedMode;

    // Update active segmented button
    document.querySelectorAll('.digi-segmented-wrapper .digi-segmented-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.currentTarget.classList.add('active');

    const groupMonthly = document.getElementById('group-monthly');
    const groupRangeStart = document.getElementById('group-range-start');
    const groupRangeEnd = document.getElementById('group-range-end');
    const groupYearly = document.getElementById('group-yearly');

    if (selectedMode === 'monthly') {
        groupMonthly.style.display = '';
        groupRangeStart.style.display = 'none';
        groupRangeEnd.style.display = 'none';
        groupYearly.style.display = 'none';
    } else if (selectedMode === 'range') {
        groupMonthly.style.display = 'none';
        groupRangeStart.style.display = '';
        groupRangeEnd.style.display = '';
        groupYearly.style.display = 'none';
    } else if (selectedMode === 'yearly') {
        groupMonthly.style.display = 'none';
        groupRangeStart.style.display = 'none';
        groupRangeEnd.style.display = 'none';
        groupYearly.style.display = '';
    }
}

function openCalcModal() {
    document.getElementById('calcModal').style.display = 'flex';
}
function closeCalcModal() {
    document.getElementById('calcModal').style.display = 'none';
}

function switchCalcMode(cMode) {
    document.getElementById('calc_mode_input').value = cMode;

    const bMonthly = document.getElementById('btn-calc-monthly');
    const bRange = document.getElementById('btn-calc-range');
    const bYearly = document.getElementById('btn-calc-yearly');

    bMonthly.classList.remove('active');
    bRange.classList.remove('active');
    bYearly.classList.remove('active');

    const cMonthly = document.getElementById('calc-monthly-container');
    const cRange = document.getElementById('calc-range-container');
    const cYearly = document.getElementById('calc-yearly-container');
    const cOverrides = document.getElementById('calc-meter-overrides');

    if (cMode === 'monthly') {
        bMonthly.classList.add('active');
        cMonthly.style.display = '';
        cRange.style.display = 'none';
        cYearly.style.display = 'none';
        cOverrides.style.display = 'grid';
    } else if (cMode === 'range') {
        bRange.classList.add('active');
        cMonthly.style.display = 'none';
        cRange.style.display = 'block';
        cYearly.style.display = 'none';
        cOverrides.style.display = 'none';
    } else if (cMode === 'yearly') {
        bYearly.classList.add('active');
        cMonthly.style.display = 'none';
        cRange.style.display = 'none';
        cYearly.style.display = '';
        cOverrides.style.display = 'none';
    }
}

const chartData = @json($chartData);
if (typeof Chart !== 'undefined' && chartData.unitLabels && chartData.unitLabels.length > 0) {
    const isMobile = window.innerWidth < 768;

    // 1. Consumption Bar Chart (Aktual vs Standar)
    const ctx1 = document.getElementById('consumptionChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: chartData.unitLabels,
                datasets: [
                    {
                        label: 'BBM Aktual (L)',
                        data: chartData.actualLiters,
                        backgroundColor: '#0284c7',
                        borderRadius: 4,
                        barPercentage: isMobile ? 0.8 : 0.6,
                        categoryPercentage: isMobile ? 0.85 : 0.7
                    },
                    {
                        label: 'BBM Standar (L)',
                        data: chartData.standardLiters,
                        backgroundColor: '#94a3b8',
                        borderRadius: 4,
                        barPercentage: isMobile ? 0.8 : 0.6,
                        categoryPercentage: isMobile ? 0.85 : 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: isMobile ? 11 : 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                return (chartData.fullLabels && chartData.fullLabels[idx]) ? chartData.fullLabels[idx] : items[0].label;
                            },
                            label: function(item) {
                                return ` ${item.dataset.label}: ${Number(item.raw).toLocaleString('id-ID', { minimumFractionDigits: 1 })} L`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: isMobile ? 30 : 0,
                            minRotation: 0,
                            autoSkip: false,
                            font: {
                                size: isMobile ? 10 : 11,
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 10 },
                            callback: function(val) {
                                return val.toLocaleString('id-ID') + ' L';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Waste Cost Horizontal Bar Chart
    const ctx2 = document.getElementById('wasteCostChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: chartData.wasteLabels,
                datasets: [
                    {
                        label: 'Estimasi Kerugian (IDR)',
                        data: chartData.wasteCosts,
                        backgroundColor: '#ef4444',
                        borderRadius: 4,
                        barThickness: isMobile ? 14 : 18
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: isMobile ? 11 : 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                return (chartData.wasteFullLabels && chartData.wasteFullLabels[idx]) ? chartData.wasteFullLabels[idx] : items[0].label;
                            },
                            label: function(item) {
                                return ` Biaya Kerugian: Rp ${Number(item.raw).toLocaleString('id-ID')}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 10 },
                            callback: function(val) {
                                if (val >= 1000000) {
                                    return (val / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
                                } else if (val >= 1000) {
                                    return (val / 1000).toLocaleString('id-ID') + ' rb';
                                }
                                return val;
                            }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: {
                                size: isMobile ? 10 : 11,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    }
}
</script>
@endsection
