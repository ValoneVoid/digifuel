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
use App\Models\SupportPoint;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FuelRequestController extends Controller
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

    public function create()
    {
        $user = $this->getActiveUser();

        // Only active units can be used for new Fuel Requests
        $vehicles = Vehicle::with(['defaultArea', 'unitVendor'])->where('is_active', true)->orderBy('unit_code')->get();
        $areas = Area::where('is_active', true)->with('activeSupportPoints')->orderBy('name')->get();
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

            $areaSpvs = $assignments->where('role_type', 'spv')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();
            $areaManagers = $assignments->where('role_type', 'manager')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();
            $areaFuelmen = $assignments->where('role_type', 'fuelman')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();
            $areaVendorUsers = $assignments->where('role_type', 'vendor')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();

            $areaVendors = $areaVendorUsers->map(fn($u) => $u->vendor)->filter(fn($v) => $v && $v->is_active)->unique('id')->values();

            $areaApproverMap[$area->name] = [
                'has_support_points' => $area->activeSupportPoints->isNotEmpty(),
                'support_points' => $area->activeSupportPoints->map(fn($sp) => [
                    'id' => $sp->id,
                    'name' => $sp->name,
                ])->values(),
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
                $spvs = $assignments->where('role_type', 'spv')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();
            }
            if ($assignments->where('role_type', 'manager')->isNotEmpty()) {
                $deptheads = $assignments->where('role_type', 'manager')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();
            }
            if ($assignments->where('role_type', 'fuelman')->isNotEmpty()) {
                $fuelmen = $assignments->where('role_type', 'fuelman')->pluck('user')->filter(fn($u) => $u && $u->is_active)->values();
            }
            $assignedVendors = $assignments->where('role_type', 'vendor')->pluck('user.vendor')->filter(fn($v) => $v && $v->is_active)->unique('id')->values();
            if ($assignedVendors->isNotEmpty()) {
                $vendors = $assignedVendors;
            }
        }

        return response()->json([
            'area' => $areaName,
            'has_support_points' => $area ? $area->activeSupportPoints()->exists() : false,
            'support_points' => $area ? $area->activeSupportPoints->map(fn($sp) => [
                'id' => $sp->id,
                'name' => $sp->name,
            ])->values() : [],
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
        $vehicle = Vehicle::with('unitVendor')->where('unit_code', $unitCode)->where('is_active', true)->first();

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
            'unit_vendor' => $vehicle->unitVendor?->name ?? 'Internal UT',
            'meter_type' => 'KM',
            'tank_capacity' => $vehicle->tank_capacity,
        ]);
    }

    public function store(StoreFuelRequest $request, FuelRequestService $service)
    {
        $user = $this->getActiveUser();
        $isDraft = $request->input('action_type') === 'draft';
        $payload = $request->validated();

        if ($request->hasFile('spbu_receipt_photo')) {
            $payload['spbu_receipt_photo'] = $this->storeCompressedSpbuReceipt(
                $request->file('spbu_receipt_photo'),
                $user->id
            );
        } else {
            unset($payload['spbu_receipt_photo']);
        }

        $fuelRequest = $service->createRequest($user, $payload, $isDraft);

        if ($isDraft) {
            return redirect()->route('history.index')
                ->with('success', "Fuel Request {$fuelRequest->request_no} berhasil disimpan sebagai Draft.");
        }

        if ($fuelRequest->isSpbu()) {
            $successMsg = "Pengisian SPBU {$fuelRequest->request_no} berhasil dicatat dan langsung Full Approved (Selesai)!";
        } elseif ($user->isDepthead()) {
            $successMsg = "Permintaan Fuel {$fuelRequest->request_no} berhasil dibuat dan otomatis disetujui (Dept Head). Menunggu Persetujuan & Realisasi Vendor!";
        } else {
            $successMsg = "Permintaan Fuel {$fuelRequest->request_no} berhasil dikirim dan menunggu persetujuan SPV!";
        }

        return redirect()->route('history.index')
            ->with('success', $successMsg);
    }

    /**
     * Compress and store uploaded SPBU receipt image (auto-resize max 1400px & JPEG 78% via GD)
     */
    protected function storeCompressedSpbuReceipt(\Illuminate\Http\UploadedFile $file, int $userId): string
    {
        $privateDir = storage_path('app/private/spbu_receipts');
        if (!is_dir($privateDir)) {
            @mkdir($privateDir, 0755, true);
        }
        $publicDir = public_path('uploads/spbu_receipts');
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }

        $filename = 'spbu_nota_' . $userId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.jpg';
        $privatePath = $privateDir . DIRECTORY_SEPARATOR . $filename;
        $publicPath = $publicDir . DIRECTORY_SEPARATOR . $filename;

        $compressed = false;
        if (extension_loaded('gd')) {
            try {
                $srcPath = $file->getRealPath();
                $info = @getimagesize($srcPath);
                if ($info !== false) {
                    [$origW, $origH] = $info;
                    $mime = $info['mime'] ?? '';
                    $srcImg = match ($mime) {
                        'image/jpeg' => @imagecreatefromjpeg($srcPath),
                        'image/png' => @imagecreatefrompng($srcPath),
                        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
                        default => false,
                    };

                    if ($srcImg !== false) {
                        $maxDim = 1400;
                        $ratio = min($maxDim / max($origW, 1), $maxDim / max($origH, 1), 1.0);
                        $newW = max(1, (int) round($origW * $ratio));
                        $newH = max(1, (int) round($origH * $ratio));

                        $dstImg = imagecreatetruecolor($newW, $newH);
                        $white = imagecolorallocate($dstImg, 255, 255, 255);
                        imagefill($dstImg, 0, 0, $white);
                        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                        imagejpeg($dstImg, $privatePath, 78);
                        imagedestroy($srcImg);
                        imagedestroy($dstImg);
                        $compressed = file_exists($privatePath);
                    }
                }
            } catch (\Throwable $e) {
                $compressed = false;
            }
        }

        if (!$compressed) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'spbu_nota_' . $userId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $privatePath = $privateDir . DIRECTORY_SEPARATOR . $filename;
            $publicPath = $publicDir . DIRECTORY_SEPARATOR . $filename;
            $file->move($privateDir, $filename);
        }

        @copy($privatePath, $publicPath);

        return 'uploads/spbu_receipts/' . $filename;
    }

    public function serveSpbuReceipt($id, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->view($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin melihat foto nota SPBU ini.');
        }

        if (empty($fuelRequest->spbu_receipt_photo)) {
            abort(404, 'Foto nota SPBU tidak ditemukan.');
        }

        $safeName = basename($fuelRequest->spbu_receipt_photo);
        $privatePath = storage_path('app/private/spbu_receipts/' . $safeName);
        if (file_exists($privatePath)) {
            return response()->file($privatePath);
        }

        $publicPath = public_path('uploads/spbu_receipts/' . $safeName);
        if (file_exists($publicPath)) {
            return response()->file($publicPath);
        }

        abort(404, 'File foto nota SPBU tidak ditemukan di penyimpanan.');
    }

    public function submitDraft($id, FuelRequestService $service, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->update($user, $fuelRequest)) {
            abort(403, 'Anda tidak memiliki akses untuk mengajukan draft ini.');
        }

        if (!$user->hasActiveSignature()) {
            return redirect()->back()->with('error', 'Gagal mengajukan Fuel Request: Anda wajib mengunggah tanda tangan digital di menu Profil terlebih dahulu.');
        }

        $service->submitDraft($fuelRequest, $user);
        $fuelRequest->refresh();

        if ($fuelRequest->isSpbu()) {
            $submitMsg = "Pengisian SPBU {$fuelRequest->request_no} berhasil diajukan dan langsung Full Approved (Selesai)!";
        } elseif ($user->isDepthead()) {
            $submitMsg = "Fuel Request {$fuelRequest->request_no} berhasil diajukan dan langsung diteruskan ke Vendor (Auto-Approved Dept Head)!";
        } else {
            $submitMsg = "Fuel Request {$fuelRequest->request_no} berhasil diajukan ke SPV!";
        }

        return redirect()->route('history.index')
            ->with('success', $submitMsg);
    }

    public function cancel($id, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->cancel($user, $fuelRequest)) {
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

    public function destroy(Request $request, $id, FuelRequestPolicy $policy)
    {
        $user = $this->getActiveUser();
        $fuelRequest = FuelRequest::findOrFail($id);

        if (!$policy->delete($user, $fuelRequest)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menghapus pengajuan ini, atau pengajuan sudah memiliki transaksi pengisian fisik BBM.');
        }

        // Verifikasi 2 Langkah: Memastikan kata kunci konfirmasi "HAPUS" diketik dengan benar
        if ($request->has('confirm_keyword') || $request->has('verification_step')) {
            $keyword = strtoupper(trim((string) $request->input('confirm_keyword', '')));
            if ($keyword !== 'HAPUS') {
                return redirect()->back()->with('error', 'Verifikasi 2 Langkah gagal: Anda wajib mengetik kata kunci "HAPUS" dengan benar.');
            }
        }

        $requestNo = $fuelRequest->request_no;
        $driverName = $fuelRequest->driver_name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($fuelRequest, $user, $requestNo, $driverName) {
            // Delete related child records explicitly
            $fuelRequest->approvals()->delete();
            $fuelRequest->histories()->delete();
            \App\Models\UserNotification::where('fuel_request_id', $fuelRequest->id)->delete();

            // Record audit trail of the deletion
            FuelRequestHistory::record(
                null,
                $user->id,
                'request_deleted',
                $fuelRequest->current_stage,
                'deleted',
                "Pengajuan BBM {$requestNo} milik {$driverName} dihapus oleh {$user->name} ({$user->jabatan})."
            );

            $fuelRequest->delete();
        });

        return redirect()->route('history.index')
            ->with('success', "Pengajuan BBM {$requestNo} berhasil dihapus dari sistem.");
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
