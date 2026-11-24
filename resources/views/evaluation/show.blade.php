@extends('layouts.app')

@section('title', 'Detail Evaluasi Unit ' . ($evaluation->vehicle?->unit_code ?? ''))

@section('content')
<div class="digi-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h1 class="digi-table-title">Detail Evaluasi BBM Unit: {{ $evaluation->vehicle?->unit_code }}</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Nomor Evaluasi: <strong>{{ $evaluation->evaluation_no }}</strong> | Periode: <strong>{{ $evaluation->period }}</strong> | No. Polisi: <strong>{{ $evaluation->vehicle?->police_no }}</strong>
            </p>
        </div>
        <a href="{{ route('evaluation.index', ['period' => $evaluation->period]) }}" class="btn-digi btn-digi-gray">
            ← Kembali ke Evaluasi BBM
        </a>
    </div>

    <div class="digi-form-box">
        <h3 class="digi-section-title">1. Evaluasi Periode {{ $evaluation->period }}</h3>
        <div class="digi-form-grid-3">
            <div class="digi-form-group">
                <label class="digi-form-label">KM Awal → Akhir</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->meter_start ?? 0, 0, ',', '.') }} → {{ number_format($evaluation->meter_end ?? 0, 0, ',', '.') }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Total Jarak Tempuh (KM)</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->total_distance_or_hours, 2, ',', '.') }} KM" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Rentang Standar Konsumsi Berlaku</label>
                <input type="text" class="digi-input" value="{{ $evaluation->standard_range_label }} KM / Liter (Toleransi: {{ (float)$evaluation->tolerance_used }}%)" readonly disabled>
            </div>
        </div>

        <div class="digi-form-grid-3">
            <div class="digi-form-group">
                <label class="digi-form-label">BBM Standar Seharusnya</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->total_standard_liters, 2, ',', '.') }} Liter" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Total BBM Aktual Tercatat</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->total_actual_liters, 2, ',', '.') }} Liter (Rasio Aktual: {{ number_format($evaluation->actual_ratio, 2, ',', '.') }} Km/L)" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Selisih & Pemborosan Dievaluasi</label>
                <input type="text" class="digi-input" value="Selisih: {{ number_format($evaluation->variance_liters, 2, ',', '.') }} L | Pemborosan: {{ number_format($evaluation->waste_liters, 2, ',', '.') }} L" readonly disabled>
            </div>
        </div>

        <div class="digi-form-grid-3" style="margin-bottom: 0;">
            <div class="digi-form-group">
                <label class="digi-form-label">Harga BBM Digunakan</label>
                <input type="text" class="digi-input" value="Rp {{ number_format($evaluation->price_used, 0, ',', '.') }} / Liter" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Estimasi Biaya Kerugian BBM</label>
                <input type="text" class="digi-input" style="font-weight: 800; color: {{ $evaluation->estimated_cost_loss > 0 ? '#b91c1c' : '#15803d' }};" value="Rp {{ number_format($evaluation->estimated_cost_loss, 0, ',', '.') }}" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Status Evaluasi Periode</label>
                <div style="padding-top: 6px;">
                    <span class="digi-badge {{ $evaluation->evaluation_status === 'normal' ? 'digi-badge-green' : ($evaluation->evaluation_status === 'needs_review' ? 'digi-badge-red' : 'digi-badge-yellow') }}">
                        {{ $evaluation->status_label }}
                    </span>
                </div>
            </div>
        </div>

        @if($evaluation->notes)
            <div style="margin-top: 14px; padding: 10px 14px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #334155;">
                <strong>Hasil Analisis Otomatis:</strong> {{ $evaluation->notes }}
            </div>
        @endif
    </div>

    <!-- SECTION 2: EVALUASI KESELURUHAN / KUMULATIF SEJAK AWAL (Item 12) -->
    <div class="digi-form-box" style="margin-top: 18px; border-color: #7dd3fc; background: #f0f9ff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <h3 class="digi-section-title" style="color: #0369a1; margin: 0;">
                2. Evaluasi Keseluruhan / Kumulatif (Sejak Pengisian Pertama di Aplikasi)
            </h3>
            <span class="digi-badge {{ $evaluation->cumulative_status === 'normal' ? 'digi-badge-green' : ($evaluation->cumulative_status === 'needs_review' ? 'digi-badge-red' : 'digi-badge-yellow') }}">
                Kumulatif: {{ $evaluation->cumulative_status_label }}
            </span>
        </div>
        <div class="digi-form-grid-4" style="margin-bottom: 0;">
            <div class="digi-form-group" style="margin-bottom: 0;">
                <label class="digi-form-label">Odometer Awal Pertama → Terakhir</label>
                <input type="text" class="digi-input" value="{{ $evaluation->cumulative_meter_start !== null ? number_format((float)$evaluation->cumulative_meter_start, 0, ',', '.') : '-' }} → {{ $evaluation->cumulative_meter_end !== null ? number_format((float)$evaluation->cumulative_meter_end, 0, ',', '.') : '-' }}" readonly disabled>
            </div>
            <div class="digi-form-group" style="margin-bottom: 0;">
                <label class="digi-form-label">Total Jarak Kumulatif</label>
                <input type="text" class="digi-input" value="{{ number_format((float)($evaluation->cumulative_distance ?? 0), 1, ',', '.') }} KM" readonly disabled>
            </div>
            <div class="digi-form-group" style="margin-bottom: 0;">
                <label class="digi-form-label">Total Liter Kumulatif</label>
                <input type="text" class="digi-input" value="{{ number_format((float)($evaluation->cumulative_liters ?? 0), 1, ',', '.') }} Liter" readonly disabled>
            </div>
            <div class="digi-form-group" style="margin-bottom: 0;">
                <label class="digi-form-label">Rasio Rata-rata Kumulatif</label>
                <input type="text" class="digi-input" style="font-weight: 800; color: #0369a1;" value="{{ number_format((float)($evaluation->cumulative_ratio ?? 0), 2, ',', '.') }} KM/L" readonly disabled>
            </div>
        </div>
    </div>

    <!-- SECTION 3: CATATAN KHUSUS ADMIN / JUSTIFIKASI MAINTENANCE (Item 13) -->
    <div class="digi-form-box" style="margin-top: 18px; border-color: #fde68a; background: #fffbeb;">
        <h3 class="digi-section-title" style="color: #92400e; margin-bottom: 8px;">3. Catatan Evaluasi Admin (Keterangan Maintenance / Kondisi Lapangan)</h3>
        <p style="font-size: 12.5px; color: #78350f; margin: 0 0 10px 0;">
            Tambahkan catatan khusus jika terdapat faktor teknis penyebab anomali konsumsi BBM (misal: ganti injektor, maintenance 2x sebulan, medan berlumpur, atau unit dinas luar).
        </p>
        <form action="{{ route('evaluation.notes', $evaluation->id) }}" method="POST" style="margin: 0;">
            @csrf
            <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div class="digi-form-group" style="flex: 1; min-width: 260px; margin-bottom: 0;">
                    <textarea name="admin_notes" rows="2" class="digi-input" placeholder="Contoh: Unit baru ganti injektor dan servis berkala pada minggu ke-2...">{{ old('admin_notes', $evaluation->admin_notes) }}</textarea>
                </div>
                <button type="submit" class="btn-digi btn-digi-blue" style="font-weight: 700;">
                    Simpan Catatan Admin
                </button>
            </div>
        </form>
    </div>

    <h3 class="digi-section-title" style="margin-top: 24px;">Transaksi Pengisian BBM Sumber & Evaluasi Per Pengisian ({{ $evaluation->transactions->count() }})</h3>
    <div class="digi-mobile-swipe-hint">
        <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>Geser tabel ke samping untuk melihat detail transaksi & rasio per pengisian</span>
    </div>

    <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
        <table class="digi-table">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>No. Fuel Request</th>
                    <th>Sumber</th>
                    <th>Waktu Pengisian</th>
                    <th>KM Saat Isi</th>
                    <th>Jarak Δ (KM)</th>
                    <th>BBM Aktual (L)</th>
                    <th>Rasio Per Isi</th>
                    <th>Status Per Isi</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluation->transactions->sortBy('fueled_at') as $trx)
                    @php
                        $m = $perFillMetrics[$trx->id] ?? null;
                        $isTxSpbu = $trx->fuelRequest?->isSpbu();
                    @endphp
                    <tr>
                        <td style="font-weight: 700;">{{ $trx->transaction_no }}</td>
                        <td>
                            @if($trx->fuelRequest)
                                <a href="{{ route('approval.show', $trx->fuelRequest->id) }}" style="color:#0284c7; font-weight:600;">{{ $trx->fuelRequest->request_no }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($isTxSpbu)
                                <span class="digi-badge digi-badge-yellow" title="{{ $trx->fuelRequest->spbu_location }}">SPBU</span>
                            @else
                                <span class="digi-badge digi-badge-blue">{{ $trx->fuelRequest?->vendor_name ?? 'Vendor' }}</span>
                            @endif
                        </td>
                        <td>{{ $trx->fueled_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($trx->meter_at_fueling, 0, ',', '.') }}</td>
                        <td>{{ $m && $m['distance'] !== null ? number_format($m['distance'], 1, ',', '.') . ' KM' : '-' }}</td>
                        <td><strong>{{ (float)$trx->actual_liters }} L</strong></td>
                        <td style="font-weight: 700;">{{ $m && $m['ratio'] !== null ? number_format($m['ratio'], 2, ',', '.') . ' Km/L' : '-' }}</td>
                        <td>
                            @if(!$m || $m['status'] === 'baseline')
                                <span class="digi-badge digi-badge-gray">Baseline Awal</span>
                            @elseif($m['status'] === 'normal')
                                <span class="digi-badge digi-badge-green">Wajar</span>
                            @elseif($m['status'] === 'needs_review')
                                <span class="digi-badge digi-badge-red">Tidak Wajar</span>
                            @else
                                <span class="digi-badge digi-badge-yellow">Invalid</span>
                            @endif
                        </td>
                        <td>{{ $trx->fuelman?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; color: #94a3b8; padding: 24px;">
                            Evaluasi ini menggunakan rekapitulasi meter awal & akhir periode {{ $evaluation->period }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

