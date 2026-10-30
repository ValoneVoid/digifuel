<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\FuelEvaluation;
use App\Models\FuelRequest;
use App\Models\FuelRequestHistory;
use App\Models\FuelTransaction;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Build the base Eloquent query for FuelRequest scoped strictly according to the active User role.
     */
    public function getBaseScopedQuery(User $user, array $filters = []): Builder
    {
        $query = FuelRequest::query();

        // 1. Role-based scoping
        if ($user->isAdmin()) {
            // Admin sees full Site Satui data without restrictions
        } elseif ($user->isDriver()) {
            // Driver / Employee: strictly see only their own requests
            $query->where('user_id', $user->id);
        } elseif ($user->isSpv()) {
            // SPV: assigned as SPV on the request OR assigned to the operational area via area_approvers
            $spvAreaIds = AreaApprover::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('role_type', 'spv')
                ->pluck('area_id')
                ->all();

            $query->where(function (Builder $q) use ($user, $spvAreaIds) {
                $q->where('spv_id', $user->id);
                if (!empty($spvAreaIds)) {
                    $q->orWhereIn('area_id', $spvAreaIds);
                }
            });
        } elseif ($user->isManager()) {
            // Dept Head / Manager: assigned on request OR assigned to the area via area_approvers
            $mgrAreaIds = AreaApprover::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('role_type', 'manager')
                ->pluck('area_id')
                ->all();

            $query->where(function (Builder $q) use ($user, $mgrAreaIds) {
                $q->where('depthead_id', $user->id)
                  ->orWhere('manager_id', $user->id);
                if (!empty($mgrAreaIds)) {
                    $q->orWhereIn('area_id', $mgrAreaIds);
                }
            });
        } elseif ($user->isVendor()) {
            // Vendor: strictly scoped to requests for their assigned vendor company
            $query->where(function (Builder $q) use ($user) {
                if ($user->vendor_id) {
                    $q->where('vendor_id', $user->vendor_id);
                }
                if ($user->vendor) {
                    $q->orWhere('vendor_name', $user->vendor->name);
                }
                $q->orWhere('vendor_user_id', $user->id);
            });
        } elseif ($user->isFuelman()) {
            // Fuel Man: assigned on request, assigned via area_approvers, or requests in fueling/completed stages
            $fmAreaIds = AreaApprover::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('role_type', 'fuelman')
                ->pluck('area_id')
                ->all();

            $query->where(function (Builder $q) use ($user, $fmAreaIds) {
                $q->where('fuelman_id', $user->id);
                if (!empty($fmAreaIds)) {
                    $q->orWhereIn('area_id', $fmAreaIds);
                }
                $q->orWhereIn('current_stage', ['approved', 'fueling', 'completed']);
            });
        } else {
            // Fallback default deny
            $query->where('user_id', $user->id);
        }

        // 2. Optional query filters (Area, Month, Date Range)
        if (!empty($filters['area'])) {
            $query->where('area', $filters['area']);
        }

        if (!empty($filters['month'])) {
            $query->where('request_date', 'like', $filters['month'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('request_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('request_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('overall_status', $filters['status']);
        }

        if (!empty($filters['stage'])) {
            $query->where('current_stage', $filters['stage']);
        }

        return $query;
    }

    /**
     * Calculate and return all dashboard statistics via optimized SQL aggregations.
     */
    public function getStats(User $user, array $filters = []): array
    {
        $scopedQuery = $this->getBaseScopedQuery($user, $filters);

        // 1. Single SQL query to aggregate request counts and volume
        $reqAgg = (clone $scopedQuery)->selectRaw("
            COUNT(*) as total_requests,
            SUM(CASE WHEN overall_status = 'menunggu' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN overall_status = 'disetujui' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN overall_status = 'ditolak' THEN 1 ELSE 0 END) as rejected_requests,
            SUM(CASE WHEN current_stage = 'draft' THEN 1 ELSE 0 END) as draft_requests,
            SUM(CASE WHEN status_fuelman = 'disetujui' AND current_stage != 'completed' THEN 1 ELSE 0 END) as ready_fueling,
            SUM(CASE WHEN current_stage = 'completed' THEN 1 ELSE 0 END) as completed_fueling,
            COALESCE(SUM(total_liters), 0) as total_requested_liters,
            COALESCE(SUM(actual_liters), 0) as total_actual_liters
        ")->first();

        $totalRequests = (int) ($reqAgg->total_requests ?? 0);
        $pendingRequests = (int) ($reqAgg->pending_requests ?? 0);
        $approvedRequests = (int) ($reqAgg->approved_requests ?? 0);
        $rejectedRequests = (int) ($reqAgg->rejected_requests ?? 0);
        $draftRequests = (int) ($reqAgg->draft_requests ?? 0);
        $readyFueling = (int) ($reqAgg->ready_fueling ?? 0);
        $completedFueling = (int) ($reqAgg->completed_fueling ?? 0);
        $totalRequestedLiters = (float) ($reqAgg->total_requested_liters ?? 0.0);
        $totalActualLiters = (float) ($reqAgg->total_actual_liters ?? 0.0);

        // 2. Aggregate actual fueling transactions from `fuel_transactions`
        $txQuery = FuelTransaction::query();
        if (!$user->isAdmin()) {
            if ($user->isDriver()) {
                $txQuery->whereHas('fuelRequest', fn(Builder $q) => $q->where('user_id', $user->id));
            } elseif ($user->isFuelman()) {
                $txQuery->where(function (Builder $q) use ($user) {
                    $q->where('fuelman_id', $user->id)
                      ->orWhereHas('fuelRequest', fn(Builder $sq) => $sq->where('fuelman_id', $user->id));
                });
            } elseif ($user->isVendor()) {
                $txQuery->whereHas('fuelRequest', function (Builder $q) use ($user) {
                    if ($user->vendor_id) {
                        $q->where('vendor_id', $user->vendor_id);
                    }
                    if ($user->vendor) {
                        $q->orWhere('vendor_name', $user->vendor->name);
                    }
                });
            } else {
                $txQuery->whereIn('fuel_request_id', (clone $scopedQuery)->select('id'));
            }
        }

        $txAgg = $txQuery->selectRaw("
            COUNT(*) as total_transactions,
            COALESCE(SUM(actual_liters), 0) as sum_actual_liters,
            COALESCE(SUM(variance_liters), 0) as sum_variance_liters
        ")->first();

        $totalTransactions = (int) ($txAgg->total_transactions ?? 0);
        $txActualLiters = (float) ($txAgg->sum_actual_liters ?? 0.0);
        $txVarianceLiters = (float) ($txAgg->sum_variance_liters ?? 0.0);

        // If request table has 0 actual_liters but transactions exist, use transaction sum
        if ($totalActualLiters <= 0 && $txActualLiters > 0) {
            $totalActualLiters = $txActualLiters;
        }

        $varianceLiters = round($totalActualLiters - $totalRequestedLiters, 2);
        $realizationPercentage = $totalRequestedLiters > 0
            ? round(($totalActualLiters / $totalRequestedLiters) * 100, 1)
            : 0.0;

        // 3. Role-specific action queue count (`waiting_my_action`)
        $waitingMyAction = 0;
        if ($user->isSpv() && !$user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)->where('status_spv', 'menunggu')->count();
        } elseif ($user->isManager() && !$user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)
                ->where('status_spv', 'disetujui')
                ->where('status_depthead', 'menunggu')
                ->count();
        } elseif ($user->isVendor() && !$user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)
                ->where('status_spv', 'disetujui')
                ->where('status_depthead', 'disetujui')
                ->whereIn('current_stage', ['pending_vendor'])
                ->count();
        } elseif ($user->isFuelman() && !$user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)
                ->whereIn('current_stage', ['approved', 'fueling'])
                ->count();
        } elseif ($user->isDriver() && !$user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)
                ->where('current_stage', 'draft')
                ->count();
        } elseif ($user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)
                ->where('overall_status', 'menunggu')
                ->count();
        }

        // 4. Fuel Evaluation Statistics (Waste Liters & Cost Loss)
        $evalCostLoss = 0.0;
        $evalWasteLiters = 0.0;
        $evalAbnormalUnits = 0;
        $evalTotal = 0;
        $evalNormal = 0;
        $evalNeedsReview = 0;

        if ($user->isAdmin() || $user->isManager()) {
            $evalAgg = FuelEvaluation::selectRaw("
                COUNT(*) as eval_total,
                SUM(CASE WHEN evaluation_status = 'normal' THEN 1 ELSE 0 END) as eval_normal,
                SUM(CASE WHEN evaluation_status = 'needs_review' THEN 1 ELSE 0 END) as eval_needs_review,
                COALESCE(SUM(CASE WHEN evaluation_status = 'needs_review' THEN waste_liters ELSE 0 END), 0) as waste_liters,
                COALESCE(SUM(CASE WHEN evaluation_status = 'needs_review' THEN estimated_cost_loss ELSE 0 END), 0) as cost_loss
            ")->first();

            $evalTotal = (int) ($evalAgg->eval_total ?? 0);
            $evalNormal = (int) ($evalAgg->eval_normal ?? 0);
            $evalNeedsReview = (int) ($evalAgg->eval_needs_review ?? 0);
            $evalAbnormalUnits = $evalNeedsReview;
            $evalWasteLiters = (float) ($evalAgg->waste_liters ?? 0.0);
            $evalCostLoss = (float) ($evalAgg->cost_loss ?? 0.0);
        }

        // 5. Active Master Data counts
        $activeUnits = Vehicle::where('is_active', true)->count();
        $activeAreas = Area::where('is_active', true)->count();
        $activeVendors = Vendor::where('is_active', true)->count();

        return [
            // Kontrak variabel utama untuk frontend blade
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedRequests,
            'rejected_requests' => $rejectedRequests,
            'draft_requests' => $draftRequests,
            'ready_fueling' => $readyFueling,
            'completed_fueling' => $completedFueling,
            'waiting_my_action' => $waitingMyAction,
            'total_requested_liters' => $totalRequestedLiters,
            'total_actual_liters' => $totalActualLiters,
            'eval_cost_loss' => $evalCostLoss,
            'eval_waste_liters' => $evalWasteLiters,
            'eval_abnormal_units' => $evalAbnormalUnits,
            'active_units' => $activeUnits,
            'active_areas' => $activeAreas,
            'active_vendors' => $activeVendors,

            // Metrik tambahan konsumsi & evaluasi
            'total_transactions' => $totalTransactions,
            'total_variance_liters' => $varianceLiters,
            'realization_percentage' => $realizationPercentage,
            'eval_total' => $evalTotal,
            'eval_normal' => $evalNormal,
            'eval_needs_review' => $evalNeedsReview,
        ];
    }

    /**
     * Get recent fuel requests within the user's role scope with eager loaded relationships.
     */
    public function getRecentRequests(User $user, array $filters = [], int $limit = 8): Collection
    {
        return $this->getBaseScopedQuery($user, $filters)
            ->with(['spv', 'depthead', 'fuelman', 'latestTransaction'])
            ->orderBy('request_date', 'desc')
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get the single latest request for the active user's visual stepper.
     */
    public function getLatestMyRequest(User $user): ?FuelRequest
    {
        if ($user->isDriver()) {
            return FuelRequest::where('user_id', $user->id)
                ->with(['spv', 'depthead', 'fuelman', 'latestTransaction'])
                ->orderBy('request_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        }

        return $this->getBaseScopedQuery($user)
            ->with(['spv', 'depthead', 'fuelman', 'latestTransaction'])
            ->orderBy('request_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Get recent activities / audit trail scoped appropriately for the user role.
     */
    public function getRecentActivities(User $user, int $limit = 6): Collection
    {
        $query = FuelRequestHistory::with(['user', 'fuelRequest']);

        if ($user->isDriver() && !$user->isAdmin()) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('fuelRequest', fn(Builder $sq) => $sq->where('user_id', $user->id));
            });
        } elseif (!$user->isAdmin()) {
            // Approver / Vendor / Fuel Man: only histories of requests within their scope
            $scopedRequestIds = $this->getBaseScopedQuery($user)->select('id');
            $query->whereIn('fuel_request_id', $scopedRequestIds);
        }

        return $query->latest('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Prepare structured datasets for Chart.js (Monthly trends, status distribution, area breakdown).
     */
    public function getChartData(User $user, array $filters = []): array
    {
        $scopedQuery = $this->getBaseScopedQuery($user, $filters);

        // 1. Tren Bulanan (Last 6 months)
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dateExpr = $isSqlite ? "strftime('%Y-%m', request_date)" : "DATE_FORMAT(request_date, '%Y-%m')";

        $monthlyRaw = (clone $scopedQuery)
            ->where('request_date', '>=', $sixMonthsAgo)
            ->selectRaw("
                {$dateExpr} as period_key,
                COUNT(*) as count_requests,
                COALESCE(SUM(total_liters), 0) as sum_requested_liters,
                COALESCE(SUM(actual_liters), 0) as sum_actual_liters
            ")
            ->groupBy('period_key')
            ->orderBy('period_key', 'asc')
            ->get()
            ->keyBy('period_key');

        $monthLabels = [];
        $requestedLitersSeries = [];
        $actualLitersSeries = [];
        $requestCountSeries = [];

        for ($i = 5; $i >= 0; $i--) {
            $dt = Carbon::now()->subMonths($i);
            $ymKey = $dt->format('Y-m');
            $monthLabels[] = $dt->translatedFormat('M Y');

            $row = $monthlyRaw->get($ymKey);
            $requestedLitersSeries[] = (float) ($row->sum_requested_liters ?? 0);
            $actualLitersSeries[] = (float) ($row->sum_actual_liters ?? 0);
            $requestCountSeries[] = (int) ($row->count_requests ?? 0);
        }

        // 2. Status Distribution
        $statusCounts = (clone $scopedQuery)->selectRaw("
            SUM(CASE WHEN overall_status = 'menunggu' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN overall_status = 'disetujui' AND current_stage != 'completed' THEN 1 ELSE 0 END) as approved_ready,
            SUM(CASE WHEN current_stage = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN overall_status = 'ditolak' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN current_stage = 'draft' THEN 1 ELSE 0 END) as draft
        ")->first();

        $statusLabels = ['Menunggu', 'Disetujui', 'Selesai Realisasi', 'Ditolak', 'Draft'];
        $statusSeries = [
            (int) ($statusCounts->pending ?? 0),
            (int) ($statusCounts->approved_ready ?? 0),
            (int) ($statusCounts->completed ?? 0),
            (int) ($statusCounts->rejected ?? 0),
            (int) ($statusCounts->draft ?? 0),
        ];
        $statusColors = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#94a3b8'];

        // 3. Area Distribution
        $areaBreakdown = (clone $scopedQuery)
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->selectRaw('area, COUNT(*) as count_requests, COALESCE(SUM(total_liters), 0) as sum_liters')
            ->groupBy('area')
            ->orderBy('sum_liters', 'desc')
            ->take(6)
            ->get();

        $areaLabels = $areaBreakdown->pluck('area')->all();
        $areaVolumeSeries = $areaBreakdown->pluck('sum_liters')->map(fn($v) => (float)$v)->all();
        $areaCountSeries = $areaBreakdown->pluck('count_requests')->map(fn($v) => (int)$v)->all();

        return [
            'monthly_trend' => [
                'labels' => $monthLabels,
                'requested_liters' => $requestedLitersSeries,
                'actual_liters' => $actualLitersSeries,
                'request_counts' => $requestCountSeries,
            ],
            'status_distribution' => [
                'labels' => $statusLabels,
                'series' => $statusSeries,
                'colors' => $statusColors,
            ],
            'area_distribution' => [
                'labels' => $areaLabels,
                'volumes' => $areaVolumeSeries,
                'counts' => $areaCountSeries,
            ],
            'summary' => [
                'total_requested' => array_sum($requestedLitersSeries),
                'total_actual' => array_sum($actualLitersSeries),
                'total_requests' => array_sum($requestCountSeries),
            ],
        ];
    }
}

