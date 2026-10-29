<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelEvaluation;
use App\Models\FuelRequest;
use App\Models\FuelRequestHistory;
use App\Models\FuelTransaction;
use App\Models\FuelPrice;
use App\Models\FuelStandard;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\Vendor;
use App\Models\User;
use App\Services\FuelEvaluationService;
use App\Services\ExcelExportService;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    protected function getActiveUser(): User
    {
        if (!Auth::check()) {
            $default = User::where('role', 'admin')->first() ?? User::first();
            if ($default) {
                Auth::login($default);
            }
        }
        return Auth::user();
    }

    public function index(Request $request)
    {
        $user = $this->getActiveUser();
        $period = $request->get('period', '2026-05');

        $query = FuelEvaluation::with(['vehicle', 'calculator']);

        if ($request->filled('period')) {
            $query->where('period', $request->period);
        } else {
            $query->where('period', $period);
        }

        if ($request->filled('unit_code')) {
            $unitCode = $request->unit_code;
            $query->whereHas('vehicle', fn($q) => $q->where('unit_code', $unitCode));
        }

        if ($request->filled('status')) {
            $query->where('evaluation_status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('vehicle', function ($q) use ($s) {
                $q->where('unit_code', 'like', "%{$s}%")
                  ->orWhere('police_no', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%")
                  ->orWhere('unit_type', 'like', "%{$s}%");
            });
        }

        $evaluations = (clone $query)->orderBy('estimated_cost_loss', 'desc')->paginate(15)->withQueryString();
        $allForSummary = (clone $query)->get();

        // Valid evaluations only for distance, waste, and cost loss aggregation (never include invalid/incomplete data!)
        $validEvaluations = $allForSummary->whereIn('evaluation_status', ['normal', 'needs_review']);

        $summary = [
            'total_requests' => FuelRequest::count(),
            'pending_requests' => FuelRequest::where('overall_status', 'menunggu')->count(),
            'approved_requests' => FuelRequest::where('overall_status', 'disetujui')->count(),
            'rejected_requests' => FuelRequest::where('overall_status', 'ditolak')->count(),
            'completed_fueling' => FuelRequest::where('current_stage', 'completed')->count(),
            'total_requested_liters' => (float)FuelRequest::sum('total_liters'),
            'total_actual_liters' => (float)$validEvaluations->sum('total_actual_liters'),
            'total_distance' => (float)$validEvaluations->sum('total_distance_or_hours'),
            'avg_ratio' => $validEvaluations->sum('total_actual_liters') > 0
                ? round($validEvaluations->sum('total_distance_or_hours') / $validEvaluations->sum('total_actual_liters'), 2)
                : 0,
            'total_actual_cost' => (float)$validEvaluations->sum('total_actual_cost'),
            'total_waste_liters' => (float)$validEvaluations->sum('waste_liters'),
            'total_cost_loss' => (float)$validEvaluations->sum('estimated_cost_loss'),
            'needs_review_count' => $allForSummary->where('evaluation_status', 'needs_review')->count(),
            'normal_count' => $allForSummary->where('evaluation_status', 'normal')->count(),
        ];

        // Chart Data from real database records
        $topWastefulUnits = $validEvaluations
            ->where('waste_liters', '>', 0)
            ->sortByDesc('estimated_cost_loss')
            ->take(8)
            ->values();

        $chartData = [
            'unitLabels' => $allForSummary->map(fn($e) => $e->vehicle?->unit_code ?? '-')->values(),
            'actualLiters' => $allForSummary->map(fn($e) => (float)$e->total_actual_liters)->values(),
            'standardLiters' => $allForSummary->map(fn($e) => (float)$e->total_standard_liters)->values(),
            'wasteLabels' => $topWastefulUnits->map(fn($e) => $e->vehicle?->unit_code ?? '-')->values(),
            'wasteCosts' => $topWastefulUnits->map(fn($e) => (float)$e->estimated_cost_loss)->values(),
            'statusCounts' => [
                $allForSummary->where('evaluation_status', 'normal')->count(),
                $allForSummary->where('evaluation_status', 'needs_review')->count(),
                $allForSummary->whereIn('evaluation_status', ['incomplete', 'invalid_data'])->count(),
            ],
        ];

        $vehicles = Vehicle::where('is_active', true)->orderBy('unit_code')->get();
        $periods = FuelEvaluation::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');
        if ($periods->isEmpty()) {
            $periods = collect(['2026-05', '2025-05', '2025-03']);
        }

        return view('evaluation.index', compact(
            'evaluations',
            'summary',
            'chartData',
            'vehicles',
            'periods',
            'period',
            'user'
        ));
    }

    public function calculate(Request $request, FuelEvaluationService $service)
    {
        $user = $this->getActiveUser();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya Administrator yang diizinkan menjalankan kalkulasi evaluasi BBM.');
        }

        $validated = $request->validate([
            'period' => 'required|string',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'meter_start' => 'nullable|numeric|min:0',
            'meter_end' => 'nullable|numeric|min:0',
            'total_actual_liters' => 'nullable|numeric|min:0',
        ]);

        if (!empty($validated['vehicle_id'])) {
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            $eval = $service->evaluateVehicle(
                $vehicle,
                $validated['period'],
                $user->id,
                isset($validated['meter_start']) && $validated['meter_start'] !== '' ? (float)$validated['meter_start'] : null,
                isset($validated['meter_end']) && $validated['meter_end'] !== '' ? (float)$validated['meter_end'] : null,
                isset($validated['total_actual_liters']) && $validated['total_actual_liters'] !== '' ? (float)$validated['total_actual_liters'] : null
            );

            return redirect()->route('evaluation.index', ['period' => $validated['period']])
                ->with('success', "Evaluasi unit {$vehicle->unit_code} periode {$validated['period']} berhasil dihitung! Status: {$eval->status_label}.");
        }

        $count = $service->evaluatePeriod($validated['period'], $user->id);

        return redirect()->route('evaluation.index', ['period' => $validated['period']])
            ->with('success', "Kalkulasi ulang evaluasi konsumsi BBM untuk periode {$validated['period']} selesai ({$count} unit dievaluasi).");
    }

    public function show($id)
    {
        $user = $this->getActiveUser();
        $evaluation = FuelEvaluation::with(['vehicle.fuelStandards', 'calculator', 'transactions.fuelRequest', 'transactions.fuelman'])->findOrFail($id);

        return view('evaluation.show', compact('evaluation', 'user'));
    }

    public function export(Request $request, ExcelExportService $excel)
    {
        $user = $this->getActiveUser();
        $period = $request->get('period', '2026-05');

        $query = FuelEvaluation::with(['vehicle'])->where('period', $period);
        if ($request->filled('status')) {
            $query->where('evaluation_status', $request->status);
        }
        if ($request->filled('unit_code')) {
            $query->whereHas('vehicle', fn($q) => $q->where('unit_code', $request->unit_code));
        }

        $data = $query->orderBy('estimated_cost_loss', 'desc')->get();

        FuelRequestHistory::record(
            null,
            $user->id,
            'report_exported',
            null,
            'completed',
            "Mengekspor Laporan Evaluasi Konsumsi BBM periode {$period} ke Excel (.xlsx) sejumlah {$data->count()} unit."
        );

        $headers = [
            'No',
            'Periode',
            'ID Kendaraan / Unit Code',
            'Nomor Polisi',
            'Tipe Unit',
            'HM/KM Awal',
            'HM/KM Akhir',
            'Total Jarak (Km) / Jam (HM)',
            'Total Pengisian Fuel Aktual (L)',
            'BBM Standar (L)',
            'Rasio Aktual',
            'Rasio Standar',
            'Satuan Standar',
            'Harga BBM / Liter (IDR)',
            'Total Biaya BBM (IDR)',
            'Selisih Konsumsi (L)',
            'Varian Pemborosan (L)',
            'Estimasi Biaya Kerugian (IDR)',
            'Status Evaluasi',
        ];

        $rows = [];
        foreach ($data as $index => $eval) {
            $r = $index + 2; // Baris Excel (Row 1 adalah Header)
            $hasMeters = $eval->meter_start !== null && $eval->meter_end !== null;

            $rows[] = [
                $index + 1,
                $eval->period,
                $eval->vehicle?->unit_code ?? '-',
                $eval->vehicle?->police_no ?? '-',
                $eval->vehicle?->model ?? $eval->vehicle?->unit_type ?? '-',
                $eval->meter_start !== null ? (float)$eval->meter_start : 0,
                $eval->meter_end !== null ? (float)$eval->meter_end : 0,
                // Col H: Total Jarak (Km) / Jam (HM) = HM/KM Akhir - HM/KM Awal (atau nilai tersimpan jika meter tidak diinput)
                $hasMeters
                    ? ['formula' => "MAX(0,G{$r}-F{$r})", 'value' => (float)$eval->total_distance_or_hours]
                    : (float)$eval->total_distance_or_hours,
                // Col I: Total Pengisian Fuel Aktual (L)
                (float)$eval->total_actual_liters,
                // Col J: BBM Standar (L) = Jika Liter/Jam maka H*L, jika Km/Liter maka H/L
                [
                    'formula' => "IF(L{$r}>0,IF(M{$r}=\"Liter/Jam\",ROUND(H{$r}*L{$r},2),ROUND(H{$r}/L{$r},2)),0)",
                    'value' => (float)$eval->total_standard_liters,
                ],
                // Col K: Rasio Aktual = Jika Liter/Jam maka I/H, jika Km/Liter maka H/I
                [
                    'formula' => "IF(AND(H{$r}>0,I{$r}>0),IF(M{$r}=\"Liter/Jam\",ROUND(I{$r}/H{$r},2),ROUND(H{$r}/I{$r},2)),0)",
                    'value' => (float)$eval->actual_ratio,
                ],
                // Col L: Rasio Standar
                (float)$eval->standard_used,
                // Col M: Satuan Standar
                $eval->standard_type === 'liter_per_hour' ? 'Liter/Jam' : 'Km/Liter',
                // Col N: Harga BBM / Liter (IDR)
                (float)$eval->price_used,
                // Col O: Total Biaya BBM (IDR) = BBM Aktual * Harga BBM/Liter
                [
                    'formula' => "ROUND(I{$r}*N{$r},0)",
                    'value' => (float)$eval->total_actual_cost,
                ],
                // Col P: Selisih Konsumsi (L) = BBM Aktual - BBM Standar
                [
                    'formula' => "ROUND(I{$r}-J{$r},2)",
                    'value' => (float)$eval->variance_liters,
                ],
                // Col Q: Varian Pemborosan (L) = MAX(0, BBM Aktual - BBM Standar)
                [
                    'formula' => "MAX(0,ROUND(I{$r}-J{$r},2))",
                    'value' => (float)$eval->waste_liters,
                ],
                // Col R: Estimasi Biaya Kerugian (IDR) = Varian Pemborosan * Harga BBM/Liter
                [
                    'formula' => "ROUND(Q{$r}*N{$r},0)",
                    'value' => (float)$eval->estimated_cost_loss,
                ],
                // Col S: Status Evaluasi = Wajar (Normal) vs Tidak Wajar (Needs Review)
                [
                    'formula' => "IF(OR(H{$r}<=0,I{$r}<=0),\"Incomplete / Invalid\",IF(Q{$r}>0,\"Tidak Wajar (Needs Review)\",\"Wajar (Normal)\"))",
                    'value' => $eval->status_label,
                ],
            ];
        }

        // Summary Footer Row menggunakan rumus SUM & IF langsung di Excel
        $valid = $data->whereIn('evaluation_status', ['normal', 'needs_review']);
        $lastDataRow = count($data) + 1;
        $sumRow = $lastDataRow + 1;

        if ($lastDataRow >= 2) {
            $rows[] = [
                '',
                'TOTAL / RATA-RATA',
                '',
                '',
                '',
                '',
                '',
                ['formula' => "SUM(H2:H{$lastDataRow})", 'value' => (float)$valid->sum('total_distance_or_hours')],
                ['formula' => "SUM(I2:I{$lastDataRow})", 'value' => (float)$valid->sum('total_actual_liters')],
                ['formula' => "SUM(J2:J{$lastDataRow})", 'value' => (float)$valid->sum('total_standard_liters')],
                ['formula' => "IF(I{$sumRow}>0,ROUND(H{$sumRow}/I{$sumRow},2),0)", 'value' => $valid->sum('total_actual_liters') > 0 ? round($valid->sum('total_distance_or_hours') / $valid->sum('total_actual_liters'), 2) : 0],
                '',
                '',
                ['formula' => "AVERAGE(N2:N{$lastDataRow})", 'value' => (float)($valid->avg('price_used') ?? 27000)],
                ['formula' => "SUM(O2:O{$lastDataRow})", 'value' => (float)$valid->sum('total_actual_cost')],
                ['formula' => "SUM(P2:P{$lastDataRow})", 'value' => (float)$valid->sum('variance_liters')],
                ['formula' => "SUM(Q2:Q{$lastDataRow})", 'value' => (float)$valid->sum('waste_liters')],
                ['formula' => "SUM(R2:R{$lastDataRow})", 'value' => (float)$valid->sum('estimated_cost_loss')],
                '',
            ];
        }

        $fileName = "Dashboard_Monitoring_Evaluasi_BBM_{$period}_" . date('Ymd_His') . '.xlsx';
        return $excel->downloadXlsx($fileName, $headers, $rows, "Evaluasi BBM {$period}");
    }
}

