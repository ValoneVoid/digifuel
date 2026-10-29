<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFuelRequest;
use App\Services\FuelRequestService;
use App\Policies\FuelRequestPolicy;
use App\Models\FuelRequest;
use App\Models\FuelRequestHistory;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FuelRequestController extends Controller
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

    public function create()
    {
        $user = $this->getActiveUser();

        // Only active units can be used for new Fuel Requests
        $vehicles = Vehicle::where('is_active', true)->orderBy('unit_code')->get();
        $areas = Area::where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        $spvs = User::where('role', 'spv')->where('is_active', true)->get();
        $fuelmen = User::where('role', 'fuelman')->where('is_active', true)->get();
        $deptheads = User::whereIn('role', ['depthead', 'manager'])->where('is_active', true)->get();

        // Build area-approver mapping for dynamic dependent dropdown
        $areaApproverMap = [];
        foreach ($areas as $area) {
            $assignments = AreaApprover::with('user.vendor')
                ->where('area_id', $area->id)
                ->where('is_active', true)
                ->get();

            $areaSpvs = $assignments->where('role_type', 'spv')->pluck('user')->filter()->values();
            $areaManagers = $assignments->where('role_type', 'manager')->pluck('user')->filter()->values();
            $areaFuelmen = $assignments->where('role_type', 'fuelman')->pluck('user')->filter()->values();
            $areaVendorUsers = $assignments->where('role_type', 'vendor')->pluck('user')->filter()->values();

            $areaVendors = $areaVendorUsers->map(fn($u) => $u->vendor)->filter()->unique('id')->values();

            $areaApproverMap[$area->name] = [
                'spvs' => ($areaSpvs->isNotEmpty() ? $areaSpvs : $spvs)->map(fn($u) => [
                    'id' => $u->id,
                    'label' => 'Approval By ' . ($u->nrp ?? $u->username) . ' - ' . $u->name,
                ])->values(),
                'deptheads' => ($areaManagers->isNotEmpty() ? $areaManagers : $deptheads)->map(fn($u) => [
                    'id' => $u->id,
                    'label' => 'Approval By ' . ($u->username ?? $u->name) . ' (' . $u->name . ')',
                ])->values(),
                'fuelmen' => ($areaFuelmen->isNotEmpty() ? $areaFuelmen : $fuelmen)->map(fn($u) => [
                    'id' => $u->id,
                    'label' => 'Approval By ' . $u->name . ' (' . $u->username . ')',
                ])->values(),
                'vendors' => ($areaVendors->isNotEmpty() ? $areaVendors : $vendors)->map(fn($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                ])->values(),
            ];
        }

        // Personal summary statistics for Employee
        $myQuery = FuelRequest::where('user_id', $user->id);
        $myStats = [
            'total' => (clone $myQuery)->count(),
            'pending' => (clone $myQuery)->where('overall_status', 'menunggu')->count(),
            'approved' => (clone $myQuery)->where('overall_status', 'disetujui')->count(),
            'rejected' => (clone $myQuery)->where('overall_status', 'ditolak')->count(),
            'latest' => (clone $myQuery)->latest('request_date')->first(),
        ];

        return view('fuel.create', compact(
            'user',
            'vehicles',
            'areas',
            'vendors',
            'spvs',
            'fuelmen',
            'deptheads',
            'areaApproverMap',
            'myStats'
        ));
    }

    /**
     * AJAX / Fetch API endpoint for Dependent Dropdown by Area
     */
    public function areaApprovers(Request $request)
    {
        $areaName = $request->query('area');
        $area = Area::where('name', $areaName)->where('is_active', true)->first();

        $spvs = User::where('role', 'spv')->where('is_active', true)->get();
        $deptheads = User::whereIn('role', ['depthead', 'manager'])->where('is_active', true)->get();
        $fuelmen = User::where('role', 'fuelman')->where('is_active', true)->get();
        $vendors = Vendor::where('is_active', true)->get();

        if ($area) {
            $assignments = AreaApprover::with('user.vendor')
                ->where('area_id', $area->id)
                ->where('is_active', true)
                ->get();

            if ($assignments->where('role_type', 'spv')->isNotEmpty()) {
                $spvs = $assignments->where('role_type', 'spv')->pluck('user')->filter()->values();
            }
            if ($assignments->where('role_type', 'manager')->isNotEmpty()) {
                $deptheads = $assignments->where('role_type', 'manager')->pluck('user')->filter()->values();
            }
            if ($assignments->where('role_type', 'fuelman')->isNotEmpty()) {
                $fuelmen = $assignments->where('role_type', 'fuelman')->pluck('user')->filter()->values();
            }
            $assignedVendors = $assignments->where('role_type', 'vendor')->pluck('user.vendor')->filter()->unique('id')->values();
            if ($assignedVendors->isNotEmpty()) {
                $vendors = $assignedVendors;
            }
        }

        return response()->json([
            'area' => $areaName,
            'spvs' => $spvs->map(fn($u) => [
                'id' => $u->id,
                'label' => 'Approval By ' . ($u->nrp ?? $u->username) . ' - ' . $u->name,
            ])->values(),
            'deptheads' => $deptheads->map(fn($u) => [
                'id' => $u->id,
                'label' => 'Approval By ' . ($u->username ?? $u->name) . ' (' . $u->name . ')',
            ])->values(),
            'fuelmen' => $fuelmen->map(fn($u) => [
                'id' => $u->id,
                'label' => 'Approval By ' . $u->name . ' (' . $u->username . ')',
            ])->values(),
            'vendors' => $vendors->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
            ])->values(),
        ]);
    }

    /**
     * AJAX / Fetch API endpoint for Unit Code lookup
     */
    public function vehicleLookup(Request $request)
    {
        $unitCode = $request->query('unit_code');
        $vehicle = Vehicle::where('unit_code', $unitCode)->where('is_active', true)->first();

        if (!$vehicle) {
            return response()->json([
                'found' => false,
                'message' => 'Unit Code tidak ditemukan atau tidak aktif.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'unit_code' => $vehicle->unit_code,
            'police_no' => $vehicle->police_no,
            'unit_type' => $vehicle->unit_type ?? $vehicle->model,
            'meter_type' => strtoupper($vehicle->meter_type ?? 'km'),
            'tank_capacity' => $vehicle->tank_capacity,
        ]);
    }

    public function store(StoreFuelRequest $request, FuelRequestService $service)
    {
        $user = $this->getActiveUser();
        $isDraft = $request->input('action_type') === 'draft';

        $fuelRequest = $service->createRequest($user, $request->validated(), $isDraft);

        if ($isDraft) {
            return redirect()->route('history.index')
                ->with('success', "Fuel Request {$fuelRequest->request_no} berhasil disimpan sebagai Draft.");
        }

        return redirect()->route('history.index')
            ->with('success', "Permintaan Fuel {$fuelRequest->request_no} berhasil dikirim dan menunggu persetujuan SPV!");
    }

    public function submitDraft($id, FuelRequestService $service)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if ((int)$fuelRequest->user_id !== (int)$user->id && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses untuk mengajukan draft ini.');
        }

        $service->submitDraft($fuelRequest, $user);

        return redirect()->route('history.index')
            ->with('success', "Fuel Request {$fuelRequest->request_no} berhasil diajukan ke SPV!");
    }

    public function cancel($id)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if ((int)$fuelRequest->user_id !== (int)$user->id && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses untuk membatalkan pengajuan ini.');
        }

        if ($fuelRequest->isVendorApproved() || $fuelRequest->transactions()->exists()) {
            return redirect()->back()->with('error', 'Pengajuan yang sudah disetujui Vendor atau sudah memiliki transaksi pengisian tidak dapat dibatalkan.');
        }

        $fromStage = $fuelRequest->current_stage;
        $fuelRequest->update([
            'overall_status' => 'cancelled',
            'current_stage' => 'cancelled',
        ]);

        FuelRequestHistory::record(
            $fuelRequest->id,
            $user->id,
            'cancelled',
            $fromStage,
            'cancelled',
            "Pengajuan dibatalkan oleh {$user->name}."
        );

        return redirect()->back()->with('success', "Pengajuan {$fuelRequest->request_no} telah dibatalkan.");
    }

    public function document($id, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::with(['user', 'employee', 'vehicle', 'spv', 'fuelman', 'depthead', 'latestTransaction'])->findOrFail($id);

        if (!$policy->printDocument($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat dokumen Fuel Request milik pengguna atau vendor lain.');
        }

        return view('fuel.document', compact('fuelRequest'));
    }

    public function documentPartial($id, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::with(['user', 'employee', 'vehicle', 'spv', 'fuelman', 'depthead', 'latestTransaction'])->findOrFail($id);

        if (!$policy->printDocument($user, $fuelRequest)) {
            return response('<div style="padding:20px; color:#ef4444; text-align:center;">Akses Ditolak: Anda tidak diizinkan mengakses dokumen ini.</div>', 403);
        }

        return view('fuel.document_partial', compact('fuelRequest'));
    }
}
