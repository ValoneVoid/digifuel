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

        // Get all available periods from evaluations
        $periods = FuelEvaluation::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');
        $defaultPeriod = $periods->first() ?? now()->format('Y-m');

        // Mode: 'monthly' (default), 'range', or 'yearly'
        $mode = $request->get('mode', 'monthly');
        if (!in_array($mode, ['monthly', 'range', 'yearly'])) {
            $mode = 'monthly';
        }

        $period = $request->get('period', $defaultPeriod);
        $periodStart = $request->get('period_start', substr($defaultPeriod, 0, 4) . '-01');
        $periodEnd = $request->get('period_end', $defaultPeriod);
        $year = $request->get('year', substr($defaultPeriod, 0, 4));

        if ($periodStart > $periodEnd) {
            $tmp = $periodStart;
            $periodStart = $periodEnd;
            $periodEnd = $tmp;
        }

        // Available years for dropdown
        $availableYears = FuelEvaluation::selectRaw('DISTINCT SUBSTRING(period, 1, 4) as yr')
            ->orderBy('yr', 'desc')
            ->pluck('yr');
        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->format('Y')]);
        }
        if (!$availableYears->contains($year)) {
            $availableYears = $availableYears->prepend($year);
        }

        // View type when multi-month: 'consolidated' (1 row per unit) vs 'monthly' (raw monthly rows)
        $viewType = $request->get('view', ($mode === 'monthly' ? 'monthly' : 'consolidated'));

        $query = FuelEvaluation::with(['vehicle.unitVendor', 'calculator']);

        if ($mode === 'monthly') {
            $query->where('period', $period);
            $parsed = \Carbon\Carbon::createFromFormat('Y-m', $period);
            $periodLabel = $parsed ? $parsed->translatedFormat('F Y') : $period;
        } elseif ($mode === 'range') {
            $query->where('period', '>=', $periodStart)->where('period', '<=', $periodEnd);
            $pStart = \Carbon\Carbon::createFromFormat('Y-m', $periodStart);
            $pEnd = \Carbon\Carbon::createFromFormat('Y-m', $periodEnd);
            $periodLabel = ($pStart && $pEnd)
                ? 'Rentang ' . $pStart->translatedFormat('M Y') . ' s/d ' . $pEnd->translatedFormat('M Y')
                : "Rentang {$periodStart} s/d {$periodEnd}";
        } else { // yearly
            $query->where('period', 'like', "{$year}-%");
            $periodLabel = "Tahun {$year}";
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
                  ->orWhere('division', 'like', "%{$s}%")
                  ->orWhere('unit_type', 'like', "%{$s}%");
            });
        }

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

        // Chart Data grouped by vehicle to avoid duplicates across multi-month views
        $vehicleChartGroups = $allForSummary->groupBy('vehicle_id')->map(function ($group) {
            $first = $group->first();
            $actL = (float)$group->sum('total_actual_liters');
            $stdL = (float)$group->sum('total_standard_liters');
            $wasteL = (float)$group->sum('waste_liters');
            $costLoss = (float)$group->sum('estimated_cost_loss');

            $rawCode = $first->vehicle?->unit_code ?? '-';
            // Extract clean short label (e.g. "UT 208 DA 8191" -> "UT-208" or "UT - 206 ( DA 8457 ZQ )" -> "UT-206")
            $shortLabel = trim(explode('(', $rawCode)[0]);
            if (preg_match('/^(UT\s*[-\s]?\s*\d+[A-Z]?)/i', $shortLabel, $m)) {
                $cleanUnit = preg_replace('/\s+/', '', $m[1]);
                $shortLabel = preg_replace('/^(UT)(\d+)/', '$1-$2', $cleanUnit);
            }

            return [
                'unit_code' => $rawCode,
                'short_label' => $shortLabel ?: $rawCode,
                'actual_liters' => $actL,
                'standard_liters' => $stdL,
                'waste_liters' => $wasteL,
                'cost_loss' => $costLoss,
            ];
        })->values();

        // Limit Chart 1 to units with actual liters (top consuming units, max 10 for best mobile/desktop readability)
        $chartUnits = $vehicleChartGroups->where('actual_liters', '>', 0)->sortByDesc('actual_liters')->take(10)->values();
        if ($chartUnits->isEmpty()) {
            $chartUnits = $vehicleChartGroups->take(10)->values();
        }

        $topWastefulUnits = $vehicleChartGroups
            ->where('waste_liters', '>', 0)
            ->sortByDesc('cost_loss')
            ->take(8)
            ->values();

        $chartData = [
            'unitLabels' => $chartUnits->pluck('short_label')->values(),
            'fullLabels' => $chartUnits->pluck('unit_code')->values(),
            'actualLiters' => $chartUnits->pluck('actual_liters')->values(),
            'standardLiters' => $chartUnits->pluck('standard_liters')->values(),
            'wasteLabels' => $topWastefulUnits->pluck('short_label')->values(),
            'wasteFullLabels' => $topWastefulUnits->pluck('unit_code')->values(),
            'wasteCosts' => $topWastefulUnits->pluck('cost_loss')->values(),
            'statusCounts' => [
                $allForSummary->where('evaluation_status', 'normal')->count(),
                $allForSummary->where('evaluation_status', 'needs_review')->count(),
                $allForSummary->whereIn('evaluation_status', ['incomplete', 'invalid_data'])->count(),
            ],
        ];

        // Handle evaluations listing: consolidated per unit vs monthly rows
        $isConsolidated = ($mode !== 'monthly' && $viewType === 'consolidated');

        if ($isConsolidated) {
            $groupedByVehicle = $allForSummary->groupBy('vehicle_id');
            $consolidatedList = $groupedByVehicle->map(function ($records) {
                $first = $records->first();
                $vehicle = $first->vehicle;
                $dist = (float)$records->sum('total_distance_or_hours');
                $actualLiters = (float)$records->sum('total_actual_liters');
                $stdLiters = (float)$records->sum('total_standard_liters');
                $actualCost = (float)$records->sum('total_actual_cost');
                $wasteLiters = (float)$records->sum('waste_liters');
                $costLoss = (float)$records->sum('estimated_cost_loss');

                $actRatio = $actualLiters > 0 ? round($dist / $actualLiters, 2) : 0;
                $stdRatio = (float)$first->standard_used;
                $tolerance = (float)$first->tolerance_used;

                $latestRec = $records->sortByDesc('period')->first();
                $stdMax = ($latestRec->standard_max_used !== null && (float)$latestRec->standard_max_used > $stdRatio)
                    ? (float)$latestRec->standard_max_used
                    : null;

                if ($actualLiters <= 0 || $dist <= 0) {
                    $status = 'incomplete';
                } elseif ($first->standard_type === 'liter_per_hour') {
                    $allowed = $stdLiters * (1 + ($tolerance / 100));
                    $status = $actualLiters > $allowed ? 'needs_review' : 'normal';
                } else {
                    if ($actRatio < ($stdRatio - 0.5)) {
                        $status = 'needs_review';
                    } elseif ($stdMax !== null && $actRatio > $stdMax) {
                        $status = 'needs_review';
                    } else {
                        $status = 'normal';
                    }
                }
                return (object)[
                    'id' => $first->id,
                    'latest_id' => $latestRec->id,
                    'vehicle' => $vehicle,
                    'period' => $records->pluck('period')->sort()->values()->implode(', '),
                    'period_count' => $records->count(),
                    'total_distance_or_hours' => $dist,
                    'total_actual_liters' => $actualLiters,
                    'total_standard_liters' => $stdLiters,
                    'actual_ratio' => $actRatio,
                    'standard_used' => $stdRatio,
                    'standard_max_used' => $latestRec->standard_max_used,
                    'evaluation_status' => $status,
                    'cumulative_ratio' => $latestRec->cumulative_ratio,
                    'cumulative_status' => $latestRec->cumulative_status,
                    'admin_notes' => $latestRec->admin_notes,
                    'price_used' => (float)($latestRec->price_used ?: $first->price_used),
                    'total_actual_cost' => $actualCost,
                    'waste_liters' => $wasteLiters,
                    'estimated_cost_loss' => $costLoss,
                ];
            })->values();

            if ($request->filled('status')) {
                $consolidatedList = $consolidatedList->where('evaluation_status', $request->status)->values();
            }

            $page = (int)$request->get('page', 1);
            $perPage = 20;
            $evaluations = new \Illuminate\Pagination\LengthAwarePaginator(
                $consolidatedList->forPage($page, $perPage)->values(),
                $consolidatedList->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $evaluations = (clone $query)->orderBy('period', 'desc')->orderBy('id', 'asc')->paginate(20)->withQueryString();
        }

        $vehicles = Vehicle::where('is_active', true)->orderBy('unit_code')->get();
        $unitVendors = UnitVendor::where('is_active', true)->orderBy('name')->get();

        if ($periods->isEmpty()) {
            $periods = collect([$period]);
        } elseif (!$periods->contains($period)) {
            $periods = $periods->prepend($period);
        }

        return view('evaluation.index', compact(
            'evaluations',
            'summary',
            'chartData',
            'vehicles',
            'unitVendors',
            'periods',
            'period',
            'mode',
            'periodStart',
            'periodEnd',
            'year',
            'periodLabel',
            'viewType',
            'isConsolidated',
            'availableYears',
            'user'
        ));
    }

    public function calculate(Request $request, FuelEvaluationService $service)
    {
        $user = $this->getActiveUser();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya Administrator yang diizinkan menjalankan kalkulasi evaluasi BBM.');
        }

        $calcMode = $request->get('calc_mode', 'monthly');

        // Multi-Month Range Calculation
        if ($calcMode === 'range' && $request->filled('calc_start') && $request->filled('calc_end')) {
            $startStr = $request->calc_start;
            $endStr = $request->calc_end;
            if ($startStr > $endStr) {
                $tmp = $startStr;
                $startStr = $endStr;
                $endStr = $tmp;
            }

            $start = \Carbon\Carbon::createFromFormat('Y-m', $startStr)->startOfMonth();
            $end = \Carbon\Carbon::createFromFormat('Y-m', $endStr)->startOfMonth();

            $totalCount = 0;
            $curr = $start->copy();
            while ($curr <= $end) {
                $p = $curr->format('Y-m');
                $totalCount += $service->evaluatePeriod($p, $user->id);
                $curr->addMonth();
            }

            return redirect()->route('evaluation.index', [
                'mode' => 'range',
                'period_start' => $startStr,
                'period_end' => $endStr
            ])->with('success', "Kalkulasi evaluasi konsumsi BBM rentang {$startStr} s/d {$endStr} selesai ({$totalCount} unit-bulan dievaluasi).");
        }

        // Full Year Calculation (12 months)
        if ($calcMode === 'yearly' && $request->filled('calc_year')) {
            $y = $request->calc_year;
            $totalCount = 0;
            for ($m = 1; $m <= 12; $m++) {
                $p = sprintf('%s-%02d', $y, $m);
                $totalCount += $service->evaluatePeriod($p, $user->id);
            }

            return redirect()->route('evaluation.index', [
                'mode' => 'yearly',
                'year' => $y
            ])->with('success', "Kalkulasi evaluasi konsumsi BBM tahun {$y} (12 bulan) selesai ({$totalCount} unit-bulan dievaluasi).");
        }

        // Default: Single Month Calculation
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

    public function show($id, FuelEvaluationService $service)
    {
        $user = $this->getActiveUser();
        $evaluation = FuelEvaluation::with(['vehicle.fuelStandards', 'calculator', 'transactions.fuelRequest', 'transactions.fuelman'])->findOrFail($id);

        $perFillMetrics = $service->computePerFillMetrics(
            $evaluation->transactions,
            (float) $evaluation->standard_used,
            $evaluation->standard_max_used !== null ? (float) $evaluation->standard_max_used : null
        );

        return view('evaluation.show', compact('evaluation', 'user', 'perFillMetrics'));
    }

    /**
     * Save or update custom Admin notes on a FuelEvaluation record (Item 13)
     */
    public function updateNotes(Request $request, $id)
    {
        $user = $this->getActiveUser();
        if (!$user->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat mengubah catatan evaluasi.');
        }

        $evaluation = FuelEvaluation::with('vehicle')->findOrFail($id);
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $evaluation->update([
            'admin_notes' => trim((string)($validated['admin_notes'] ?? '')) ?: null,
        ]);

        FuelRequestHistory::record(
            null,
            $user->id,
            'evaluation_notes_updated',
            null,
            $evaluation->evaluation_status,
            "Admin ({$user->name}) memperbarui catatan evaluasi untuk {$evaluation->vehicle?->unit_code} periode {$evaluation->period}."
        );

        return redirect()->route('evaluation.show', $evaluation->id)
            ->with('success', 'Catatan Admin pada evaluasi unit berhasil disimpan.');
    }

    public function export(Request $request, ExcelExportService $excel)
    {
        $user = $this->getActiveUser();

        // Get available periods
        $periods = FuelEvaluation::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');
        $defaultPeriod = $periods->first() ?? now()->format('Y-m');

        $mode = $request->get('mode', 'monthly');
        if (!in_array($mode, ['monthly', 'range', 'yearly'])) {
            $mode = 'monthly';
        }

        $period = $request->get('period', $defaultPeriod);
        $periodStart = $request->get('period_start', substr($defaultPeriod, 0, 4) . '-01');
        $periodEnd = $request->get('period_end', $defaultPeriod);
        $year = $request->get('year', substr($defaultPeriod, 0, 4));

        if ($periodStart > $periodEnd) {
            $tmp = $periodStart;
            $periodStart = $periodEnd;
            $periodEnd = $tmp;
        }

        $query = FuelEvaluation::with(['vehicle.unitVendor']);

        if ($mode === 'monthly') {
            $query->where('period', $period);
            $parsed = \Carbon\Carbon::createFromFormat('Y-m', $period);
            $periodLabel = $parsed ? $parsed->translatedFormat('F Y') : $period;
            $fileName = "Dashboard_Monitoring_BBM_{$period}_" . date('Ymd_His') . '.xlsx';
        } elseif ($mode === 'range') {
            $query->where('period', '>=', $periodStart)->where('period', '<=', $periodEnd);
            $pStart = \Carbon\Carbon::createFromFormat('Y-m', $periodStart);
            $pEnd = \Carbon\Carbon::createFromFormat('Y-m', $periodEnd);
            $periodLabel = ($pStart && $pEnd)
                ? 'Rentang ' . $pStart->translatedFormat('M Y') . ' s/d ' . $pEnd->translatedFormat('M Y')
                : "Rentang {$periodStart} s/d {$periodEnd}";
            $fileName = "Dashboard_Monitoring_BBM_Rentang_{$periodStart}_sd_{$periodEnd}_" . date('Ymd_His') . '.xlsx';
        } else { // yearly
            $query->where('period', 'like', "{$year}-%");
            $periodLabel = "Tahun {$year}";
            $fileName = "Dashboard_Monitoring_BBM_Tahun_{$year}_" . date('Ymd_His') . '.xlsx';
        }

        if ($request->filled('status')) {
            $query->where('evaluation_status', $request->status);
        }
        if ($request->filled('unit_code')) {
            $query->whereHas('vehicle', fn($q) => $q->where('unit_code', $request->unit_code));
        }
        if ($request->filled('unit_vendor_id')) {
            $query->whereHas('vehicle', fn($q) => $q->where('unit_vendor_id', $request->unit_vendor_id));
        }

        $data = $query->orderBy('id', 'asc')->get();

        FuelRequestHistory::record(
            null,
            $user->id,
            'report_exported',
            null,
            'completed',
            "Mengekspor Laporan Evaluasi Konsumsi BBM ({$periodLabel}) ke Excel (.xlsx) sejumlah {$data->count()} record."
        );

        // Struktur 16 Kolom (A s/d P) — Seragam dengan tabel Evaluasi BBM di Website
        $headers = [
            'ID Kendaraan',
            'Tipe Unit',
            'Vendor Unit',
            'Divisi / Beban Account',
            'No. Polisi',
            'Fuel Type',
            'Total Jarak (Km)',
            'Total Pengisian Fuel (L)',
            'Harga / Liter (IDR)',
            'Total Harga (IDR)',
            'Rasio Aktual (Km/L)',
            'Standar (Km/L)',
            'Evaluasi Periode',
            'Kumulatif (Sejak Awal)',
            'Varian Pemborosan (L)',
            'Biaya Kerugian (IDR)',
        ];

        // Determine active fuel price
        $priceEffectiveDate = $mode === 'monthly' ? "{$period}-15" : ($mode === 'range' ? "{$periodEnd}-15" : "{$year}-06-15");
        $priceRecord = FuelPrice::getCurrentPrice($priceEffectiveDate) ?? FuelPrice::where('is_active', true)->latest('effective_start')->first();
        $pricePerLiter = $priceRecord ? (float)$priceRecord->price_per_liter : 27000.00;

        $rows = [];
        $masterVehicles = [];
        $unitSheets = [];

        if ($mode === 'monthly') {
            foreach ($data as $index => $eval) {
                $r = $index + 9;
                $price = (int)round($eval->price_used > 0 ? $eval->price_used : $pricePerLiter);
                $actualRatio = $eval->total_actual_liters > 0
                    ? round($eval->total_distance_or_hours / $eval->total_actual_liters, 2)
                    : 0;
                $evalLabel = $actualRatio < ($eval->standard_used - 0.5) ? 'Tidak Wajar' : 'Wajar';

                $rows[] = [
                    $eval->vehicle?->unit_code ?? '-',
                    $eval->vehicle?->model ?? $eval->vehicle?->unit_type ?? '-',
                    $eval->vehicle?->division ?? '-',
                    (float)$eval->total_distance_or_hours,
                    (float)$eval->total_actual_liters,
                    [
                        'formula' => "IF(E{$r}>0, D{$r}/E{$r}, 0)",
                        'value' => $actualRatio,
                    ],
                    (float)$eval->standard_used,
                    [
                        'formula' => "IF(F{$r}<(G{$r}-0.5), \"Tidak Wajar\", \"Wajar\")",
                        'value' => $evalLabel,
                    ],
                    [
                        'formula' => "E{$r}*{$price}",
                        'value' => (float)$eval->total_actual_cost,
                    ],
                    [
                        'formula' => "IF(F{$r}<G{$r}, E{$r}-(D{$r}/G{$r}), 0)",
                        'value' => (float)$eval->waste_liters,
                    ],
                    [
                        'formula' => "J{$r}*{$price}",
                        'value' => (float)$eval->estimated_cost_loss,
                    ],
                ];

                $v = $eval->vehicle;
                $unitCode = $v?->unit_code ?? "Unit-{$eval->id}";
                $cumLabel = (!empty($eval->cumulative_ratio) && (float)$eval->cumulative_ratio > 0)
                    ? number_format((float)$eval->cumulative_ratio, 1, ',', '.') . ' Km/L (' . ($eval->cumulative_status === 'normal' ? 'Wajar' : ($eval->cumulative_status === 'needs_review' ? 'Tidak Wajar' : ucfirst($eval->cumulative_status ?? '-'))) . ')'
                    : '-';
                $masterVehicles[] = [
                    'unit_code' => $unitCode,
                    'model' => $v?->model ?? $v?->unit_type ?? 'Light Vehicle',
                    'division' => $v?->division ?? '-',
                    'police_no' => $v?->police_no ?? '-',
                    'fuel_type' => $v?->fuel_type ?? 'Solar Industri',
                    'unit_vendor' => $v?->unitVendor?->name ?? 'Internal UT',
                    'target_ratio' => (float)$eval->standard_used,
                    'standard_max' => ($eval->standard_max_used !== null && (float)$eval->standard_max_used > (float)$eval->standard_used) ? (float)$eval->standard_max_used : null,
                    'tank_capacity' => (float)($v?->tank_capacity ?? 80),
                    'price_per_liter' => (float)$price,
                    'cumulative_label' => $cumLabel,
                    'cumulative_meter_start' => $eval->cumulative_meter_start !== null ? (float)$eval->cumulative_meter_start : null,
                    'cumulative_meter_end' => $eval->cumulative_meter_end !== null ? (float)$eval->cumulative_meter_end : null,
                    'cumulative_liters' => (float)($eval->cumulative_liters ?? 0),
                    'cumulative_ratio' => (float)($eval->cumulative_ratio ?? 0),
                    'cumulative_status' => $eval->cumulative_status ?? 'incomplete',
                ];

                // Load transactions
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
                        $txUnitPrice = ($fr && $fr->isSpbu() && (float)$fr->spbu_price_per_liter > 0)
                            ? (float)$fr->spbu_price_per_liter
                            : $pricePerLiter;
                        $fuelCost = round($liters * $txUnitPrice, 2);
                        $isWajar = $actRatio >= ($stdRatio - 0.5);
                        $waste = ($actRatio < $stdRatio && $stdRatio > 0) ? round($liters - ($dist / $stdRatio), 2) : 0;
                        $loss = round($waste * $txUnitPrice, 2);

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
        } else {
            // Group by vehicle for consolidated Range / Yearly export
            $groupedByVehicle = $data->groupBy('vehicle_id');
            $rowCounter = 0;

            foreach ($groupedByVehicle as $vehicleId => $evalGroup) {
                $r = $rowCounter + 9;
                $rowCounter++;

                $firstEval = $evalGroup->first();
                $v = $firstEval->vehicle;
                $unitCode = $v?->unit_code ?? "Unit-{$vehicleId}";

                $totDist = (float)$evalGroup->sum('total_distance_or_hours');
                $totLiters = (float)$evalGroup->sum('total_actual_liters');
                $totCost = (float)$evalGroup->sum('total_actual_cost');
                $totWaste = (float)$evalGroup->sum('waste_liters');
                $totLoss = (float)$evalGroup->sum('estimated_cost_loss');

                $actualRatio = $totLiters > 0 ? round($totDist / $totLiters, 2) : 0;
                $stdRatio = (float)$firstEval->standard_used;
                $evalLabel = $actualRatio < ($stdRatio - 0.5) ? 'Tidak Wajar' : 'Wajar';

                $rows[] = [
                    $unitCode,
                    $v?->model ?? $v?->unit_type ?? '-',
                    $v?->division ?? '-',
                    $totDist,
                    $totLiters,
                    [
                        'formula' => "IF(E{$r}>0, D{$r}/E{$r}, 0)",
                        'value' => $actualRatio,
                    ],
                    $stdRatio,
                    [
                        'formula' => "IF(F{$r}<(G{$r}-0.5), \"Tidak Wajar\", \"Wajar\")",
                        'value' => $evalLabel,
                    ],
                    [
                        'formula' => "E{$r}*{$pricePerLiter}",
                        'value' => $totCost,
                    ],
                    [
                        'formula' => "IF(F{$r}<G{$r}, E{$r}-(D{$r}/G{$r}), 0)",
                        'value' => $totWaste,
                    ],
                    [
                        'formula' => "J{$r}*{$pricePerLiter}",
                        'value' => $totLoss,
                    ],
                ];

                $latestEvalInGroup = $evalGroup->sortByDesc('period')->first();
                $groupPrice = (float)($latestEvalInGroup->price_used > 0 ? $latestEvalInGroup->price_used : $pricePerLiter);
                $cumLabel = (!empty($latestEvalInGroup->cumulative_ratio) && (float)$latestEvalInGroup->cumulative_ratio > 0)
                    ? number_format((float)$latestEvalInGroup->cumulative_ratio, 1, ',', '.') . ' Km/L (' . ($latestEvalInGroup->cumulative_status === 'normal' ? 'Wajar' : ($latestEvalInGroup->cumulative_status === 'needs_review' ? 'Tidak Wajar' : ucfirst($latestEvalInGroup->cumulative_status ?? '-'))) . ')'
                    : '-';
                $masterVehicles[] = [
                    'unit_code' => $unitCode,
                    'model' => $v?->model ?? $v?->unit_type ?? 'Light Vehicle',
                    'division' => $v?->division ?? '-',
                    'police_no' => $v?->police_no ?? '-',
                    'fuel_type' => $v?->fuel_type ?? 'Solar Industri',
                    'unit_vendor' => $v?->unitVendor?->name ?? 'Internal UT',
                    'target_ratio' => $stdRatio,
                    'standard_max' => ($latestEvalInGroup->standard_max_used !== null && (float)$latestEvalInGroup->standard_max_used > $stdRatio) ? (float)$latestEvalInGroup->standard_max_used : null,
                    'tank_capacity' => (float)($v?->tank_capacity ?? 80),
                    'price_per_liter' => $groupPrice,
                    'cumulative_label' => $cumLabel,
                    'cumulative_meter_start' => $latestEvalInGroup->cumulative_meter_start !== null ? (float)$latestEvalInGroup->cumulative_meter_start : null,
                    'cumulative_meter_end' => $latestEvalInGroup->cumulative_meter_end !== null ? (float)$latestEvalInGroup->cumulative_meter_end : null,
                    'cumulative_liters' => (float)($latestEvalInGroup->cumulative_liters ?? 0),
                    'cumulative_ratio' => (float)($latestEvalInGroup->cumulative_ratio ?? 0),
                    'cumulative_status' => $latestEvalInGroup->cumulative_status ?? 'incomplete',
                ];

                // Load all transactions across the range / year
                $txQuery = FuelTransaction::whereHas('fuelRequest', function ($q) use ($v) {
                    $q->where('vehicle_id', $v->id)->orWhere('unit_code', $v->unit_code);
                });

                if ($mode === 'range') {
                    $txQuery->whereBetween('fueled_at', ["{$periodStart}-01 00:00:00", "{$periodEnd}-31 23:59:59"]);
                } else { // yearly
                    $txQuery->where('fueled_at', 'like', "{$year}%");
                }

                $transactions = $txQuery->orderBy('fueled_at', 'asc')->with(['fuelRequest.user', 'fuelman'])->get();
                $txRows = [];

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
                } else {
                    // Fallback to monthly evaluation records summary row
                    foreach ($evalGroup as $e) {
                        $txRows[] = [
                            'date' => "{$e->period}-01",
                            'unit_code' => $unitCode,
                            'driver' => '-',
                            'odo_start' => (float)$e->meter_start,
                            'odo_end' => (float)$e->meter_end,
                            'distance' => (float)$e->total_distance_or_hours,
                            'fuel_liters' => (float)$e->total_actual_liters,
                            'actual_ratio' => (float)$e->actual_ratio,
                            'standard_ratio' => (float)$e->standard_used,
                            'fuel_cost' => (float)$e->total_actual_cost,
                            'evaluation' => $e->evaluation_status === 'normal' ? 'Wajar' : 'Tidak Wajar',
                            'waste_liters' => (float)$e->waste_liters,
                            'loss_cost' => (float)$e->estimated_cost_loss,
                        ];
                    }
                }

                $unitSheets[$unitCode] = $txRows;
            }
        }

        $multiSheetData = [
            'period' => $periodLabel,
            'period_label' => $periodLabel,
            'price' => $pricePerLiter,
            'master_vehicles' => $masterVehicles,
            'unit_sheets' => $unitSheets,
            'evaluations' => $data,
        ];

        return $excel->downloadEvaluationDashboardXlsx($fileName, $headers, $rows, $periodLabel, $multiSheetData);
    }
}

