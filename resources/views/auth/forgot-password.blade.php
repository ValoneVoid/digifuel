<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - FuelLink PT United Tractors Tbk Site Satui</title>
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}?v={{ file_exists(public_path('css/digifuel.css')) ? filemtime(public_path('css/digifuel.css')) : time() }}">
    @if(file_exists(public_path('css/digifuel.css')))
        <style id="digifuel-core-css">{!! file_get_contents(public_path('css/digifuel.css')) !!}</style>
    @endif
    <link rel="icon" type="image/png" href="{{ asset('images/fuellink_logo.png') }}">
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
    </style>
</head>
<body style="justify-content: center; align-items: center; padding: 20px;">
    <div class="digi-card" style="width: 100%; max-width: 460px; min-height: auto; padding: 36px 34px;">
        <div style="text-align: center; margin-bottom: 22px;">
            <div style="display: inline-flex; background: #ffffff; padding: 6px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.12); margin-bottom: 12px;">
                <img src="{{ asset('images/fuellink_logo.png') }}" alt="FuelLink" style="height: 68px; width: 68px; border-radius: 12px; object-fit: contain;">
            </div>
            <h1 class="digi-form-title" style="font-size: 22px; margin-bottom: 4px; font-weight: 800; color: #0f172a;">Ajukan Reset Password</h1>
            <p class="digi-form-subtitle" style="font-size: 13px; color: #64748b; line-height: 1.5;">
                Masukkan identitas akun Anda. Permintaan reset akan dikirim ke notifikasi <strong>Administrator</strong>.
            </p>
        </div>

        @if($errors->any())
            <div class="digi-alert digi-alert-error" style="font-size: 13px; padding: 10px 14px; display:flex; align-items:center; gap:8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('password.request.submit') }}" method="POST" onsubmit="handleForgotSubmit(this)">
            @csrf
            <div class="digi-form-box" style="padding: 20px 22px; margin-bottom: 16px;">
                <div class="digi-form-group" style="margin-bottom: 14px;">
                    <label class="digi-form-label">Username / Email / NRP <span class="digi-required">*</span></label>
                    <input type="text" name="login" class="digi-input" placeholder="Masukkan username, email, atau NRP Anda" value="{{ old('login') }}" required autofocus>
                </div>

                <div class="digi-form-group" style="margin-bottom: 18px;">
                    <label class="digi-form-label">Catatan / No. WA (Opsional)</label>
                    <input type="text" name="note" class="digi-input" placeholder="Contoh: Mohon kirim password baru ke WA 0812..." value="{{ old('note') }}">
                </div>

                <button type="submit" id="forgotSubmitBtn" class="btn-digi btn-digi-blue" style="width: 100%; padding: 11px; font-size: 14px;">
                    Kirim Permintaan ke Admin
                </button>
            </div>
        </form>

        <div style="text-align: center;">
            <a href="{{ route('login') }}" style="font-size: 13px; font-weight: 600; color: #0284c7; text-decoration: none;">
                &larr; Kembali ke Halaman Login
            </a>
        </div>
    </div>

    <script>
    function handleForgotSubmit(form) {
        const btn = document.getElementById('forgotSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="login-spinner"></span> Mengirim Permintaan...';
    }
    </script>
</body>
</html>

