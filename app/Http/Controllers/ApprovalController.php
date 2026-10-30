<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelRequest;
use App\Models\Area;
use App\Models\User;
use App\Services\FuelRequestService;
use App\Policies\FuelRequestPolicy;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
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
        $query = FuelRequest::with(['latestTransaction']);

        // Strict Vendor isolation: Vendor accounts can only access requests assigned to their Vendor company
        if ($user->isVendor() && !$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                if ($user->vendor_id) {
                    $q->where('vendor_id', $user->vendor_id);
                }
                if ($user->vendor) {
                    $q->orWhere('vendor_name', $user->vendor->name);
                }
            });
        }

        // Filter Area
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('overall_status', $request->status);
        }

        // Filter Stage
        if ($request->filled('stage')) {
            $query->where('current_stage', $request->stage);
        }

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%{$s}%")
                  ->orWhere('driver_name', 'like', "%{$s}%")
                  ->orWhere('unit_code', 'like', "%{$s}%")
                  ->orWhere('police_no', 'like', "%{$s}%");
            });
        }

        // Summary metrics for Approver / Vendor / Fuel Man
        $baseStatsQuery = clone $query;
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'pending_spv' => (clone $baseStatsQuery)->where('status_spv', 'menunggu')->count(),
            'pending_depthead' => (clone $baseStatsQuery)->where('status_spv', 'disetujui')->where('status_depthead', 'menunggu')->count(),
            'pending_vendor' => (clone $baseStatsQuery)->where('status_spv', 'disetujui')->where('status_depthead', 'disetujui')->whereIn('current_stage', ['pending_vendor'])->count(),
            'ready_fueling' => (clone $baseStatsQuery)->whereIn('current_stage', ['approved', 'fueling'])->count(),
            'completed' => (clone $baseStatsQuery)->where('current_stage', 'completed')->count(),
            'approved_total' => (clone $baseStatsQuery)->where('overall_status', 'disetujui')->count(),
            'rejected_total' => (clone $baseStatsQuery)->where('overall_status', 'ditolak')->count(),
        ];

        // Sort pending items relevant to current role first, then by request_date desc
        $requests = $query->orderByRaw("CASE 
            WHEN status_spv = 'menunggu' THEN 1 
            WHEN status_depthead = 'menunggu' THEN 2 
            WHEN status_vendor = 'menunggu' OR status_fuelman = 'menunggu' THEN 3 
            WHEN current_stage IN ('approved', 'fueling') THEN 4
            ELSE 5 END")
            ->orderBy('request_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        $areas = Area::where('is_active', true)->orderBy('name')->get();

        return view('approval.index', compact('requests', 'user', 'stats', 'areas'));
    }

    public function show($id, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::with([
            'user',
            'employee',
            'vehicle',
            'spv',
            'fuelman',
            'depthead',
            'vendorModel',
            'approvals.approver',
            'transactions.fuelman',
            'histories.user',
        ])->findOrFail($id);

        if (!$policy->view($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses untuk membuka pengajuan ini.');
        }

        return view('approval.show', compact('fuelRequest', 'user'));
    }

    public function approve(Request $request, $id, FuelRequestService $service, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->approve($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menyetujui pengajuan ini.');
        }

        $adjustedLiters = null;
        if ($request->filled('total_liters') && ($user->isSpv() || $user->isAdmin()) && $fuelRequest->status_spv === 'menunggu') {
            $request->validate([
                'total_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            ], [
                'total_liters.required' => 'Jumlah liter harus diisi.',
                'total_liters.numeric' => 'Jumlah liter harus berupa angka numerik.',
                'total_liters.gt' => 'Jumlah liter harus lebih besar dari 0.',
                'total_liters.max' => 'Jumlah liter melebihi batas sistem (5.000 Liter).',
            ]);
            $adjustedLiters = (float) $request->input('total_liters');
        }

        $result = $service->approve($fuelRequest, $user, $request->input('note'), $adjustedLiters);

        if (!$result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('approval.show', $fuelRequest->id)->with('success', $result['message']);
    }

    public function updateLiters(Request $request, $id, FuelRequestService $service, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->updateLiters($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengubah jumlah liter pada pengajuan ini.');
        }

        $validated = $request->validate([
            'total_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'total_liters.required' => 'Jumlah liter wajib diisi.',
            'total_liters.numeric' => 'Jumlah liter harus berupa angka numerik.',
            'total_liters.gt' => 'Jumlah liter harus lebih besar dari 0.',
            'total_liters.max' => 'Jumlah liter melebihi batas maksimum sistem (5.000 Liter).',
        ]);

        $result = $service->updateLiters($fuelRequest, $user, (float) $validated['total_liters'], $validated['reason'] ?? null);

        if (!$result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('approval.show', $fuelRequest->id)->with('success', $result['message']);
    }

    public function reject(Request $request, $id, FuelRequestService $service, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->approve($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menolak pengajuan ini.');
        }

        $note = trim($request->input('note', '')) ?: 'Permintaan ditolak oleh Approver.';

        $result = $service->reject($fuelRequest, $user, $note);

        if (!$result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('approval.show', $fuelRequest->id)->with('error', $result['message']);
    }

    public function recordRealization(Request $request, $id, FuelRequestService $service)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$user->isFuelman() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya petugas Fuel Man atau Admin yang dapat mencatat realisasi pengisian BBM.');
        }

        $validated = $request->validate([
            'actual_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'meter_at_fueling' => ['required', 'numeric', 'min:0'],
            'fueled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'actual_liters.required' => 'Jumlah liter aktual wajib diisi.',
            'actual_liters.gt' => 'Jumlah liter aktual harus lebih besar dari nol (0).',
            'meter_at_fueling.required' => 'HM / KM aktual saat pengisian wajib diisi.',
            'meter_at_fueling.min' => 'HM / KM aktual tidak boleh negatif.',
            'fueled_at.required' => 'Tanggal dan waktu pengisian aktual wajib diisi.',
        ]);

        $transaction = $service->recordRealization($fuelRequest, $user, $validated);

        return redirect()->route('approval.show', $fuelRequest->id)
            ->with('success', "Realisasi pengisian BBM berhasil dicatat! Nomor Transaksi: {$transaction->transaction_no} ({$transaction->actual_liters} Liter pada HM/KM {$transaction->meter_at_fueling}).");
    }
}
