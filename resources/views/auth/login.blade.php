<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DigiFuel PT United Tractors Tbk Site Satui</title>
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ut_logo_badge.svg') }}">
</head>
<body style="justify-content: center; align-items: center; padding: 20px;">
    <div class="digi-card" style="width: 100%; max-width: 460px; padding: 38px 36px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="display: inline-flex; background: #f8fafc; padding: 8px 18px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                <img src="{{ asset('images/ut_logo_badge.svg') }}" alt="United Tractors" style="height: 34px; width: auto;">
            </div>
            <h1 class="digi-form-title" style="font-size: 22px; margin-bottom: 4px;">DigiFuel</h1>
            <p class="digi-form-subtitle" style="font-weight: 600; color: #334155;">Fuel Request Management System</p>
            <p style="font-size: 12px; color: #64748b; margin-top: 4px;">PT UNITED TRACTORS Tbk - SITE SATUI</p>
        </div>

        @if(session('success'))
            <div class="digi-alert digi-alert-success" style="font-size: 13px; padding: 10px 14px;">
                <span>✓ {{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="digi-alert digi-alert-error" style="font-size: 13px; padding: 10px 14px;">
                <span>✕ {{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" onsubmit="handleLoginSubmit(this)">
            @csrf
            <div class="digi-form-box" style="padding: 20px 22px; margin-bottom: 18px;">
                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Username / Email / NRP <span class="digi-required">*</span></label>
                    <input type="text" name="login" class="digi-input" placeholder="Masukkan username, email, atau NRP" value="{{ old('login', 'adji') }}" required autofocus>
                </div>

                <div class="digi-form-group" style="margin-bottom: 16px;">
                    <label class="digi-form-label">Password <span class="digi-required">*</span></label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" name="password" id="loginPassword" class="digi-input" placeholder="Masukkan password" value="password123" required style="padding-right: 72px;">
                        <button type="button" onclick="togglePassword()" id="togglePwdBtn" style="position: absolute; right: 8px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 5px; padding: 4px 8px; font-size: 11px; font-weight: 600; color: #475569; cursor: pointer;">
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit" id="loginSubmitBtn" class="btn-digi btn-digi-blue" style="width: 100%; padding: 11px; font-size: 14.5px;">
                    Login ke DigiFuel
                </button>
            </div>
        </form>

        <!-- Quick Demo Accounts Reference -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; font-size: 12px; color: #475569;">
            <div style="font-weight: 700; color: #1e293b; margin-bottom: 6px;">Akun Pengujian Cepat (Password: <code>password123</code>):</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px;">
                <div>• Employee: <strong>adji</strong></div>
                <div>• SPV: <strong>aklis</strong></div>
                <div>• Dept Head: <strong>Pdh-kusan</strong></div>
                <div>• Vendor: <strong>vendor_sudan</strong></div>
                <div>• Fuel Man: <strong>adya</strong></div>
                <div>• Admin: <strong>admin</strong></div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('loginPassword');
        const btn = document.getElementById('togglePwdBtn');
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = 'Sembunyikan';
        } else {
            input.type = 'password';
            btn.textContent = 'Lihat';
        }
    }
    function handleLoginSubmit(form) {
        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'Memproses Login...';
    }
    </script>
</body>
</html>

