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
        <h3 class="digi-section-title">Rincian Rumus & Hasil Perhitungan</h3>
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
                <label class="digi-form-label">Standar Konsumsi Berlaku</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->standard_used, 2, ',', '.') }} KM / Liter (Toleransi: {{ (float)$evaluation->tolerance_used }}%)" readonly disabled>
            </div>
        </div>

        <div class="digi-form-grid-3">
            <div class="digi-form-group">
                <label class="digi-form-label">BBM Standar Seharusnya</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->total_standard_liters, 2, ',', '.') }} Liter" readonly disabled>
            </div>
            <div class="digi-form-group">
                <label class="digi-form-label">Total BBM Aktual Tercatat</label>
                <input type="text" class="digi-input" value="{{ number_format($evaluation->total_actual_liters, 2, ',', '.') }} Liter (Rasio Aktual: {{ number_format($evaluation->actual_ratio, 2, ',', '.') }})" readonly disabled>
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
                <label class="digi-form-label">Status Evaluasi</label>
                <div style="padding-top: 6px;">
                    <span class="digi-badge {{ $evaluation->evaluation_status === 'normal' ? 'digi-badge-green' : ($evaluation->evaluation_status === 'needs_review' ? 'digi-badge-red' : 'digi-badge-yellow') }}">
                        {{ $evaluation->status_label }}
                    </span>
                </div>
            </div>
        </div>

        @if($evaluation->notes)
            <div style="margin-top: 14px; padding: 10px 14px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #334155;">
                <strong>Catatan Evaluasi:</strong> {{ $evaluation->notes }}
            </div>
        @endif
    </div>

    <h3 class="digi-section-title" style="margin-top: 24px;">Transaksi Pengisian BBM Sumber ({{ $evaluation->transactions->count() }})</h3>
    <div class="digi-mobile-swipe-hint">
        <span>👉 Geser tabel ke samping untuk melihat detail transaksi</span>
    </div>

    <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
        <table class="digi-table">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>No. Fuel Request</th>
                    <th>Waktu Pengisian Aktual</th>
                    <th>KM Saat Isi</th>
                    <th>BBM Diminta (L)</th>
                    <th>BBM Aktual (L)</th>
                    <th>Petugas Fuel Man</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluation->transactions as $trx)
                    <tr>
                        <td style="font-weight: 700;">{{ $trx->transaction_no }}</td>
                        <td>
                            @if($trx->fuelRequest)
                                <a href="{{ route('approval.show', $trx->fuelRequest->id) }}" style="color:#0284c7; font-weight:600;">{{ $trx->fuelRequest->request_no }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $trx->fueled_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($trx->meter_at_fueling, 0, ',', '.') }}</td>
                        <td>{{ (float)$trx->requested_liters }} L</td>
                        <td><strong>{{ (float)$trx->actual_liters }} L</strong></td>
                        <td>{{ $trx->fuelman?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 24px;">
                            Evaluasi ini menggunakan rekapitulasi meter awal & akhir periode {{ $evaluation->period }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

