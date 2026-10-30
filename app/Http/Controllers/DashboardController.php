<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
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

    /**
     * Display the main Dashboard with aggregated statistics, visual stepper, and recent activities.
     */
    public function index(Request $request, DashboardService $dashboardService): View|JsonResponse
    {
        $user = $this->getActiveUser();
        $filters = $request->only(['area', 'month', 'date_from', 'date_to', 'status', 'stage']);

        $stats = $dashboardService->getStats($user, $filters);
        $chartData = $dashboardService->getChartData($user, $filters);
        $recentRequests = $dashboardService->getRecentRequests($user, $filters, 8);
        $latestMyRequest = $dashboardService->getLatestMyRequest($user);
        $recentActivities = $dashboardService->getRecentActivities($user, 6);
        $areas = Area::where('is_active', true)->orderBy('name')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'jabatan' => $user->jabatan,
                ],
                'stats' => $stats,
                'chartData' => $chartData,
                'recentRequests' => $recentRequests,
                'latestMyRequest' => $latestMyRequest,
                'recentActivities' => $recentActivities,
            ]);
        }

        return view('dashboard.index', compact(
            'user',
            'stats',
            'chartData',
            'recentRequests',
            'latestMyRequest',
            'recentActivities',
            'areas'
        ));
    }

    /**
     * Dedicated API endpoint returning structured datasets for Chart.js.
     */
    public function charts(Request $request, DashboardService $dashboardService): JsonResponse
    {
        $user = $this->getActiveUser();
        $filters = $request->only(['area', 'month', 'date_from', 'date_to', 'status', 'stage']);

        $chartData = $dashboardService->getChartData($user, $filters);

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }
}
