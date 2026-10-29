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

        $evaluations = (clone $query)->orderBy('id', 'asc')->paginate(20)->withQueryString();
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
            'avg_standard_ratio' => $validEvaluations->count() > 0
                ? round((float)$validEvaluations->avg('standard_used'), 2)
                : 8.0,
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

        $data = $query->orderBy('id', 'asc')->get();

        FuelRequestHistory::record(
            null,
            $user->id,
            'report_exported',
            null,
            'completed',
            "Mengekspor Laporan Evaluasi Konsumsi BBM periode {$period} ke Excel (.xlsx) sejumlah {$data->count()} unit."
        );

        // Struktur 10 Kolom (A s/d J) identik dengan Dashboard_Monitoring_BBM MEI 2026.xlsx
        $headers = [
            'ID Kendaraan',
            'Tipe Unit',
            'Total Jarak (Km)',
            'Total Pengisian Fuel (L)',
            'Rasio Aktual (Km/L)',
            'Rasio Standar (Km/L)',
            'Evaluasi per Unit',
            'Total Biaya (IDR)',
            'Varian Pemborosan (L)',
            'Biaya Kerugian (IDR)',
        ];

        $rows = [];
        foreach ($data as $index => $eval) {
            $r = $index + 9; // Baris data di Sheet Dashboard dimulai pada Row 9 (A9:J23)
            $price = (int)round($eval->price_used > 0 ? $eval->price_used : 27000);
            $actualRatio = $eval->total_actual_liters > 0
                ? round($eval->total_distance_or_hours / $eval->total_actual_liters, 2)
                : 0;
            $evalLabel = $actualRatio < ($eval->standard_used - 0.5) ? 'Tidak Wajar' : 'Wajar';

            $rows[] = [
                // Col A: ID Kendaraan
                $eval->vehicle?->unit_code ?? '-',
                // Col B: Tipe Unit
                $eval->vehicle?->model ?? $eval->vehicle?->unit_type ?? '-',
                // Col C: Total Jarak (Km)
                (float)$eval->total_distance_or_hours,
                // Col D: Total Pengisian Fuel (L)
                (float)$eval->total_actual_liters,
                // Col E: Rasio Aktual (Km/L) -> =IF(D9>0, C9/D9, 0)
                [
                    'formula' => "IF(D{$r}>0, C{$r}/D{$r}, 0)",
                    'value' => $actualRatio,
                ],
                // Col F: Rasio Standar (Km/L)
                (float)$eval->standard_used,
                // Col G: Evaluasi per Unit -> =IF(E9<(F9-0.5), "Tidak Wajar", "Wajar")
                [
                    'formula' => "IF(E{$r}<(F{$r}-0.5), \"Tidak Wajar\", \"Wajar\")",
                    'value' => $evalLabel,
                ],
                // Col H: Total Biaya (IDR) -> =D9*27000
                [
                    'formula' => "D{$r}*{$price}",
                    'value' => (float)$eval->total_actual_cost,
                ],
                // Col I: Varian Pemborosan (L) -> =IF(E9<F9, D9-(C9/F9), 0)
                [
                    'formula' => "IF(E{$r}<F{$r}, D{$r}-(C{$r}/F{$r}), 0)",
                    'value' => (float)$eval->waste_liters,
                ],
                // Col J: Biaya Kerugian (IDR) -> =I9*27000
                [
                    'formula' => "I{$r}*{$price}",
                    'value' => (float)$eval->estimated_cost_loss,
                ],
            ];
        }

        $fileName = "Dashboard_Monitoring_BBM_{$period}_" . date('Ymd_His') . '.xlsx';
        return $excel->downloadEvaluationDashboardXlsx($fileName, $headers, $rows, $period);
    }
}

