<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fuel Request') - DigiFuel PT United Tractors Tbk Site Satui</title>
    <meta name="description" content="Sistem Pengajuan, Persetujuan Digital, dan Evaluasi Konsumsi BBM PT United Tractors Tbk Site Satui">
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}?v={{ file_exists(public_path('css/digifuel.css')) ? filemtime(public_path('css/digifuel.css')) : time() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ut_logo_badge.svg') }}">
    <!-- SweetAlert2 Library for Animated Success, Reject & Confirmation Popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Canvas Confetti for Celebration Checkmark Animation -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <style>
        body {
            background-image: url('{{ \App\Models\SystemBackground::getActiveImageUrl() }}');
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
            <a href="{{ route('dashboard') }}" class="digi-logo-badge" title="United Tractors Satui">
                <img src="{{ asset('images/ut_logo_badge.svg') }}" alt="United Tractors">
            </a>
            <span class="digi-status-indicator"><span class="digi-status-dot"></span> online</span>
        </div>

        <div class="digi-nav-links">
            <a href="{{ route('dashboard') }}" class="digi-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            @if($isAdmin)
                <a href="{{ route('fuel.create') }}" class="digi-nav-link {{ request()->routeIs('fuel.create') ? 'active' : '' }}">Form Request</a>
                <a href="{{ route('approval.index') }}" class="digi-nav-link {{ request()->routeIs('approval.*') ? 'active' : '' }}">Pengajuan</a>
                <a href="{{ route('history.index') }}" class="digi-nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">History</a>
                <a href="{{ route('evaluation.index') }}" class="digi-nav-link {{ request()->routeIs('evaluation.*') ? 'active' : '' }}">Evaluasi BBM</a>
                <a href="{{ route('admin.master') }}" class="digi-nav-link {{ request()->routeIs('admin.master*') ? 'active' : '' }}">Master Data</a>
                <a href="{{ route('profile.index') }}" class="digi-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profil</a>
            @elseif($isApprover)
                <a href="{{ route('approval.index') }}" class="digi-nav-link {{ request()->routeIs('approval.*') ? 'active' : '' }}">Pengajuan</a>
                <a href="{{ route('history.index') }}" class="digi-nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">History</a>
                <a href="{{ route('profile.index') }}" class="digi-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profil</a>
            @else
                <a href="{{ route('fuel.create') }}" class="digi-nav-link {{ request()->routeIs('fuel.create') ? 'active' : '' }}">Form Request</a>
                <a href="{{ route('history.index') }}" class="digi-nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">History</a>
                <a href="{{ route('profile.index') }}" class="digi-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profil</a>
            @endif
        </div>

        <div class="digi-nav-right">
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
                <a href="{{ route('profile.index') }}" class="digi-user-profile-badge" title="Buka Profil: {{ $currentUser->name }} ({{ $currentUser->jabatan }})">
                    <span class="digi-user-avatar">
                        @if($currentUser->profile_photo_url)
                            <img src="{{ asset($currentUser->profile_photo_url) }}" alt="{{ $currentUser->name }}" class="digi-user-avatar-img">
                        @else
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        @endif
                    </span>
                    <div class="digi-user-info">
                        <span class="digi-user-name">{{ $currentUser->name }}</span>
                        <span class="digi-user-role">{{ $currentUser->jabatan }}</span>
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
            <div class="digi-alert digi-alert-success">
                <span style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {{ session('success') }}
                </span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:16px;">&times;</button>
            </div>
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
            <div class="digi-alert digi-alert-error" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <div style="display:flex; width:100%; justify-content:space-between; align-items:center;">
                    <strong style="display:inline-flex; align-items:center; gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Periksa kembali data input Anda:
                    </strong>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:16px;">&times;</button>
                </div>
                <ul style="margin: 4px 0 0 18px; padding: 0; font-size: 13px;">
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
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: successMsg,
                        showConfirmButton: false,
                        timer: 3500,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'digi-toast-popup'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
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
    });
    </script>
    @yield('scripts')
</body>
</html>
