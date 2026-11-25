<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fuel Request') - FuelLink PT United Tractors Tbk Site Satui</title>
    <meta name="description" content="Sistem Pengajuan, Persetujuan Digital, dan Evaluasi Konsumsi BBM PT United Tractors Tbk Site Satui">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}?v={{ file_exists(public_path('css/digifuel.css')) ? filemtime(public_path('css/digifuel.css')) : time() }}">
    @if(file_exists(public_path('css/digifuel.css')))
        <style id="digifuel-core-css">{!! file_get_contents(public_path('css/digifuel.css')) !!}</style>
    @endif
    <link rel="icon" type="image/png" href="{{ asset('images/fuellink_logo.png') }}">
    <!-- SweetAlert2 Library for Animated Success, Reject & Confirmation Popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Canvas Confetti for Celebration Checkmark Animation -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    @php
        $bgData = \App\Models\SystemBackground::getActiveBackground();
        $bgUrl = $bgData['image_url'];
        $bgOpacity = $bgData['overlay_opacity'];
    @endphp
    <style>
        :root {
            --bg-overlay-opacity: {{ $bgOpacity }};
            --bg-active-url: url('{{ $bgUrl }}');
        }
        body {
            background-image: linear-gradient(rgba(0, 0, 0, var(--bg-overlay-opacity)), rgba(0, 0, 0, var(--bg-overlay-opacity))), var(--bg-active-url) !important;
            background-size: cover !important;
            background-position: center top !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
        }
        .digi-nav-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            margin-left: 6px;
            border-radius: 999px;
            background: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.35);
            vertical-align: middle;
        }
        .digi-nav-count-badge.is-amber {
            background: #f59e0b;
            box-shadow: 0 2px 5px rgba(245, 158, 11, 0.35);
        }
    </style>
