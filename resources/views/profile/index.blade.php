@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    if ($initials === '') {
        $initials = 'UT';
    }
    $hasProfileErrors = $errors->hasAny(['name', 'username', 'email', 'phone', 'nrp', 'jabatan', 'company', 'password']);
@endphp

<div class="digi-profile-layout">
    <!-- Panel Kiri: Foto Profil & Identitas Singkat -->
    <div class="digi-profile-left">
        <!-- Avatar / Foto Profil -->
        <div class="digi-avatar-wrapper" title="{{ $user->profile_photo_url ? 'Klik untuk melihat foto ukuran penuh' : 'Belum ada foto profil' }}">
            @if($user->profile_photo_url)
                <img src="{{ asset($user->profile_photo_url) }}"
                     alt="Foto Profil {{ $user->name }}"
                     class="digi-avatar-img"
                     onclick="openPhotoModal()">
            @else
                <span class="digi-avatar-placeholder">{{ $initials }}</span>
            @endif
        </div>

        <!-- Tombol CRUD Foto Profil (Lihat, Tambah/Ganti, Hapus) -->
        <div class="digi-photo-actions">
            @if($user->profile_photo_url)
                <button type="button"
                        class="btn-digi btn-digi-gray"
                        style="padding: 6px 12px; font-size: 12px;"
                        onclick="openPhotoModal()">
                    Lihat Foto
                </button>
                <button type="button"
                        class="btn-digi btn-digi-blue"
                        style="padding: 6px 12px; font-size: 12px;"
                        onclick="togglePhotoUploadForm(true)">
                    Ganti Foto
                </button>
                <button type="button"
                        class="btn-digi btn-digi-red"
                        style="padding: 6px 12px; font-size: 12px;"
                        onclick="openDeletePhotoModal()">
                    Hapus
                </button>
            @else
                <button type="button"
                        class="btn-digi btn-digi-blue"
                        style="padding: 7px 16px; font-size: 12.5px;"
                        onclick="togglePhotoUploadForm(true)">
                    + Tambah Foto Profil
                </button>
            @endif
        </div>

        <!-- Form Upload / Ganti Foto Profil -->
        <form id="photo-upload-form"
              action="{{ route('profile.photo.upload') }}"
              method="POST"
              enctype="multipart/form-data"
              style="display: {{ $errors->has('profile_photo') ? 'block' : 'none' }}; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin-bottom: 16px; text-align: left;">
            @csrf
            <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                {{ $user->profile_photo_url ? 'Ganti Foto Profil' : 'Tambah Foto Profil Baru' }}
            </div>
            <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                Format: PNG, JPG, JPEG, WEBP (Maks. 3MB)
            </div>

            <!-- Live Preview Sebelum Simpan -->
            <div id="new-photo-preview-wrap" style="display: none; text-align: center; margin-bottom: 10px;">
                <img id="new-photo-preview-img"
                     src=""
                     alt="Pratinjau Foto Baru"
                     style="width: 84px; height: 84px; border-radius: 50%; object-fit: cover; border: 2px solid #0284c7; margin: 0 auto;">
                <div style="font-size: 10.5px; color: #0284c7; margin-top: 4px; font-weight: 600;">Pratinjau Foto Baru</div>
            </div>

            <input type="file"
                   name="profile_photo"
                   id="profile_photo_input"
                   accept="image/png,image/jpeg,image/webp"
                   class="digi-input"
                   style="font-size: 11.5px; padding: 6px; margin-bottom: 10px;"
                   onchange="previewSelectedPhoto(this)"
                   required>

            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                <button type="button"
                        class="btn-digi btn-digi-gray"
                        style="padding: 6px 12px; font-size: 12px;"
                        onclick="togglePhotoUploadForm(false)">
                    Batal
                </button>
                <button type="submit"
                        class="btn-digi btn-digi-blue"
                        style="padding: 6px 14px; font-size: 12px;">
                    Simpan Foto
                </button>
            </div>
        </form>

        <h2 class="digi-profile-name">{{ $user->name }}</h2>
        <span class="digi-badge digi-badge-blue" style="font-size: 13px; padding: 6px 18px;">
            {{ $user->jabatan ?: ucfirst($user->role) }}
        </span>
        @if($user->nrp)
            <div style="margin-top: 12px; font-size: 13px; color: #475569; font-weight: 600;">
                NRP: {{ $user->nrp }}
            </div>
        @endif
        @if($user->email)
            <div style="margin-top: 4px; font-size: 12px; color: #64748b;">
                {{ $user->email }}
            </div>
        @endif
        <div style="margin-top: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
            {{ $user->company ?? 'PT UNITED TRACTORS Tbk - SITE SATUI' }}
        </div>
    </div>

    <!-- Panel Kanan: Detail & CRUD Profil + Tanda Tangan -->
    <div class="digi-profile-right">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <h1 class="digi-profile-header" style="border-bottom: none; padding-bottom: 0; margin: 0;">Profile Detail</h1>
            <button type="button"
                    id="btn-toggle-edit-profile"
                    class="btn-digi btn-digi-blue"
                    style="padding: 7px 18px; font-size: 13px;"
                    onclick="toggleEditProfileMode(true)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Edit Data Profil
            </button>
        </div>

        <!-- MODE BACA (READ PROFILE DETAIL) -->
        <div id="profile-view-section" style="display: {{ $hasProfileErrors ? 'none' : 'block' }};">
            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    Nama Lengkap
                </div>
                <div class="digi-profile-value" style="font-weight: 700;">
                    {{ $user->name }}
                </div>
            </div>

            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    Username
                </div>
                <div class="digi-profile-value">
                    {{ $user->username ?? $user->name }}
                </div>
            </div>

            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    Email
                </div>
                <div class="digi-profile-value">
                    {{ $user->email ?? '-' }}
                </div>
            </div>

            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    No. WhatsApp / HP
                </div>
                <div class="digi-profile-value">
                    {{ $user->phone ?: '-' }}
                </div>
            </div>

            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    NRP / Employee ID
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
                </div>
                <div class="digi-profile-value">
                    {{ $user->jabatan ?: '-' }}
                </div>
            </div>

            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    Perusahaan / Site
                </div>
                <div class="digi-profile-value">
                    {{ $user->company ?? 'PT UNITED TRACTORS Tbk - SITE SATUI' }}
                </div>
            </div>

            <div class="digi-profile-row">
                <div class="digi-profile-label">
                    Tanggal Dibuat
                </div>
                <div class="digi-profile-value">
                    {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') . ' WITA' : '-' }}
                </div>
            </div>
        </div>

        <!-- MODE EDIT (UPDATE DATA PROFIL & PASSWORD) -->
        <div id="profile-edit-section" style="display: {{ $hasProfileErrors ? 'block' : 'none' }}; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Formulir Edit Data Profil</h3>
                <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px;" onclick="toggleEditProfileMode(false)">
                    Tutup Edit
                </button>
            </div>

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="digi-input" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Username <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" class="digi-input" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Alamat Email <span style="font-weight:400; color:#64748b;">(Opsional)</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="digi-input" placeholder="Email (Opsional)">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">No. WhatsApp / HP <span style="font-size:11px; font-weight:normal; color:#64748b;">(08xxx)</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="digi-input" placeholder="Contoh: 081234567890">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">NRP / ID Karyawan</label>
                        <input type="text" name="nrp" value="{{ old('nrp', $user->nrp) }}" class="digi-input">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Jabatan <span style="font-weight:400; color:#64748b;">(Opsional)</span></label>
                        <input type="text" name="jabatan" value="{{ old('jabatan', $user->jabatan) }}" class="digi-input" placeholder="Jabatan (Opsional)">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Perusahaan / Site <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="company" value="{{ old('company', $user->company ?? 'PT UNITED TRACTORS Tbk - SITE SATUI') }}" class="digi-input" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Password Baru <span style="font-weight:400; color:#64748b;">(Opsional)</span></label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="password" id="profile_password" class="digi-input" placeholder="Kosongkan jika tidak ganti password" style="padding-right: 42px;">
                            <button type="button" onclick="toggleProfilePassword('profile_password', this)" title="Lihat Password" aria-label="Lihat Password" style="position: absolute; right: 10px; background: transparent; border: none; padding: 4px; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Konfirmasi Password Baru</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="password_confirmation" id="profile_password_confirmation" class="digi-input" placeholder="Ulangi password baru" style="padding-right: 42px;">
                            <button type="button" onclick="toggleProfilePassword('profile_password_confirmation', this)" title="Lihat Password" aria-label="Lihat Password" style="position: absolute; right: 10px; background: transparent; border: none; padding: 4px; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 14px; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 8px 18px; font-size: 13px;" onclick="toggleEditProfileMode(false)">
                        Batal
                    </button>
                    <button type="submit" class="btn-digi btn-digi-blue" style="padding: 8px 22px; font-size: 13px;">
                        Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>

        <!-- BAGIAN TANDA TANGAN DIGITAL (Lihat, Tambah, Ganti, Hapus) -->
        <div class="digi-profile-row" style="align-items: flex-start; margin-top: 8px;">
            <div class="digi-profile-label" style="padding-top: 6px;">
                Tanda Tangan Digital
            </div>
            <div class="digi-profile-value">
                @if($user->signature_url)
                    <div id="signature-preview-box">
                        <img src="{{ asset($user->signature_url) }}"
                             alt="Tanda Tangan"
                             class="digi-signature-preview"
                             style="cursor: pointer;"
                             onclick="openSignatureModal('{{ asset($user->signature_url) }}')"
                             title="Klik untuk memperbesar tanda tangan">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                            <button type="button" class="btn-digi btn-digi-gray" style="padding: 6px 14px; font-size: 12.5px;" onclick="openSignatureModal('{{ asset($user->signature_url) }}')">
                                Lihat TTD
                            </button>
                            <button type="button" class="btn-digi btn-digi-yellow" style="padding: 6px 14px; font-size: 12.5px;" onclick="showUploadForm()">
                                Ganti TTD
                            </button>
                            <form action="{{ route('profile.signature.delete', 'current') }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus tanda tangan aktif saat ini dari profil Anda?');">
                                @csrf
                                <button type="submit" class="btn-digi btn-digi-red" style="padding: 6px 14px; font-size: 12.5px;">
                                    Hapus TTD
                                </button>
                            </form>
                        </div>
                    </div>

                    <form id="signature-upload-form" action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data" style="display: none; margin-top: 10px;">
                        @csrf
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">Format wajib: PNG atau JPEG (Maks. 2MB).</div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="file" name="signature" accept="image/png,image/jpeg" class="digi-input" style="max-width: 280px; padding: 6px;" required>
                            <button type="submit" class="btn-digi btn-digi-blue" style="padding: 7px 18px; font-size: 13px;">Simpan TTD</button>
                            <button type="button" class="btn-digi btn-digi-gray" style="padding: 7px 12px; font-size: 13px;" onclick="hideUploadForm()">Batal</button>
                        </div>
                    </form>
                @else
                    <form action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">Belum ada tanda tangan aktif. Format wajib: PNG atau JPEG (Maks. 2MB).</div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="file" name="signature" accept="image/png,image/jpeg" class="digi-input" style="max-width: 300px; padding: 6px;" required>
                            <button type="submit" class="btn-digi btn-digi-blue" style="padding: 8px 20px; font-size: 13px;">
                                + Tambah TTD
                            </button>
                        </div>
                    </form>
                @endif

                @if(isset($signatures) && $signatures->count() > 0)
                    <div style="margin-top: 16px; font-size: 12px; color: #64748b;">
                        <strong>Riwayat Tanda Tangan Tersimpan ({{ $signatures->count() }}):</strong>
                        @foreach($signatures->take(4) as $sig)
                            <div style="display:flex; align-items:center; gap:8px; margin-top:6px; flex-wrap: wrap;">
                                <span>• {{ $sig->created_at?->format('d/m/Y H:i') }}</span>
                                <span class="digi-badge {{ $sig->is_active ? 'digi-badge-green' : 'digi-badge-gray' }}" style="padding: 2px 8px; font-size: 10.5px;">
                                    {{ $sig->is_active ? 'Aktif' : 'Historis (Nonaktif)' }}
                                </span>
                                <button type="button"
                                        style="background:none; border:none; color:#0284c7; font-size:11.5px; font-weight:600; cursor:pointer; padding:0;"
                                        onclick="openSignatureModal('{{ asset($sig->signature_path) }}')">
                                    [Lihat]
                                </button>
                                <form action="{{ route('profile.signature.delete', $sig->id) }}" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Hapus item riwayat tanda tangan ini?');">
                                    @csrf
                                    <button type="submit" style="background:none; border:none; color:#ef4444; font-size:11.5px; font-weight:600; cursor:pointer; padding:0;">
                                        [Hapus]
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Tombol Logout -->
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

<!-- MODAL LIHAT FOTO PROFIL -->
@if($user->profile_photo_url)
<div id="viewPhotoModal" class="digi-modal-backdrop" style="display: none;" onclick="if(event.target === this) closePhotoModal()">
    <div class="digi-modal" style="max-width: 460px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Foto Profil — {{ $user->name }}</h3>
            <button type="button" class="digi-modal-close" onclick="closePhotoModal()">&times;</button>
        </div>
        <div class="digi-modal-body" style="text-align: center; background: #f8fafc;">
            <img src="{{ asset($user->profile_photo_url) }}"
                 alt="Foto Profil {{ $user->name }}"
                 style="max-width: 100%; max-height: 360px; border-radius: 12px; object-fit: contain; box-shadow: 0 6px 20px rgba(0,0,0,0.12);">
        </div>
        <div class="digi-modal-footer">
            <button type="button" class="btn-digi btn-digi-blue" onclick="closePhotoModal(); togglePhotoUploadForm(true);">
                Ganti Foto
            </button>
            <button type="button" class="btn-digi btn-digi-gray" onclick="closePhotoModal()">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS FOTO PROFIL -->
<div id="deletePhotoModal" class="digi-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeDeletePhotoModal()">
    <div class="digi-modal" style="max-width: 420px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Konfirmasi Hapus Foto Profil</h3>
            <button type="button" class="digi-modal-close" onclick="closeDeletePhotoModal()">&times;</button>
        </div>
        <div class="digi-modal-body">
            Apakah Anda yakin ingin menghapus foto profil saat ini? Tampilan profil akan kembali menggunakan inisial nama Anda.
        </div>
        <div class="digi-modal-footer">
            <button type="button" class="btn-digi btn-digi-gray" onclick="closeDeletePhotoModal()">Batal</button>
            <form action="{{ route('profile.photo.delete') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-digi btn-digi-red">Ya, Hapus Foto</button>
            </form>
        </div>
    </div>
</div>
@endif

<!-- MODAL LIHAT TANDA TANGAN -->
<div id="viewSignatureModal" class="digi-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeSignatureModal()">
    <div class="digi-modal" style="max-width: 460px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title">Pratinjau Tanda Tangan Digital</h3>
            <button type="button" class="digi-modal-close" onclick="closeSignatureModal()">&times;</button>
        </div>
        <div class="digi-modal-body" style="text-align: center; background: #f8fafc;">
            <img id="signatureModalImg"
                 src=""
                 alt="Pratinjau Tanda Tangan"
                 style="max-width: 100%; max-height: 260px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; object-fit: contain;">
        </div>
        <div class="digi-modal-footer">
            <button type="button" class="btn-digi btn-digi-gray" onclick="closeSignatureModal()">Tutup</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function togglePhotoUploadForm(show) {
    const form = document.getElementById('photo-upload-form');
    if (!form) return;
    form.style.display = show ? 'block' : 'none';
    if (!show) {
        const input = document.getElementById('profile_photo_input');
        if (input) input.value = '';
        const previewWrap = document.getElementById('new-photo-preview-wrap');
        if (previewWrap) previewWrap.style.display = 'none';
    }
}

function previewSelectedPhoto(input) {
    const previewWrap = document.getElementById('new-photo-preview-wrap');
    const previewImg = document.getElementById('new-photo-preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewWrap.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewWrap.style.display = 'none';
    }
}

function openPhotoModal() {
    const modal = document.getElementById('viewPhotoModal');
    if (modal) modal.style.display = 'flex';
}

function closePhotoModal() {
    const modal = document.getElementById('viewPhotoModal');
    if (modal) modal.style.display = 'none';
}

function openDeletePhotoModal() {
    const modal = document.getElementById('deletePhotoModal');
    if (modal) modal.style.display = 'flex';
}

function closeDeletePhotoModal() {
    const modal = document.getElementById('deletePhotoModal');
    if (modal) modal.style.display = 'none';
}

function toggleEditProfileMode(editMode) {
    const viewSection = document.getElementById('profile-view-section');
    const editSection = document.getElementById('profile-edit-section');
    const toggleBtn = document.getElementById('btn-toggle-edit-profile');
    if (viewSection && editSection) {
        viewSection.style.display = editMode ? 'none' : 'block';
        editSection.style.display = editMode ? 'block' : 'none';
    }
    if (toggleBtn) {
        toggleBtn.style.display = editMode ? 'none' : 'inline-flex';
    }
}

function openSignatureModal(imgUrl) {
    const modal = document.getElementById('viewSignatureModal');
    const img = document.getElementById('signatureModalImg');
    if (modal && img) {
        img.src = imgUrl;
        modal.style.display = 'flex';
    }
}

function closeSignatureModal() {
    const modal = document.getElementById('viewSignatureModal');
    if (modal) modal.style.display = 'none';
}

function showUploadForm() {
    document.getElementById('signature-preview-box').style.display = 'none';
    document.getElementById('signature-upload-form').style.display = 'block';
}

function hideUploadForm() {
    document.getElementById('signature-upload-form').style.display = 'none';
    document.getElementById('signature-preview-box').style.display = 'block';
}

function toggleProfilePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const eyeOpen = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');

    if (input.type === 'password') {
        input.type = 'text';
        if (eyeOpen) eyeOpen.style.display = 'none';
        if (eyeClosed) eyeClosed.style.display = 'inline';
        btn.title = 'Sembunyikan Password';
    } else {
        input.type = 'password';
        if (eyeOpen) eyeOpen.style.display = 'inline';
        if (eyeClosed) eyeClosed.style.display = 'none';
        btn.title = 'Lihat Password';
    }
}
</script>
@endsection
