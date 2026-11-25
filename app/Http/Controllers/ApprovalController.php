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
            'pending_user_verification' => (clone $baseStatsQuery)->where('current_stage', 'pending_user_verification')->count(),
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
            WHEN current_stage = 'pending_user_verification' THEN 4
            WHEN current_stage IN ('approved', 'fueling') THEN 5
            ELSE 6 END")
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

        if (!$user->hasActiveSignature()) {
            return redirect()->back()->with('error', 'Gagal memproses persetujuan: Anda wajib mengunggah tanda tangan digital di menu Profil terlebih dahulu.');
        }

        $adjustedLiters = null;
        $canAdjustOnSpv = ($user->isSpv() || $user->isAdmin()) && $fuelRequest->status_spv === 'menunggu';
        $canAdjustOnDepthead = ($user->isManager() || $user->isAdmin()) && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead === 'menunggu';

        if ($request->filled('total_liters') && ($canAdjustOnSpv || $canAdjustOnDepthead)) {
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

        // Tangkap data realisasi pengisian BBM fisik dari pihak Vendor (Tahap 4)
        // Catatan: meter_at_fueling dan fueled_at dikunci di server untuk non-Admin (Item 7)
        $realizationData = null;
        if (($user->isVendor() || $user->isFuelman() || $user->isAdmin()) && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead === 'disetujui') {
            if ($request->filled('actual_liters') || $request->filled('meter_at_fueling')) {
                $request->validate([
                    'actual_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
                    'meter_at_fueling' => ['nullable', 'numeric', 'min:0'],
                    'fueled_at' => ['nullable', 'date'],
                    'notes' => ['nullable', 'string', 'max:1000'],
                ], [
                    'actual_liters.required' => 'Jumlah liter aktual wajib diisi.',
                    'actual_liters.numeric' => 'Jumlah liter aktual harus berupa angka.',
                    'actual_liters.gt' => 'Jumlah liter aktual harus lebih besar dari 0.',
                    'meter_at_fueling.numeric' => 'KM/HM odometer harus berupa angka.',
                    'meter_at_fueling.min' => 'KM/HM odometer tidak boleh negatif.',
                ]);

                $realizationData = [
                    'actual_liters' => $user->isAdmin() && $request->filled('actual_liters')
                        ? (float)$request->input('actual_liters')
                        : (float)$fuelRequest->total_liters,
                    'meter_at_fueling' => $user->isAdmin() && $request->filled('meter_at_fueling')
                        ? (float)$request->input('meter_at_fueling')
                        : (float)$fuelRequest->hm_km,
                    'fueled_at' => $user->isAdmin() && $request->filled('fueled_at')
                        ? $request->input('fueled_at')
                        : now(),
                    'notes' => $request->input('notes') ?? $request->input('note'),
                ];
            }
        }

        $result = $service->approve($fuelRequest, $user, $request->input('note'), $adjustedLiters, $realizationData);

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

    public function recordRealization(Request $request, $id, FuelRequestService $service, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->recordRealization($user, $fuelRequest)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Realisasi pengisian BBM sudah tercatat & dikunci, atau pengajuan belum memperoleh persetujuan Dept Head / Vendor.',
            ]);
        }

        if (!$user->hasActiveSignature()) {
            return redirect()->back()->with('error', 'Gagal mencatat realisasi pengisian BBM: Anda wajib mengunggah tanda tangan digital di menu Profil terlebih dahulu.');
        }

        $validated = $request->validate([
            'actual_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'meter_at_fueling' => ['nullable', 'numeric', 'min:0'],
            'fueled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'actual_liters.required' => 'Jumlah liter aktual wajib diisi.',
            'actual_liters.numeric' => 'Jumlah liter aktual harus berupa angka numerik.',
            'actual_liters.gt' => 'Jumlah liter aktual harus lebih besar dari nol (0).',
            'actual_liters.max' => 'Jumlah liter aktual tidak boleh lebih dari 5.000 Liter.',
            'meter_at_fueling.numeric' => 'HM / KM aktual harus berupa angka numerik valid.',
            'meter_at_fueling.min' => 'HM / KM aktual tidak boleh negatif.',
            'fueled_at.date' => 'Format tanggal dan waktu pengisian tidak valid.',
        ]);

        // Server-side lock penuh untuk non-Admin (Vendor tidak boleh mengubah data apapun)
        if (!$user->isAdmin()) {
            $validated['actual_liters'] = (float) $fuelRequest->total_liters;
            $validated['meter_at_fueling'] = (float) $fuelRequest->hm_km;
            $validated['fueled_at'] = now();
        } else {
            $validated['meter_at_fueling'] = isset($validated['meter_at_fueling']) ? (float) $validated['meter_at_fueling'] : (float) $fuelRequest->hm_km;
            $validated['fueled_at'] = $validated['fueled_at'] ?? now();
        }

        $transaction = $service->recordRealization($fuelRequest, $user, $validated);

        return redirect()->route('approval.show', $fuelRequest->id)
            ->with('success', "Realisasi pengisian BBM berhasil dicatat & dikunci! Nomor Transaksi: {$transaction->transaction_no} ({$transaction->actual_liters} Liter). Menunggu Verifikasi Akhir Pemohon.");
    }

    /**
     * Final Verification (Final Approve) by requesting User / Driver
     */
    public function verifyUser(Request $request, $id, FuelRequestService $service, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->verifyUser($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin melakukan Verifikasi Akhir pada pengajuan ini.');
        }

        $validated = $request->validate([
            'confirm_responsibility' => ['required', 'accepted'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'confirm_responsibility.required' => 'Anda wajib mencentang pernyataan pertanggungjawaban sebelum menyelesaikan pengisian.',
            'confirm_responsibility.accepted' => 'Anda wajib menyetujui pernyataan pertanggungjawaban sebelum menyelesaikan pengisian.',
        ]);

        $result = $service->verifyByUser($fuelRequest, $user, $validated['note'] ?? null);

        if (!$result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('approval.show', $fuelRequest->id)->with('success', $result['message']);
    }
}
