@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div class="digi-card">
    <!-- Header Dashboard & Navigasi Cepat sesuai Role -->
    <div class="digi-table-top" style="margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 18px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <h1 class="digi-table-title" style="font-size: 22px;">Dashboard DigiFuel</h1>
                <span class="digi-badge digi-badge-blue">{{ strtoupper($user->jabatan ?? $user->role) }}</span>
            </div>
            <p style="font-size: 13.5px; color: #64748b; margin: 0;">
                Selamat datang, <strong>{{ $user->name }}</strong>
                @if($user->nrp) (NRP: <strong>{{ $user->nrp }}</strong>) @endif
                — PT UNITED TRACTORS Tbk - SITE SATUI
                @if($user->isVendor() && $user->vendor)
                    | Vendor: <strong>{{ $user->vendor->name }}</strong>
                @endif
            </p>
        </div>

        <!-- Tombol Navigasi Menuju Fitur Utama sesuai Role -->
        <div class="digi-dashboard-quick-actions">
            @if($user->isDriver() || $user->isAdmin())
                <a href="{{ route('fuel.create') }}" class="btn-digi btn-digi-blue">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Buat Fuel Request</span>
                </a>
            @endif

            @if($user->isApprover() || $user->isAdmin())
                <a href="{{ route('approval.index') }}" class="btn-digi btn-digi-yellow">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <span>Halaman Approval ({{ $stats['waiting_my_action'] }})</span>
                </a>
            @endif

            @if($user->isAdmin())
                <a href="{{ route('evaluation.index') }}" class="btn-digi btn-digi-green">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Evaluasi Konsumsi BBM</span>
                </a>
                <a href="{{ route('admin.master') }}" class="btn-digi btn-digi-gray">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                    <span>Kelola Master Data</span>
                </a>
            @else
                <a href="{{ route('history.index') }}" class="btn-digi btn-digi-gray">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Riwayat Pengajuan</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Peringatan Tanda Tangan jika belum diunggah -->
    @if(!$user->signature_url)
        <div class="digi-signature-alert">
            <div class="digi-signature-alert-content">
                <div class="digi-signature-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
                <div>
                    <div class="digi-signature-alert-title">
                        <span>Tanda Tangan Digital Wajib Diisi!</span>
                        <span class="digi-signature-alert-badge">Wajib</span>
                    </div>
                    <div class="digi-signature-alert-text">
                        Anda belum memiliki tanda tangan digital aktif. Sesuai kebijakan sistem DigiFuel, seluruh pengajuan dan persetujuan Fuel Request mewajibkan tanda tangan digital yang sah.
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="digi-signature-alert-btn">
                Unggah Tanda Tangan Sekarang &rarr;
            </a>
        </div>
    @endif

    <!-- KARTU STATISTIK (Menyesuaikan Role Pengguna) -->
    @if($user->isAdmin())
        <!-- Statistik Lengkap Admin -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #475569;">TOTAL FUEL REQUEST</div>
                <div style="font-size: 26px; font-weight: 800; color: #0284c7; margin-top: 6px;">{{ $stats['total_requests'] }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Seluruh pengajuan tercatat</div>
            </div>

            <div style="background: #fefce8; border: 1px solid #fde047; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #854d0e;">MENUNGGU APPROVAL</div>
                <div style="font-size: 26px; font-weight: 800; color: #ca8a04; margin-top: 6px;">{{ $stats['pending_requests'] }}</div>
                <div style="font-size: 12px; color: #854d0e; margin-top: 4px;">Dalam proses berjenjang</div>
            </div>

            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #166534;">DISETUJUI & SELESAI ISI</div>
                <div style="font-size: 26px; font-weight: 800; color: #15803d; margin-top: 6px;">
                    {{ $stats['approved_requests'] }} <span style="font-size: 13px; font-weight: 600;">({{ $stats['completed_fueling'] }} Terealisasi)</span>
                </div>
                <div style="font-size: 12px; color: #166534; margin-top: 4px;">Ditolak: {{ $stats['rejected_requests'] }} pengajuan</div>
            </div>

            <div style="background: #e0f2fe; border: 1px solid #7dd3fc; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #0369a1;">VOLUME BBM (DIMINTA / AKTUAL)</div>
                <div style="font-size: 22px; font-weight: 800; color: #0284c7; margin-top: 6px;">
                    {{ number_format($stats['total_requested_liters'], 0, ',', '.') }} L / {{ number_format($stats['total_actual_liters'], 0, ',', '.') }} L
                </div>
                <div style="font-size: 12px; color: #0369a1; margin-top: 4px;">Unit Aktif: {{ $stats['active_units'] }} | Area: {{ $stats['active_areas'] }}</div>
            </div>

            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #991b1b;">ESTIMASI BIAYA KERUGIAN BBM</div>
                <div style="font-size: 21px; font-weight: 800; color: #b91c1c; margin-top: 6px;">
                    Rp {{ number_format($stats['eval_cost_loss'], 0, ',', '.') }}
                </div>
                <div style="font-size: 12px; color: #991b1b; margin-top: 4px;">
                    Pemborosan: {{ number_format($stats['eval_waste_liters'], 1, ',', '.') }} L ({{ $stats['eval_abnormal_units'] }} Unit Tidak Wajar)
                </div>
            </div>
        </div>
    @elseif($user->isApprover())
        <!-- Statistik Approver (SPV, Dept Head/Manager, Vendor, Fuel Man) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div style="background: #fefce8; border: 1.5px solid #facc15; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #854d0e;">MENUNGGU TINDAKAN ANDA</div>
                <div style="font-size: 28px; font-weight: 800; color: #ca8a04; margin-top: 6px;">{{ $stats['waiting_my_action'] }}</div>
                <div style="font-size: 12px; color: #854d0e; margin-top: 4px;">
                    @if($user->isFuelman()) Siap diisi BBM aktual @else Menunggu keputusan persetujuan @endif
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #475569;">TOTAL PENGAJUAN TERKAIT</div>
                <div style="font-size: 28px; font-weight: 800; color: #0284c7; margin-top: 6px;">{{ $stats['total_requests'] }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Total volume: {{ number_format($stats['total_requested_liters'], 0, ',', '.') }} Liter</div>
            </div>

            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #166534;">PENGAJUAN DISETUJUI</div>
                <div style="font-size: 28px; font-weight: 800; color: #15803d; margin-top: 6px;">{{ $stats['approved_requests'] }}</div>
                <div style="font-size: 12px; color: #166534; margin-top: 4px;">Realisasi selesai: {{ $stats['completed_fueling'] }}</div>
            </div>

            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #991b1b;">PENGAJUAN DITOLAK</div>
                <div style="font-size: 28px; font-weight: 800; color: #dc2626; margin-top: 6px;">{{ $stats['rejected_requests'] }}</div>
                <div style="font-size: 12px; color: #991b1b; margin-top: 4px;">Memiliki catatan alasan penolakan</div>
            </div>
        </div>
    @else
        <!-- Statistik Employee / Driver -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #475569;">TOTAL PENGAJUAN SAYA</div>
                <div style="font-size: 28px; font-weight: 800; color: #0284c7; margin-top: 6px;">{{ $stats['total_requests'] }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Total BBM: {{ number_format($stats['total_requested_liters'], 0, ',', '.') }} Liter</div>
            </div>

            <div style="background: #fefce8; border: 1px solid #fde047; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #854d0e;">MENUNGGU PERSETUJUAN</div>
                <div style="font-size: 28px; font-weight: 800; color: #ca8a04; margin-top: 6px;">{{ $stats['pending_requests'] }}</div>
                <div style="font-size: 12px; color: #854d0e; margin-top: 4px;">Sedang diproses Approver</div>
            </div>

            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #166534;">PENGAJUAN DISETUJUI</div>
                <div style="font-size: 28px; font-weight: 800; color: #15803d; margin-top: 6px;">{{ $stats['approved_requests'] }}</div>
                <div style="font-size: 12px; color: #166534; margin-top: 4px;">Siap cetak surat / isi BBM</div>
            </div>

            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #991b1b;">PENGAJUAN DITOLAK</div>
                <div style="font-size: 28px; font-weight: 800; color: #dc2626; margin-top: 6px;">{{ $stats['rejected_requests'] }}</div>
                <div style="font-size: 12px; color: #991b1b; margin-top: 4px;">Periksa catatan penolakan</div>
            </div>
        </div>
    @endif

    <!-- PROGRESS STEPPER PENGAJUAN TERBARU (Khususnya untuk memantau tahapan berjenjang) -->
    @if($latestMyRequest)
        @php
            $spvDone = $latestMyRequest->status_spv === 'disetujui';
            $dhDone = $latestMyRequest->status_depthead === 'disetujui';
            $vendorDone = $latestMyRequest->isVendorApproved();
            $fuelingDone = $latestMyRequest->current_stage === 'completed';
        @endphp
        <div class="digi-form-box" style="margin-bottom: 24px; padding: 18px 22px; background: #f8fafc;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 14px; font-weight: 700; color: #0284c7;">
                    Progres Persetujuan Pengajuan Terbaru: <strong>{{ $latestMyRequest->request_no }}</strong> ({{ $latestMyRequest->unit_code }} — {{ (float)$latestMyRequest->total_liters }} L)
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ route('approval.show', $latestMyRequest->id) }}" class="btn-digi btn-digi-blue" style="padding: 5px 14px; font-size: 12px;">Buka Detail</a>
                    <a href="{{ route('fuel.document', $latestMyRequest->id) }}" target="_blank" class="btn-digi btn-digi-green" style="padding: 5px 14px; font-size: 12px;">Surat Fuel Request</a>
                </div>
            </div>
            <div class="digi-stepper-scroll" style="text-align: center; font-size: 12px;">
                <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px;">
                    <div style="font-weight: 700; color: #15803d;">1. Employee</div>
                    <div style="color: #475569; margin: 2px 0;">{{ $latestMyRequest->driver_name }}</div>
                    <span class="digi-badge digi-badge-green">Diajukan</span>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                    <div style="font-weight: 700; color: #334155;">2. SPV / Dept Head</div>
                    <div style="color: #64748b; margin: 2px 0;">Tahap Pertama</div>
                    <span class="digi-badge {{ $spvDone ? 'digi-badge-green' : ($latestMyRequest->status_spv === 'ditolak' ? 'digi-badge-red' : 'digi-badge-yellow') }}">
                        {{ ucfirst($latestMyRequest->status_spv) }}
                    </span>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                    <div style="font-weight: 700; color: #334155;">3. Dept Head / Mgr</div>
                    <div style="color: #64748b; margin: 2px 0;">Tahap Kedua</div>
                    <span class="digi-badge {{ $dhDone ? 'digi-badge-green' : ($latestMyRequest->status_depthead === 'ditolak' ? 'digi-badge-red' : ($latestMyRequest->status_depthead === 'menunggu' ? 'digi-badge-yellow' : 'digi-badge-gray')) }}">
                        {{ $latestMyRequest->status_depthead === '-' ? 'Menunggu' : ucfirst($latestMyRequest->status_depthead) }}
                    </span>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                    <div style="font-weight: 700; color: #334155;">4. Persetujuan Vendor</div>
                    <div style="color: #64748b; margin: 2px 0;">{{ $latestMyRequest->vendor_name }}</div>
                    <span class="digi-badge {{ $vendorDone ? 'digi-badge-green' : ($latestMyRequest->status_fuelman === 'ditolak' ? 'digi-badge-red' : ($latestMyRequest->status_fuelman === 'menunggu' ? 'digi-badge-yellow' : 'digi-badge-gray')) }}">
                        {{ $vendorDone ? 'Disetujui' : ($latestMyRequest->status_fuelman === 'menunggu' ? 'Menunggu' : 'Belum Aktif') }}
                    </span>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                    <div style="font-weight: 700; color: #334155;">5. Realisasi BBM</div>
                    <div style="color: #64748b; margin: 2px 0;">Fuel Man</div>
                    <span class="digi-badge {{ $fuelingDone ? 'digi-badge-green' : ($vendorDone ? 'digi-badge-blue' : 'digi-badge-gray') }}">
                        {{ $fuelingDone ? 'Selesai Isi' : ($vendorDone ? 'Siap Diisi' : 'Menunggu') }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    <!-- TABEL PERMINTAAN TERBARU & AKTIVITAS TERBARU -->
    <div class="digi-dashboard-split-grid">
        <!-- Kolom Kiri: Daftar Permintaan Fuel Terbaru -->
        <div style="min-width: 0; max-width: 100%; width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 class="digi-section-title" style="margin: 0;">Permintaan Fuel Request Terbaru</h2>
                <a href="{{ route('history.index') }}" style="font-size: 13px; color: #0284c7; font-weight: 600; text-decoration: none;">Lihat Semua →</a>
            </div>

            <div class="digi-mobile-swipe-hint">
                <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>Geser tabel ke samping untuk melihat detail BBM & aksi</span>
            </div>

            <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
                <table class="digi-table" style="margin-bottom: 0;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th>No. Surat & Tgl</th>
                            <th>Employee & Area</th>
                            <th>Unit</th>
                            <th>BBM</th>
                            <th>Status Tahapan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRequests as $req)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #0284c7;">{{ $req->request_no }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">{{ $req->request_date->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $req->driver_name }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">{{ $req->area }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $req->unit_code }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">{{ $req->police_no }}</div>
                                </td>
                                <td>
                                    <strong>{{ (float)$req->total_liters }} L</strong>
                                    @if($req->actual_liters !== null)
                                        <div style="font-size: 11px; color: #15803d; font-weight: 600;">Aktual: {{ (float)$req->actual_liters }} L</div>
                                    @endif
                                </td>
                                <td>
                                    @if($req->overall_status === 'disetujui')
                                        <span class="digi-badge digi-badge-green">{{ $req->stage_label }}</span>
                                    @elseif($req->overall_status === 'ditolak')
                                        <span class="digi-badge digi-badge-red">Ditolak</span>
                                    @else
                                        <span class="digi-badge digi-badge-yellow">{{ $req->stage_label }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="{{ route('approval.show', $req->id) }}" class="digi-action-btn digi-action-purple" title="Buka Detail / Approval">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                        </a>
                                        <a href="{{ route('fuel.document', $req->id) }}" target="_blank" class="digi-action-btn digi-action-green" title="Lihat Surat Fuel Request">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                <rect x="6" y="14" width="12" height="8"></rect>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 28px; color: #94a3b8;">
                                    Belum ada permintaan Fuel Request yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan: Aktivitas Terbaru & Navigasi Cepat -->
        <div style="min-width: 0; max-width: 100%; width: 100%;">
            <h2 class="digi-section-title" style="margin: 0 0 12px 0;">Aktivitas Terbaru</h2>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                @forelse($recentActivities as $act)
                    <div style="padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; font-size: 12.5px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                            <span style="font-weight: 700; color: #0284c7;">{{ strtoupper(str_replace('_', ' ', $act->action)) }}</span>
                            <span style="font-size: 11px; color: #94a3b8;">{{ $act->created_at?->diffForHumans() }}</span>
                        </div>
                        <div style="color: #334155; line-height: 1.4;">{{ $act->note }}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                            Oleh: <strong>{{ $act->user?->name ?? 'Sistem' }}</strong>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">
                        Belum ada aktivitas terbaru.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
