<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DigiFuel PT United Tractors Tbk Site Satui</title>
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}?v={{ file_exists(public_path('css/digifuel.css')) ? filemtime(public_path('css/digifuel.css')) : time() }}">
    @if(file_exists(public_path('css/digifuel.css')))
        <style id="digifuel-core-css">{!! file_get_contents(public_path('css/digifuel.css')) !!}</style>
    @endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ut_logo_badge.svg') }}">
    <style>
        body {
            background-image: url('{{ \App\Models\SystemBackground::getActiveImageUrl() }}');
        }
    </style>
</head>
<body style="justify-content: center; align-items: center; padding: 20px;">
    <div class="digi-card" style="width: 100%; max-width: 460px; min-height: auto; padding: 38px 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="display: inline-flex; background: #ffffff; padding: 8px 18px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                <img src="{{ asset('images/LOGO UT.png') }}" alt="United Tractors" style="height: 38px; width: auto; object-fit: contain;">
            </div>
            <h1 class="digi-form-title" style="font-size: 22px; margin-bottom: 4px;">DigiFuel</h1>
            <p class="digi-form-subtitle" style="font-weight: 600; color: #334155;">Fuel Request Management System</p>
            <p style="font-size: 12px; color: #64748b; margin-top: 4px;">PT UNITED TRACTORS Tbk - SITE SATUI</p>
        </div>

        @if(session('success'))
            <div class="digi-alert digi-alert-success" style="font-size: 13px; padding: 10px 14px; display:flex; align-items:center; gap:8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="digi-alert digi-alert-error" style="font-size: 13px; padding: 10px 14px; display:flex; align-items:center; gap:8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" onsubmit="handleLoginSubmit(this)">
            @csrf
            <div class="digi-form-box" style="padding: 20px 22px; margin-bottom: 18px;">
                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Username / Email / NRP <span class="digi-required">*</span></label>
                    <input type="text" name="login" class="digi-input" placeholder="Masukkan username, email, atau NRP" value="{{ old('login') }}" autocomplete="username" required autofocus>
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Password <span class="digi-required">*</span></label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" name="password" id="loginPassword" class="digi-input" placeholder="Masukkan password" autocomplete="current-password" required style="padding-right: 42px;">
                        <button type="button" onclick="togglePassword()" id="togglePwdBtn" title="Lihat Password" aria-label="Lihat Password" style="position: absolute; right: 10px; background: transparent; border: none; padding: 4px; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                            <svg id="eyeIconOpen" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eyeIconClosed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="loginSubmitBtn" class="btn-digi btn-digi-blue" style="width: 100%; padding: 11px; font-size: 14.5px;">
                    Login
                </button>
            </div>
        </form>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('loginPassword');
        const btn = document.getElementById('togglePwdBtn');
        const eyeOpen = document.getElementById('eyeIconOpen');
        const eyeClosed = document.getElementById('eyeIconClosed');
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
            btn.title = 'Sembunyikan Password';
        } else {
            input.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
            btn.title = 'Lihat Password';
        }
    }
    function handleLoginSubmit(form) {
        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="login-spinner"></span> Memproses Login...';
    }
    </script>
</body>
</html>

