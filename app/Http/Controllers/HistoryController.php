<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelRequest;
use App\Models\FuelRequestHistory;
use App\Models\Area;
use App\Models\Vendor;
use App\Models\User;
use App\Services\ExcelExportService;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
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

    protected function buildFilteredQuery(Request $request, User $user)
    {
        $query = FuelRequest::with(['latestTransaction']);

        // Role-based access isolation
        if ($user->isDriver() && !$user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isVendor() && !$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                if ($user->vendor_id) {
                    $q->where('vendor_id', $user->vendor_id);
                }
                if ($user->vendor) {
                    $q->orWhere('vendor_name', $user->vendor->name);
                }
            });
        }

        // Filter Month
        if ($request->filled('month')) {
            $query->where('request_date', 'like', $request->month . '%');
        }

        // Filter Area
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Filter Vendor
        if ($request->filled('vendor')) {
            $query->where('vendor_name', $request->vendor);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('overall_status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('driver_name', 'like', "%{$search}%")
                  ->orWhere('unit_code', 'like', "%{$search}%")
                  ->orWhere('police_no', 'like', "%{$search}%")
                  ->orWhere('request_no', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = $this->getActiveUser();
        $query = $this->buildFilteredQuery($request, $user);

        $perPage = (int)$request->get('per_page', 10);
        $requests = $query->orderBy('request_date', 'desc')->paginate($perPage)->withQueryString();

        $areas = Area::where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('history.index', compact('requests', 'user', 'areas', 'vendors'));
    }

    public function export(Request $request, ExcelExportService $excel)
    {
        $user = $this->getActiveUser();
        $query = $this->buildFilteredQuery($request, $user);
        $data = $query->orderBy('request_date', 'desc')->get();

        FuelRequestHistory::record(
            null,
            $user->id,
            'report_exported',
            null,
            'completed',
            "Mengekspor laporan Fuel Request ke Excel (.xlsx) sejumlah {$data->count()} baris data."
        );

        $headers = [
            'No',
            'Nomor Pengajuan',
            'Tanggal Pengajuan',
            'Waktu Rencana Isi',
            'NRP Employee',
            'Nama Employee',
            'Perusahaan',
            'Unit Code',
            'Nomor Polisi',
            'Area Operasional',
            'HM/KM Pengajuan',
            'BBM Diminta (Liter)',
            'BBM Aktual (Liter)',
            'Selisih BBM (Liter)',
            'HM/KM Aktual Pengisian',
            'Tanggal Pengisian Aktual',
            'Vendor',
            'Status SPV',
            'Status Dept Head',
            'Status Vendor',
            'Tahap Proses',
            'Status Akhir',
        ];

        $rows = [];
        foreach ($data as $index => $row) {
            $r = $index + 2;
            $actualLiters = $row->actual_liters ?? $row->latestTransaction?->actual_liters;
            $variance = $actualLiters !== null ? round((float)$actualLiters - (float)$row->total_liters, 2) : 0;
            $fueledAt = $row->fueled_at ?? $row->latestTransaction?->fueled_at;
            $actualMeter = $row->actual_hm_km ?? $row->latestTransaction?->meter_at_fueling;

            $rows[] = [
                $index + 1,
                $row->request_no,
                $row->request_date ? $row->request_date->format('d/m/Y H:i') : '-',
                $row->planned_fuel_time ? $row->planned_fuel_time->format('d/m/Y H:i') : ($row->request_date ? $row->request_date->format('d/m/Y H:i') : '-'),
                $row->driver_nrp ?? $row->user?->nrp ?? '-',
                $row->driver_name,
                $row->company,
                $row->unit_code,
                $row->police_no,
                $row->area,
                $row->hm_km,
                (float)$row->total_liters,
                $actualLiters !== null ? (float)$actualLiters : '-',
                // Kolom N: Selisih BBM = BBM Aktual (M) - BBM Diminta (L)
                [
                    'formula' => "IF(ISNUMBER(M{$r}),ROUND(M{$r}-L{$r},2),\"-\")",
                    'value' => $actualLiters !== null ? (float)$variance : '-',
                ],
                $actualMeter !== null ? (float)$actualMeter : '-',
                $fueledAt ? $fueledAt->format('d/m/Y H:i') : '-',
                $row->vendor_name,
                strtoupper($row->status_spv),
                strtoupper($row->status_depthead),
                strtoupper($row->status_fuelman),
                $row->stage_label,
                [
                    'formula' => "IF(T{$r}=\"DISETUJUI\",\"DISETUJUI\",IF(OR(R{$r}=\"DITOLAK\",S{$r}=\"DITOLAK\",T{$r}=\"DITOLAK\"),\"DITOLAK\",\"MENUNGGU\"))",
                    'value' => strtoupper($row->overall_status),
                ],
            ];
        }

        // Baris Total / Ringkasan Rumus Excel di Bawah Tabel
        $lastRow = count($data) + 1;
        $sumRow = $lastRow + 1;
        if ($lastRow >= 2) {
            $totalReq = (float)$data->sum('total_liters');
            $totalAct = (float)$data->sum(fn($d) => (float)($d->actual_liters ?? $d->latestTransaction?->actual_liters ?? 0));
            $rows[] = [
                '',
                'TOTAL KESELURUHAN',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ['formula' => "SUM(L2:L{$lastRow})", 'value' => $totalReq],
                ['formula' => "SUM(M2:M{$lastRow})", 'value' => $totalAct],
                ['formula' => "ROUND(M{$sumRow}-L{$sumRow},2)", 'value' => round($totalAct - $totalReq, 2)],
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        $fileName = 'Laporan_Fuel_Request_' . date('Ymd_His') . '.xlsx';
        return $excel->downloadXlsx($fileName, $headers, $rows, 'Fuel Request');
    }
}
