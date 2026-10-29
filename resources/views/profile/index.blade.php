@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="digi-profile-layout">
    <!-- Panel Kiri: Identitas Pengguna -->
    <div class="digi-profile-left">
        <h2 class="digi-profile-name">{{ $user->name }}</h2>
        <span class="digi-badge digi-badge-blue" style="font-size: 13px; padding: 6px 18px;">
            {{ $user->jabatan }}
        </span>
        @if($user->nrp)
            <div style="margin-top: 12px; font-size: 13px; color: #475569; font-weight: 600;">
                NRP: {{ $user->nrp }}
            </div>
        @endif
        <div style="margin-top: 6px; font-size: 12px; color: #64748b;">
            {{ $user->company ?? 'PT UNITED TRACTORS Tbk - SITE SATUI' }}
        </div>
    </div>

    <!-- Panel Kanan: Profile Detail -->
    <div class="digi-profile-right">
        <h1 class="digi-profile-header">Profile Detail</h1>

        <div class="digi-profile-row">
            <div class="digi-profile-label">
                Username 
                <span style="display:inline-flex; width:16px; height:16px; border-radius:50%; background:#94a3b8; color:#fff; font-size:10px; align-items:center; justify-content:center; font-weight:bold;">i</span>
            </div>
            <div class="digi-profile-value">
                {{ $user->username ?? $user->name }}
            </div>
        </div>

        <div class="digi-profile-row">
            <div class="digi-profile-label">
                NRP / Employee ID 
                <span style="display:inline-flex; width:16px; height:16px; border-radius:50%; background:#94a3b8; color:#fff; font-size:10px; align-items:center; justify-content:center; font-weight:bold;">i</span>
            </div>
            <div class="digi-profile-value">
                {{ $user->nrp ?? $user->employee?->nrp ?? '-' }}
                @if($user->employee)
                    <span class="digi-badge digi-badge-gray" style="margin-left: 8px;">Sumber: {{ strtoupper($user->employee->source) }}</span>
                @endif
            </div>
        </div>

        <div class="digi-profile-row">
            <div class="digi-profile-label">
                Jabatan 
                <span style="display:inline-flex; width:16px; height:16px; border-radius:50%; background:#94a3b8; color:#fff; font-size:10px; align-items:center; justify-content:center; font-weight:bold;">i</span>
            </div>
            <div class="digi-profile-value">
                {{ $user->jabatan }}
            </div>
        </div>

        <div class="digi-profile-row">
            <div class="digi-profile-label">
                Tanggal Dibuat 
                <span style="display:inline-flex; width:16px; height:16px; border-radius:50%; background:#94a3b8; color:#fff; font-size:10px; align-items:center; justify-content:center; font-weight:bold;">i</span>
            </div>
            <div class="digi-profile-value">
                {{ $user->created_at ? $user->created_at->format('d/m/Y') : '-' }}
            </div>
        </div>

        <div class="digi-profile-row" style="align-items: flex-start;">
            <div class="digi-profile-label" style="padding-top: 6px;">
                Upload Tanda Tangan 
                <span style="display:inline-flex; width:16px; height:16px; border-radius:50%; background:#94a3b8; color:#fff; font-size:10px; align-items:center; justify-content:center; font-weight:bold;">i</span>
            </div>
            <div class="digi-profile-value">
                @if($user->signature_url)
                    <!-- Pratinjau Tanda Tangan -->
                    <div id="signature-preview-box">
                        <img src="{{ asset($user->signature_url) }}" alt="Tanda Tangan" class="digi-signature-preview">
                        <button type="button" class="btn-digi btn-digi-yellow" style="padding: 7px 18px; font-size: 13px; margin-top: 8px;" onclick="showUploadForm()">
                            Ubah TTD
                        </button>
                    </div>

                    <form id="signature-upload-form" action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data" style="display: none; margin-top: 10px;">
                        @csrf
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">Format wajib: PNG atau JPEG (Maks. 2MB). Disimpan di storage privat.</div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="file" name="signature" accept="image/png,image/jpeg" class="digi-input" style="max-width: 280px; padding: 6px;" required>
                            <button type="submit" class="btn-digi btn-digi-blue" style="padding: 7px 18px; font-size: 13px;">Upload</button>
                            <button type="button" class="btn-digi btn-digi-gray" style="padding: 7px 12px; font-size: 13px;" onclick="hideUploadForm()">Batal</button>
                        </div>
                    </form>
                @else
                    <!-- Form Upload Langsung jika belum ada TTD (Slide 3) -->
                    <form action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">Format wajib: PNG atau JPEG (Maks. 2MB).</div>
                        <div style="margin-bottom: 12px;">
                            <input type="file" name="signature" accept="image/png,image/jpeg" class="digi-input" style="max-width: 320px; padding: 6px;" required>
                        </div>
                        <button type="submit" class="btn-digi btn-digi-blue" style="padding: 8px 24px; font-size: 13px;">
                            Upload
                        </button>
                    </form>
                @endif

                @if(isset($signatures) && $signatures->count() > 0)
                    <div style="margin-top: 16px; font-size: 12px; color: #64748b;">
                        <strong>Riwayat Tanda Tangan Tersimpan ({{ $signatures->count() }}):</strong>
                        @foreach($signatures->take(3) as $sig)
                            <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                <span>• {{ $sig->created_at?->format('d/m/Y H:i') }}</span>
                                <span class="digi-badge {{ $sig->is_active ? 'digi-badge-green' : 'digi-badge-gray' }}" style="padding: 2px 8px; font-size: 10.5px;">
                                    {{ $sig->is_active ? 'Aktif' : 'Historis (Nonaktif)' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Tombol Logout Merah di kiri bawah (Slide 4, 8) -->
        <div style="margin-top: 36px;">
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-digi btn-digi-red" style="padding: 8px 26px;">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showUploadForm() {
    document.getElementById('signature-preview-box').style.display = 'none';
    document.getElementById('signature-upload-form').style.display = 'block';
}

function hideUploadForm() {
    document.getElementById('signature-upload-form').style.display = 'none';
    document.getElementById('signature-preview-box').style.display = 'block';
}
</script>
@endsection
