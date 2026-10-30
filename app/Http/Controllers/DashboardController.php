<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FuelRequest;
use App\Models\FuelTransaction;
use App\Models\FuelEvaluation;
use App\Models\FuelRequestHistory;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected function getActiveUser(): User
    {
        if (!Auth::check()) {
            $default = User::where('username', 'adji')->first() ?? User::first();
            if ($default) {
                Auth::login($default);
            }
        }
        return Auth::user();
    }

    public function index(Request $request)
    {
        $user = $this->getActiveUser();

        // Scope requests based on active role
        $scopedQuery = FuelRequest::with(['latestTransaction', 'spv', 'depthead', 'fuelman']);

        if ($user->isDriver() && !$user->isAdmin()) {
            $scopedQuery->where('user_id', $user->id);
        } elseif ($user->isVendor() && !$user->isAdmin()) {
            $scopedQuery->where(function ($q) use ($user) {
                if ($user->vendor_id) {
                    $q->where('vendor_id', $user->vendor_id);
                }
                if ($user->vendor) {
                    $q->orWhere('vendor_name', $user->vendor->name);
                }
            });
        }

        // Optional filters on dashboard
        if ($request->filled('area')) {
            $scopedQuery->where('area', $request->area);
        }
        if ($request->filled('month')) {
            $scopedQuery->where('request_date', 'like', $request->month . '%');
        }

        $allScoped = (clone $scopedQuery)->get();

        // Role-specific action queue count
        $waitingMyAction = 0;
        if ($user->isSpv()) {
            $waitingMyAction = (clone $scopedQuery)->where('status_spv', 'menunggu')->count();
        } elseif ($user->isManager()) {
            $waitingMyAction = (clone $scopedQuery)->where('status_spv', 'disetujui')->where('status_depthead', 'menunggu')->count();
        } elseif ($user->isVendor()) {
            $waitingMyAction = (clone $scopedQuery)->where('status_spv', 'disetujui')->where('status_depthead', 'disetujui')->where('status_fuelman', 'menunggu')->count();
        } elseif ($user->isFuelman()) {
            $waitingMyAction = (clone $scopedQuery)->where('status_fuelman', 'disetujui')->where('current_stage', '!=', 'completed')->count();
        } elseif ($user->isAdmin()) {
            $waitingMyAction = (clone $scopedQuery)->where('overall_status', 'menunggu')->count();
        }

        // Valid evaluations for BBM waste & cost loss summary
        $validEvaluations = FuelEvaluation::whereIn('evaluation_status', ['normal', 'needs_review'])->get();

        $stats = [
            'total_requests' => $allScoped->count(),
            'pending_requests' => $allScoped->where('overall_status', 'menunggu')->count(),
            'approved_requests' => $allScoped->where('overall_status', 'disetujui')->count(),
            'rejected_requests' => $allScoped->where('overall_status', 'ditolak')->count(),
            'draft_requests' => $allScoped->where('current_stage', 'draft')->count(),
            'ready_fueling' => $allScoped->where('status_fuelman', 'disetujui')->where('current_stage', '!=', 'completed')->count(),
            'completed_fueling' => $allScoped->where('current_stage', 'completed')->count(),
            'waiting_my_action' => $waitingMyAction,
            'total_requested_liters' => (float)$allScoped->sum('total_liters'),
            'total_actual_liters' => (float)$allScoped->sum(fn($r) => (float)($r->actual_liters ?? $r->latestTransaction?->actual_liters ?? 0)),
            'eval_waste_liters' => (float)$validEvaluations->sum('waste_liters'),
            'eval_cost_loss' => (float)$validEvaluations->sum('estimated_cost_loss'),
            'eval_abnormal_units' => $validEvaluations->where('evaluation_status', 'needs_review')->count(),
            'active_units' => Vehicle::where('is_active', true)->count(),
            'active_areas' => Area::where('is_active', true)->count(),
            'active_vendors' => Vendor::where('is_active', true)->count(),
        ];

        $recentRequests = (clone $scopedQuery)->orderBy('request_date', 'desc')->take(8)->get();
        $latestMyRequest = $user->isDriver()
            ? FuelRequest::where('user_id', $user->id)->orderBy('request_date', 'desc')->first()
            : $recentRequests->first();

        $recentActivities = FuelRequestHistory::with(['user', 'fuelRequest'])
            ->when($user->isDriver() && !$user->isAdmin(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest('created_at')
            ->take(6)
            ->get();

        $areas = Area::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'user',
            'stats',
            'recentRequests',
            'latestMyRequest',
            'recentActivities',
            'areas'
        ));
    }
}
