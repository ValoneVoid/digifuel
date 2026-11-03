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
use App\Models\UnitVendor;
use App\Models\User;
use App\Services\FuelEvaluationService;
use App\Services\ExcelExportService;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    protected function getActiveUser(): User
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        return $user;
    }

    public function index(Request $request)
    {
        $user = $this->getActiveUser();
        $period = $request->get('period', '2026-05');

        $query = FuelEvaluation::with(['vehicle.unitVendor', 'calculator']);

        if ($request->filled('period')) {
            $query->where('period', $request->period);
        } else {
            $query->where('period', $period);
        }

        if ($request->filled('unit_code')) {
            $unitCode = $request->unit_code;
            $query->whereHas('vehicle', fn($q) => $q->where('unit_code', $unitCode));
        }

        if ($request->filled('unit_vendor_id')) {
            $uvId = $request->unit_vendor_id;
            $query->whereHas('vehicle', fn($q) => $q->where('unit_vendor_id', $uvId));
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
        $unitVendors = UnitVendor::where('is_active', true)->orderBy('name')->get();
        $periods = FuelEvaluation::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');
        if ($periods->isEmpty()) {
            $periods = collect(['2026-05', '2025-05', '2025-03']);
        }

        return view('evaluation.index', compact(
            'evaluations',
            'summary',
            'chartData',
            'vehicles',
            'unitVendors',
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

        // Get active fuel price for this period
        $priceRecord = FuelPrice::getCurrentPrice("{$period}-15") ?? FuelPrice::where('is_active', true)->latest('effective_start')->first();
        $pricePerLiter = $priceRecord ? (float)$priceRecord->price_per_liter : 27000.00;

        // Load reference data if present
        $refDataFile = app_path('Services/monitoring_bbm_mei_2026_data.json');
        $refData = file_exists($refDataFile) ? json_decode(file_get_contents($refDataFile), true) : [];

        $masterVehicles = [];
        $unitSheets = [];

        foreach ($data as $eval) {
            $v = $eval->vehicle;
            $unitCode = $v?->unit_code ?? "Unit-{$eval->id}";
            $masterVehicles[] = [
                'unit_code' => $unitCode,
                'model' => $v?->model ?? $v?->unit_type ?? 'Light Vehicle',
                'unit_vendor' => $v?->unitVendor?->name ?? 'Internal UT',
                'target_ratio' => (float)$eval->standard_used,
                'tank_capacity' => (float)($v?->tank_capacity ?? 80),
            ];

            // Load transactions for this vehicle
            $txRows = [];
            $transactions = $eval->transactions()->with(['fuelRequest.user', 'fuelman'])->orderBy('fueled_at', 'asc')->get();
            if ($transactions->isEmpty() && $v) {
                $transactions = FuelTransaction::whereHas('fuelRequest', function ($q) use ($v, $period) {
                    $q->where('vehicle_id', $v->id)->orWhere('unit_code', $v->unit_code);
                })->where('fueled_at', 'like', "{$period}%")->orderBy('fueled_at', 'asc')->with(['fuelRequest.user', 'fuelman'])->get();
            }

            if ($transactions->isNotEmpty()) {
                foreach ($transactions as $tx) {
                    $fr = $tx->fuelRequest;
                    $dateStr = $tx->fueled_at ? $tx->fueled_at->format('Y-m-d') : ($fr?->request_date ? $fr->request_date->format('Y-m-d') : date('Y-m-d'));
                    $odoStart = (float)($fr?->hm_km ?? 0);
                    $odoEnd = (float)$tx->meter_at_fueling;
                    if ($odoEnd < $odoStart) {
                        $odoEnd = $odoStart;
                    }
                    $dist = round($odoEnd - $odoStart, 2);
                    $liters = (float)$tx->actual_liters;
                    $actRatio = $liters > 0 ? round($dist / $liters, 2) : 0;
                    $stdRatio = (float)$eval->standard_used;
                    $fuelCost = round($liters * $pricePerLiter, 2);
                    $isWajar = $actRatio >= ($stdRatio - 0.5);
                    $waste = ($actRatio < $stdRatio && $stdRatio > 0) ? round($liters - ($dist / $stdRatio), 2) : 0;
                    $loss = round($waste * $pricePerLiter, 2);

                    $txRows[] = [
                        'date' => $dateStr,
                        'unit_code' => $unitCode,
                        'driver' => $fr?->driver_name ?? $fr?->user?->name ?? '-',
                        'odo_start' => $odoStart,
                        'odo_end' => $odoEnd,
                        'distance' => $dist,
                        'fuel_liters' => $liters,
                        'actual_ratio' => $actRatio,
                        'standard_ratio' => $stdRatio,
                        'fuel_cost' => $fuelCost,
                        'evaluation' => $isWajar ? 'Wajar' : 'Tidak Wajar',
                        'waste_liters' => $waste,
                        'loss_cost' => $loss,
                    ];
                }
            } elseif (isset($refData[$unitCode])) {
                $txRows = $refData[$unitCode];
            } else {
                $dist = (float)$eval->total_distance_or_hours;
                $liters = (float)$eval->total_actual_liters;
                $mStart = (float)$eval->meter_start;
                $mEnd = (float)$eval->meter_end;
                $stdRatio = (float)$eval->standard_used;
                $actRatio = (float)$eval->actual_ratio;
                $isWajar = $eval->evaluation_status === 'normal' || $actRatio >= ($stdRatio - 0.5);

                $txRows[] = [
                    'date' => "{$period}-01",
                    'unit_code' => $unitCode,
                    'driver' => '-',
                    'odo_start' => $mStart,
                    'odo_end' => $mEnd > $mStart ? $mEnd : ($mStart + $dist),
                    'distance' => $dist,
                    'fuel_liters' => $liters,
                    'actual_ratio' => $actRatio,
                    'standard_ratio' => $stdRatio,
                    'fuel_cost' => (float)$eval->total_actual_cost,
                    'evaluation' => $isWajar ? 'Wajar' : 'Tidak Wajar',
                    'waste_liters' => (float)$eval->waste_liters,
                    'loss_cost' => (float)$eval->estimated_cost_loss,
                ];
            }

            $unitSheets[$unitCode] = $txRows;
        }

        $multiSheetData = [
            'period' => $period,
            'price' => $pricePerLiter,
            'master_vehicles' => $masterVehicles,
            'unit_sheets' => $unitSheets,
            'evaluations' => $data,
        ];

        return $excel->downloadEvaluationDashboardXlsx($fileName, $headers, $rows, $period, $multiSheetData);
    }
}