</head>
<body>
    @php
        $currentUser = auth()->user() ?? \App\Models\User::where('role', 'driver')->first();
        $isApprover = $currentUser ? $currentUser->isApprover() : false;
        $isAdmin = $currentUser ? $currentUser->isAdmin() : false;
        $unreadNotifs = $currentUser ? $currentUser->userNotifications()->where('is_read', false)->take(6)->get() : collect();
    @endphp

    <!-- NAVBAR -->
    <nav class="digi-navbar">
        <div class="digi-brand-left">
            <a href="{{ route('dashboard') }}" class="digi-logo-badge" title="FuelLink - PT United Tractors Tbk Site Satui">
                <img src="{{ asset('images/fuellink_logo.png') }}" alt="FuelLink" style="height: 28px; width: 28px; border-radius: 6px; object-fit: contain;">
                <span style="font-weight: 800; font-size: 16px; letter-spacing: -0.3px;"><span style="color: #0284c7;">Fuel</span><span style="color: #16a34a;">Link</span></span>
            </a>
        </div>

        <div class="digi-nav-links">
            <span class="digi-nav-pill" aria-hidden="true"></span>
            <a href="{{ route('dashboard') }}" class="digi-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="digi-nav-icon" aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>
                </span>
                <span>Dashboard</span>
            </a>
            @if($isAdmin)
                <a href="{{ route('fuel.create') }}" class="digi-nav-link {{ request()->routeIs('fuel.create') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    </span>
                    <span>Form Request</span>
                </a>
                <a href="{{ route('approval.index') }}" class="digi-nav-link {{ request()->routeIs('approval.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    </span>
                    <span>Pengajuan</span>
                    @if(($pendingApprovalCount ?? 0) > 0)
                        <span class="digi-nav-count-badge" title="{{ $pendingApprovalCount }} pengajuan menunggu persetujuan">{{ $pendingApprovalCount }}</span>
                    @endif
                </a>
                <a href="{{ route('history.index') }}" class="digi-nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span>History</span>
                    @if(($pendingUserVerifyCount ?? 0) > 0)
                        <span class="digi-nav-count-badge is-amber" title="{{ $pendingUserVerifyCount }} pengisian menunggu verifikasi akhir Anda">{{ $pendingUserVerifyCount }}</span>
                    @endif
                </a>
                <a href="{{ route('evaluation.index') }}" class="digi-nav-link {{ request()->routeIs('evaluation.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </span>
                    <span>Evaluasi BBM</span>
                </a>
                <a href="{{ route('admin.master') }}" class="digi-nav-link {{ request()->routeIs('admin.master*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                    </span>
                    <span>Master Data</span>
                </a>
                <a href="{{ route('profile.index') }}" class="digi-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <span>Profil</span>
                </a>
            @elseif($isApprover)
                @if($currentUser?->isSpv() || $currentUser?->isDepthead())
                    <a href="{{ route('fuel.create') }}" class="digi-nav-link {{ request()->routeIs('fuel.create') ? 'active' : '' }}">
                        <span class="digi-nav-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                        </span>
                        <span>Form Request</span>
                    </a>
                @endif
                <a href="{{ route('approval.index') }}" class="digi-nav-link {{ request()->routeIs('approval.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    </span>
                    <span>Pengajuan</span>
                    @if(($pendingApprovalCount ?? 0) > 0)
                        <span class="digi-nav-count-badge" title="{{ $pendingApprovalCount }} pengajuan menunggu tindakan Anda">{{ $pendingApprovalCount }}</span>
                    @endif
                </a>
                <a href="{{ route('history.index') }}" class="digi-nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span>History</span>
                    @if(($pendingUserVerifyCount ?? 0) > 0)
                        <span class="digi-nav-count-badge is-amber" title="{{ $pendingUserVerifyCount }} pengisian menunggu verifikasi akhir Anda">{{ $pendingUserVerifyCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.index') }}" class="digi-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <span>Profil</span>
                </a>
            @else
                <a href="{{ route('fuel.create') }}" class="digi-nav-link {{ request()->routeIs('fuel.create') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    </span>
                    <span>Form Request</span>
                </a>
                <a href="{{ route('history.index') }}" class="digi-nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span>History</span>
                    @if(($pendingUserVerifyCount ?? 0) > 0)
                        <span class="digi-nav-count-badge is-amber" title="{{ $pendingUserVerifyCount }} pengisian menunggu verifikasi akhir Anda">{{ $pendingUserVerifyCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.index') }}" class="digi-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <span class="digi-nav-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <span>Profil</span>
                </a>
            @endif
        </div>

        <div class="digi-nav-right">
            <!-- Tombol Panduan Penggunaan (Tanda Tanya Ikon Mencolok Dekat Notifikasi) -->
            <a href="{{ route('panduan') }}" 
               class="digi-help-icon-btn {{ request()->routeIs('panduan*') ? 'active' : '' }}" 
               title="Panduan & Tutorial Penggunaan FuelLink" 
               aria-label="Panduan Sistem">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </a>

            <!-- Notification Dropdown -->
            @if($currentUser)
                <div style="position: relative; display: flex; align-items: center;">
                    <button type="button" onclick="toggleNotifMenu()" class="digi-notif-btn" title="Notifikasi Pengajuan & Approval">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                        @if($unreadNotifs->count() > 0)
                            <span class="digi-notif-badge">{{ $unreadNotifs->count() }}</span>
                        @endif
                    </button>
                    <div id="digiNotifMenu" style="display: none; position: absolute; right: 0; top: 38px; width: 320px; background: #fff; border-radius: 10px; box-shadow: 0 12px 28px rgba(0,0,0,0.18); border: 1px solid #e2e8f0; z-index: 200; overflow: hidden;">
                        <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 12.5px; font-weight: 700; color: #0f172a; display: flex; justify-content: space-between;">
                            <span>Notifikasi Sistem</span>
                            <span style="color: #64748b; font-weight: 500;">{{ $unreadNotifs->count() }} baru</span>
                        </div>
                        <div style="max-height: 260px; overflow-y: auto;">
                            @forelse($unreadNotifs as $notif)
                                <a href="{{ route('notifications.read', $notif->id) }}" style="display: block; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: #1e293b; font-size: 12px;">
                                    <div style="font-weight: 700; color: #0284c7; margin-bottom: 2px;">{{ $notif->title }}</div>
                                    <div style="color: #475569; line-height: 1.35;">{{ $notif->message }}</div>
                                    <div style="color: #94a3b8; font-size: 10.5px; margin-top: 4px;">{{ $notif->created_at?->diffForHumans() }}</div>
                                </a>
                            @empty
                                <div style="padding: 18px; text-align: center; color: #94a3b8; font-size: 12px;">
                                    Belum ada notifikasi baru.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <!-- User Profile Info Badge -->
            @if($currentUser)
                <a href="{{ route('profile.index') }}" class="digi-user-profile-badge" title="Buka Profil: {{ $currentUser->name }} ({{ $currentUser->jabatan ?: ucfirst($currentUser->role) }})">
                    <span class="digi-user-avatar">
                        @if($currentUser->profile_photo_url)
                            <img src="{{ asset($currentUser->profile_photo_url) }}" alt="{{ $currentUser->name }}" class="digi-user-avatar-img">
                        @else
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        @endif
                    </span>
                    <div class="digi-user-info">
                        <span class="digi-user-name">{{ $currentUser->name }}</span>
                        <span class="digi-user-role">{{ $currentUser->jabatan ?: ucfirst($currentUser->role) }}</span>
                    </div>
                </a>
            @endif
        </div>
    </nav>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- MAIN CONTENT -->
    <main class="digi-main-container">
        @if(session('success'))
            @php
                $isWelcomeMsg = str_contains(strtolower(session('success')), 'selamat datang') || str_contains(strtolower(session('success')), 'login') || str_contains(strtolower(session('success')), 'beralih ke akun');
            @endphp
            @if(!$isWelcomeMsg)
            <div class="digi-alert digi-alert-success">
                <span style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {{ session('success') }}
                </span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:16px;">&times;</button>
            </div>
            @endif
        @endif

        @if(session('error'))
            <div class="digi-alert digi-alert-error">
                <span style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    {{ session('error') }}
                </span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:16px;">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="digi-alert digi-alert-error" style="flex-direction: column; align-items: flex-start; gap: 8px; border-left: 4px solid #ef4444; background: #fff5f5; padding: 14px 18px; margin-bottom: 20px;">
                <div style="display:flex; width:100%; justify-content:space-between; align-items:center;">
                    <strong style="display:inline-flex; align-items:center; gap:8px; color: #b91c1c; font-size: 14.5px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        Periksa kembali data input Anda (Terdapat {{ $errors->count() }} kolom/isian yang perlu diperbaiki):
                    </strong>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:18px; color: #991b1b; line-height: 1;">&times;</button>
                </div>
                <div style="font-size: 12.5px; color: #7f1d1d; margin-top: -2px;">
                    Kolom yang bermasalah telah ditandai dengan <strong>bingkai merah</strong> pada formulir di bawah. Silakan periksa keterangan spesifik berikut:
                </div>
                <ul style="margin: 4px 0 0 20px; padding: 0; font-size: 13px; color: #991b1b; line-height: 1.6;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="digi-footer">
        2026© PT United Tractors Tbk - Site Satui
    </footer>

    <!-- Flash Notification Data Container for Safe Client-Side Parsing -->
    @php
        $flashSuccess = session('success');
        $flashError = session('error');
        $flashErrorsList = $errors->any() ? $errors->all() : [];
        $isLoginMsg = $flashSuccess && (str_contains(strtolower($flashSuccess), 'selamat datang') || str_contains(strtolower($flashSuccess), 'login') || str_contains(strtolower($flashSuccess), 'beralih ke akun'));
        $isApprovedMsg = $flashSuccess && (str_contains(strtolower($flashSuccess), 'disetujui') || str_contains(strtolower($flashSuccess), 'realisasi'));
        $isSubmittedMsg = $flashSuccess && (str_contains(strtolower($flashSuccess), 'diajukan') || str_contains(strtolower($flashSuccess), 'dikirim') || str_contains(strtolower($flashSuccess), 'berhasil dibuat'));
        $swalTitle = $isApprovedMsg ? 'Pengajuan Disetujui!' : ($isSubmittedMsg ? 'Pengajuan Berhasil Dikirim!' : 'Berhasil!');
        $isRejectedMsg = $flashError && (str_contains(strtolower($flashError), 'ditolak') || str_contains(strtolower($flashError), 'dibatalkan'));
        $swalErrTitle = $isRejectedMsg ? 'Pengajuan Ditolak!' : 'Proses Gagal / Ditolak';
    @endphp
    <div id="digiFlashData" style="display: none;"
         data-success="{{ $flashSuccess ? e($flashSuccess) : '' }}"
         data-success-title="{{ e($swalTitle) }}"
         data-is-login="{{ $isLoginMsg ? '1' : '0' }}"
         data-error="{{ $flashError ? e($flashError) : '' }}"
         data-error-title="{{ e($swalErrTitle) }}"
         data-errors="{{ !empty($flashErrorsList) ? e(json_encode($flashErrorsList)) : '' }}"></div>

    <script>
    function toggleNotifMenu() {
        const menu = document.getElementById('digiNotifMenu');
        if (menu) {
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
    }
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('digiNotifMenu');
        if (menu && !e.target.closest('#digiNotifMenu') && !e.target.closest('button[onclick="toggleNotifMenu()"]')) {
            menu.style.display = 'none';
        }
    });

    // Global DigiFuel SweetAlert2 Helper & Automatic Flash / Confirm Integration
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal === 'undefined') return;

        const flashElem = document.getElementById('digiFlashData');
        if (flashElem) {
            const successMsg = flashElem.dataset.success;
            const successTitle = flashElem.dataset.successTitle;
            const isLoginMsg = flashElem.dataset.isLogin === '1';

            if (successMsg) {
                if (isLoginMsg) {
                    const isMobile = window.innerWidth <= 768;
                    Swal.fire({
                        toast: true,
                        position: isMobile ? 'bottom' : 'top-end',
                        icon: 'success',
                        title: successMsg,
                        showConfirmButton: false,
                        showCloseButton: true,
                        timer: 4000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'digi-toast-popup'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                            toast.style.cursor = 'pointer';
                            toast.title = 'Klik untuk menutup';
                            toast.addEventListener('click', (e) => {
                                Swal.close();
                            });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: successTitle,
                        text: successMsg,
                        confirmButtonText: 'OK, Mengerti',
                        confirmButtonColor: '#0284c7',
                        timer: 4500,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__zoomIn animate__faster'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__zoomOut animate__faster'
                        },
                        customClass: {
                            popup: 'digi-swal-popup digi-swal-success-popup'
                        }
                    });
                }
            }

            const errorMsg = flashElem.dataset.error;
            const errorTitle = flashElem.dataset.errorTitle;
            if (errorMsg) {
                Swal.fire({
                    icon: 'error',
                    title: errorTitle,
                    text: errorMsg,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#ef4444',
                    customClass: {
                        popup: 'digi-swal-popup'
                    }
                });
            }

            const errorsRaw = flashElem.dataset.errors;
            if (errorsRaw) {
                try {
                    const errList = JSON.parse(errorsRaw);
                    if (Array.isArray(errList) && errList.length > 0) {
                        const htmlList = errList.map(e => `<li>${e}</li>`).join('');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Periksa Kembali Input Anda',
                            html: `<ul style="text-align:left; margin: 6px 0 0 16px; font-size: 13.5px; color: #334155; line-height: 1.5;">${htmlList}</ul>`,
                            confirmButtonText: 'Perbaiki Data',
                            confirmButtonColor: '#f59e0b',
                            customClass: {
                                popup: 'digi-swal-popup'
                            }
                        });
                    }
                } catch (e) {}
            }
        }

        // Upgrade native onsubmit="return confirm('...')" forms into animated SweetAlert2 confirmation dialogs
        document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function(form) {
            const rawOnsubmit = form.getAttribute('onsubmit') || '';
            const match = rawOnsubmit.match(/confirm\(['"](.+?)['"]\)/);
            if (!match) return;

            const confirmMessage = match[1];
            form.removeAttribute('onsubmit');

            form.addEventListener('submit', function(e) {
                if (form.dataset.swalConfirmed === 'true') {
                    return true;
                }
                e.preventDefault();

                const isDanger = /hapus|tolak|batal|nonaktif/i.test(confirmMessage);
                Swal.fire({
                    icon: isDanger ? 'warning' : 'question',
                    title: isDanger ? 'Konfirmasi Tindakan' : 'Lanjutkan Proses?',
                    text: confirmMessage,
                    showCancelButton: true,
                    confirmButtonText: isDanger ? 'Ya, Lanjutkan' : 'Ya, Proses',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: isDanger ? '#ef4444' : '#0284c7',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    customClass: {
                        popup: 'digi-swal-popup'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.dataset.swalConfirmed = 'true';
                        form.submit();
                    }
                });
            });
        });

        // Initialize Universal Table Sorter & Column Filter on all .digi-table
        initDigiTableSorters();
    });

    // =========================================================================
    // UNIVERSAL DIGIFUEL TABLE COLUMN SORTER & QUICK FILTER (A-Z / 1-9)
    // =========================================================================
    function parseDigiSortVal(raw) {
        if (!raw) return { type: 'empty', val: '' };
        raw = raw.trim();

        // 1. Currency e.g. "Rp 27.000", "Rp 100.000 / L", "Rp 28.500"
        if (/^Rp\s*/i.test(raw)) {
            const clean = raw.replace(/^Rp\s*/i, '').replace(/\/.*$/, '').replace(/\./g, '').replace(/,/g, '.').trim();
            const num = parseFloat(clean);
            if (!isNaN(num)) return { type: 'num', val: num };
        }

        // 2. Numeric with unit (e.g. "10.00 KM/L", "±10%", "80 L", "5 unit")
        const numWithUnit = raw.match(/^[±\+]?\s*([0-9]+(?:[.,][0-9]+)?)\s*(?:km\/l|jam\/l|l\/jam|%|l|liter|unit|data)?$/i);
        if (numWithUnit) {
            const stdNum = parseFloat(numWithUnit[1].replace(',', '.'));
            if (!isNaN(stdNum)) return { type: 'num', val: stdNum };
        }

        // 3. Indonesian standard thousand separators (e.g. 1.500 or 25.000,5)
        if (/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(raw)) {
            const stdNum = parseFloat(raw.replace(/\./g, '').replace(',', '.'));
            if (!isNaN(stdNum)) return { type: 'num', val: stdNum };
        }

        // 4. Plain integer or decimal
        const plainNum = parseFloat(raw);
        if (!isNaN(plainNum) && String(plainNum) === raw) {
            return { type: 'num', val: plainNum };
        }

        // 5. Date detection (DD/MM/YYYY or YYYY-MM-DD)
        const dmy = raw.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (dmy) {
            const ts = new Date(`${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}`).getTime();
            if (!isNaN(ts)) return { type: 'date', val: ts };
        }
        const ymd = raw.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
        if (ymd) {
            const ts = new Date(`${ymd[1]}-${ymd[2].padStart(2, '0')}-${ymd[3].padStart(2, '0')}`).getTime();
            if (!isNaN(ts)) return { type: 'date', val: ts };
        }

        // 6. Alphanumeric text
        return { type: 'str', val: raw.toLowerCase() };
    }

    function initDigiTableSorters() {
        document.querySelectorAll('.digi-table').forEach(function(table) {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const thead = table.querySelector('thead');
            if (!thead) return;

            const ths = Array.from(thead.querySelectorAll('tr:first-child th'));
            if (ths.length === 0) return;

            const originalRows = Array.from(tbody.querySelectorAll('tr')).filter(tr => !tr.querySelector('td[colspan]'));
            let currentSortedCol = -1;
            let currentSortedDir = 'none';

            ths.forEach(function(th, colIdx) {
                const rawTitle = (th.textContent || '').trim();
                const lowerTitle = rawTitle.toLowerCase();
                if (lowerTitle === 'aksi' || lowerTitle === 'action' || th.classList.contains('no-sort')) {
                    return;
                }

                const cleanTitle = rawTitle.replace(/[↕▲▼]/g, '').trim() || `Kolom ${colIdx + 1}`;
                th.classList.add('digi-sortable-th');
                th.setAttribute('title', `Klik untuk mengurutkan ${cleanTitle} (A-Z / 1-9)`);

                th.querySelectorAll('.sort-icon').forEach(el => el.remove());

                if (!th.querySelector('.digi-sort-icon-box')) {
                    const iconBox = document.createElement('span');
                    iconBox.className = 'digi-sort-icon-box';
                    iconBox.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="digi-sort-svg-neutral"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>`;
                    th.appendChild(iconBox);
                }

                th.addEventListener('click', function() {
                    // 3-way toggle: default -> asc (A-Z / 1-9) -> desc (Z-A / 9-1) -> reset
                    if (currentSortedCol !== colIdx) {
                        applySort(colIdx, 'asc');
                    } else if (currentSortedDir === 'asc') {
                        applySort(colIdx, 'desc');
                    } else {
                        resetSort();
                    }
                });
            });

            function applySort(colIdx, direction) {
                const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => !tr.querySelector('td[colspan]'));
                if (rows.length === 0) return;

                currentSortedCol = colIdx;
                currentSortedDir = direction;

                rows.sort(function(a, b) {
                    const cellA = a.children[colIdx] ? a.children[colIdx].textContent : '';
                    const cellB = b.children[colIdx] ? b.children[colIdx].textContent : '';

                    const valA = parseDigiSortVal(cellA);
                    const valB = parseDigiSortVal(cellB);

                    let cmp = 0;
                    if (valA.type === 'num' && valB.type === 'num') {
                        cmp = valA.val - valB.val;
                    } else if (valA.type === 'date' && valB.type === 'date') {
                        cmp = valA.val - valB.val;
                    } else {
                        cmp = String(valA.val).localeCompare(String(valB.val), undefined, { numeric: true, sensitivity: 'base' });
                    }

                    return direction === 'asc' ? cmp : -cmp;
                });

                rows.forEach(function(row, idx) {
                    tbody.appendChild(row);
                    const firstTd = row.children[0];
                    if (firstTd && !isNaN(parseInt(firstTd.textContent.trim())) && !firstTd.querySelector('input, select, button')) {
                        firstTd.textContent = idx + 1;
                    }
                });

                ths.forEach(function(th, idx) {
                    const iconBox = th.querySelector('.digi-sort-icon-box');
                    if (!iconBox) return;

                    if (idx === colIdx) {
                        th.classList.add('is-sorted');
                        if (direction === 'asc') {
                            iconBox.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><polyline points="18 15 12 9 6 15"></polyline></svg><span class="digi-sort-tag">A-Z</span>`;
                        } else {
                            iconBox.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><polyline points="6 9 12 15 18 9"></polyline></svg><span class="digi-sort-tag">Z-A</span>`;
                        }
                    } else {
                        th.classList.remove('is-sorted');
                        iconBox.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="digi-sort-svg-neutral"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>`;
                    }
                });
            }

            function resetSort() {
                originalRows.forEach(function(row, idx) {
                    tbody.appendChild(row);
                    const firstTd = row.children[0];
                    if (firstTd && !isNaN(parseInt(firstTd.textContent.trim())) && !firstTd.querySelector('input, select, button')) {
                        firstTd.textContent = idx + 1;
                    }
                });

                currentSortedCol = -1;
                currentSortedDir = 'none';

                ths.forEach(function(th) {
                    th.classList.remove('is-sorted');
                    const iconBox = th.querySelector('.digi-sort-icon-box');
                    if (iconBox) {
                        iconBox.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="digi-sort-svg-neutral"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>`;
                    }
                });
            }
        });
    }
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navGroup = document.querySelector('.digi-nav-links');
        const navPill = navGroup ? navGroup.querySelector('.digi-nav-pill') : null;
        const navItems = navGroup ? Array.from(navGroup.querySelectorAll('.digi-nav-link')) : [];

        if (!navGroup || !navPill || navItems.length === 0) return;

        function movePill(target, animate = true) {
            if (!target) return;
            if (!animate) {
                navPill.style.transition = 'none';
            }
            navPill.style.width = target.offsetWidth + 'px';
            navPill.style.height = target.offsetHeight + 'px';
            navPill.style.transform = `translate3d(${target.offsetLeft}px, ${target.offsetTop}px, 0)`;
            navPill.style.opacity = '1';

            if (!animate) {
                navPill.offsetHeight; // force reflow
                navPill.style.transition = '';
            }
        }

        function updateNavScrollFade() {
            const maxScroll = navGroup.scrollWidth - navGroup.clientWidth;
            if (maxScroll <= 2) {
                navGroup.style.setProperty('--nav-fade-left', '0px');
                navGroup.style.setProperty('--nav-fade-right', '0px');
                return;
            }
            const leftFade = navGroup.scrollLeft > 4 ? '20px' : '0px';
            const rightFade = navGroup.scrollLeft < (maxScroll - 4) ? '20px' : '0px';
            navGroup.style.setProperty('--nav-fade-left', leftFade);
            navGroup.style.setProperty('--nav-fade-right', rightFade);
        }

        function syncActivePill(scrollIntoView = false) {
            const current = navGroup.querySelector('.digi-nav-link.active');
            if (!current) {
                updateNavScrollFade();
                return;
            }
            navGroup.classList.add('has-pill');
            requestAnimationFrame(function() {
                movePill(current, false);
                if (scrollIntoView && navGroup.scrollWidth > navGroup.clientWidth) {
                    current.scrollIntoView({ behavior: 'auto', inline: 'center', block: 'nearest' });
                }
                updateNavScrollFade();
            });
        }

        navGroup.addEventListener('scroll', updateNavScrollFade, { passive: true });
        syncActivePill(true);

        window.addEventListener('load', function() {
            syncActivePill(true);
        });

        window.addEventListener('pageshow', function() {
            syncActivePill(false);
        });

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function() {
                syncActivePill(false);
            });
        }

        window.addEventListener('resize', function() {
            syncActivePill(false);
        });

        navItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;

                const targetUrl = new URL(item.href, window.location.origin);
                const isSameUrl = targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search;

                navGroup.classList.add('has-pill');
                navItems.forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                movePill(item, true);

                if (!isSameUrl) {
                    e.preventDefault();
                    setTimeout(function() {
                        window.location.href = item.href;
                    }, 220);
                }
            });
        });
    });
    </script>
    @yield('scripts')
</body>
</html>
