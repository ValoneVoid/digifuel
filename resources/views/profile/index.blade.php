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

<style>
/* Defensive Responsive Rules for Profile & Canvas Signature Pad */
.digi-sigpad-container {
    width: 100% !important;
    max-width: 520px;
    box-sizing: border-box !important;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    overflow: hidden !important;
    margin-top: 10px;
}
.digi-sigpad-tabs {
    display: flex;
    background: #f1f5f9;
    border-bottom: 1.5px solid #e2e8f0;
    padding: 4px;
    gap: 4px;
    box-sizing: border-box;
}
.digi-sigpad-tab-btn {
    flex: 1;
    padding: 8px 10px;
    font-size: 12.5px;
    font-weight: 600;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.digi-sigpad-tab-btn.active {
    background: #ffffff;
    color: #0284c7;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.digi-sigpad-body {
    padding: 14px 16px;
    box-sizing: border-box;
    width: 100%;
}
.digi-sigpad-canvas-wrapper {
    position: relative;
    background: #ffffff;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    cursor: crosshair;
    touch-action: none;
    overflow: hidden !important;
    height: 185px !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.digi-sigpad-canvas-wrapper.is-drawing {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}
.digi-sigpad-canvas {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    touch-action: none;
    background: #ffffff;
    box-sizing: border-box !important;
}
.digi-sigpad-baseline {
    position: absolute;
    bottom: 36px;
    left: 16px;
    right: 16px;
    border-bottom: 1px dashed #cbd5e1;
    pointer-events: none;
}
.digi-sigpad-baseline-hint {
    position: absolute;
    bottom: 8px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
    pointer-events: none;
    user-select: none;
}
.digi-sigpad-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 12px;
    flex-wrap: wrap;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .digi-profile-layout {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .digi-profile-left, .digi-profile-right {
        padding: 18px 14px !important;
        border-radius: 14px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }
    .digi-profile-row {
        grid-template-columns: 1fr !important;
        gap: 4px !important;
        padding: 10px 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .digi-profile-value {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .digi-sigpad-container {
        max-width: 100% !important;
        width: 100% !important;
    }
    .digi-sigpad-tabs {
        padding: 3px !important;
        gap: 4px !important;
    }
    .digi-sigpad-tab-btn {
        padding: 8px 6px !important;
        font-size: 11.5px !important;
        white-space: nowrap !important;
    }
    .digi-sigpad-body {
        padding: 12px 10px !important;
    }
    .digi-sigpad-canvas-wrapper, .digi-sigpad-canvas {
        height: 175px !important;
    }
    .digi-sigpad-footer {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
    }
    .digi-sigpad-footer .btn-digi {
        width: 100% !important;
        justify-content: center !important;
        text-align: center !important;
        padding: 10px 14px !important;
    }
    .digi-sigpad-footer > div {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        width: 100% !important;
    }
}
</style>

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
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-1px; margin-right:3px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>Lihat TTD
                            </button>
                            <button type="button" class="btn-digi btn-digi-yellow" style="padding: 6px 14px; font-size: 12.5px;" onclick="showSignatureCreator()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-1px; margin-right:3px;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>Ganti TTD
                            </button>
                            <form action="{{ route('profile.signature.delete', 'current') }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus tanda tangan aktif saat ini dari profil Anda?');">
                                @csrf
                                <button type="submit" class="btn-digi btn-digi-red" style="padding: 6px 14px; font-size: 12.5px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-1px; margin-right:3px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>Hapus TTD
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- SIGNATURE CREATOR COMPONENT (CANVAS DRAW OR FILE UPLOAD) -->
                <div id="signature-creator-container" class="digi-sigpad-container" style="{{ $user->signature_url ? 'display: none;' : 'display: block;' }}">
                    <!-- Segmented Tabs -->
                    <div class="digi-sigpad-tabs">
                        <button type="button" id="sigTabDraw" class="digi-sigpad-tab-btn active" onclick="switchSignatureTab('draw')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            <span>Gores Langsung (Kanvas)</span>
                        </button>
                        <button type="button" id="sigTabUpload" class="digi-sigpad-tab-btn" onclick="switchSignatureTab('upload')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <span>Upload File Gambar</span>
                        </button>
                    </div>

                    <div class="digi-sigpad-body">
                        <!-- PANEL 1: CANVAS DRAWING -->
                        <div id="sigPanelDraw">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                <div style="font-size: 12.5px; color: #475569; font-weight: 500;">
                                    Goreskan tanda tangan pada kanvas di bawah:
                                </div>
                                <div id="sigpadStatusBadge" class="digi-sigpad-status">
                                    <span style="color: #94a3b8; font-size: 11.5px;">Belum ada goresan</span>
                                </div>
                            </div>

                            <div class="digi-sigpad-canvas-wrapper" id="digiSigCanvasWrapper" style="position: relative; width: 100%; height: 185px; max-width: 100%; box-sizing: border-box; touch-action: none; background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 10px; overflow: hidden;">
                                <canvas id="digiSignatureCanvas" class="digi-sigpad-canvas" style="display: block; width: 100%; height: 100%; touch-action: none; background: #ffffff;"></canvas>
                                <div class="digi-sigpad-baseline" style="position: absolute; bottom: 36px; left: 16px; right: 16px; border-bottom: 1px dashed #cbd5e1; pointer-events: none;"></div>
                                <div class="digi-sigpad-baseline-hint" style="position: absolute; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 11px; color: #94a3b8; pointer-events: none; user-select: none;">Gores tanda tangan di atas garis ini</div>
                            </div>

                            <form id="canvasSignatureForm" action="{{ route('profile.signature') }}" method="POST" onsubmit="return prepareAndSubmitCanvasSignature();" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="signature_base64" id="signature_base64_input">
                                
                                <div class="digi-sigpad-footer">
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 7px 14px; font-size: 12.5px;" onclick="clearSignaturePad()">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-1px; margin-right:3px;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                        Bersihkan / Ulangi
                                    </button>

                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        @if($user->signature_url)
                                            <button type="button" class="btn-digi btn-digi-gray" style="padding: 7px 14px; font-size: 12.5px;" onclick="hideSignatureCreator()">
                                                Batal
                                            </button>
                                        @endif
                                        <button type="submit" id="saveCanvasSignatureBtn" class="btn-digi btn-digi-blue" style="padding: 8px 18px; font-size: 13px; opacity: 0.5; cursor: not-allowed;" disabled>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            Simpan Tanda Tangan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- PANEL 2: FILE UPLOAD -->
                        <div id="sigPanelUpload" style="display: none;">
                            <form action="{{ route('profile.signature') }}" method="POST" enctype="multipart/form-data" style="margin: 0;">
                                @csrf
                                <div style="font-size: 12.5px; color: #64748b; margin-bottom: 10px;">
                                    Pilih file scan atau foto tanda tangan bertipe <strong>PNG</strong> atau <strong>JPEG</strong> (Maksimal 2 MB).
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <input type="file" name="signature" accept="image/png,image/jpeg" class="digi-input" style="flex: 1; min-width: 220px; padding: 7px;" required>
                                    <button type="submit" class="btn-digi btn-digi-blue" style="padding: 8px 18px; font-size: 13px;">
                                        Unggah Berkas
                                    </button>
                                    @if($user->signature_url)
                                        <button type="button" class="btn-digi btn-digi-gray" style="padding: 8px 14px; font-size: 13px;" onclick="hideSignatureCreator()">
                                            Batal
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

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

function showSignatureCreator() {
    const previewBox = document.getElementById('signature-preview-box');
    const creatorBox = document.getElementById('signature-creator-container');
    if (previewBox) previewBox.style.display = 'none';
    if (creatorBox) {
        creatorBox.style.display = 'block';
        switchSignatureTab('draw');
        requestAnimationFrame(() => {
            resizeSignaturePad();
            setTimeout(() => {
                resizeSignaturePad();
                creatorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 60);
        });
    }
}

function hideSignatureCreator() {
    const previewBox = document.getElementById('signature-preview-box');
    const creatorBox = document.getElementById('signature-creator-container');
    if (creatorBox) creatorBox.style.display = 'none';
    if (previewBox) previewBox.style.display = 'block';
}

function switchSignatureTab(mode) {
    const drawTab = document.getElementById('sigTabDraw');
    const uploadTab = document.getElementById('sigTabUpload');
    const drawPanel = document.getElementById('sigPanelDraw');
    const uploadPanel = document.getElementById('sigPanelUpload');

    if (!drawTab || !uploadTab || !drawPanel || !uploadPanel) return;

    if (mode === 'draw') {
        drawTab.classList.add('active');
        uploadTab.classList.remove('active');
        drawPanel.style.display = 'block';
        uploadPanel.style.display = 'none';
        requestAnimationFrame(() => {
            resizeSignaturePad();
            setTimeout(resizeSignaturePad, 50);
        });
    } else {
        uploadTab.classList.add('active');
        drawTab.classList.remove('active');
        uploadPanel.style.display = 'block';
        drawPanel.style.display = 'none';
    }
}

// -------------------------------------------------------------
// HTML5 Canvas Digital Signature Pad Implementation
// -------------------------------------------------------------
let sigPadCanvas = null;
let sigPadCtx = null;
let isDrawing = false;
let hasStrokes = false;
let lastX = 0;
let lastY = 0;
let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

function initSignaturePad() {
    sigPadCanvas = document.getElementById('digiSignatureCanvas');
    if (!sigPadCanvas) return;
    sigPadCtx = sigPadCanvas.getContext('2d');

    resizeSignaturePad();
    window.addEventListener('resize', () => {
        requestAnimationFrame(resizeSignaturePad);
    });

    sigPadCanvas.addEventListener('pointerdown', startDrawing, { passive: false });
    sigPadCanvas.addEventListener('pointermove', drawStroke, { passive: false });
    sigPadCanvas.addEventListener('pointerup', stopDrawing);
    sigPadCanvas.addEventListener('pointercancel', stopDrawing);
    sigPadCanvas.addEventListener('pointerleave', stopDrawing);
}

function resizeSignaturePad() {
    if (!sigPadCanvas || !sigPadCtx) return;
    const wrapper = document.getElementById('digiSigCanvasWrapper');
    if (!wrapper) return;
    const rect = wrapper.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) return;

    const dpr = Math.min(window.devicePixelRatio || 1, 2.5);

    // Save existing stroke image if any before resizing canvas buffer
    let tempCanvas = null;
    if (hasStrokes && sigPadCanvas.width > 0 && sigPadCanvas.height > 0) {
        tempCanvas = document.createElement('canvas');
        tempCanvas.width = sigPadCanvas.width;
        tempCanvas.height = sigPadCanvas.height;
        const tempCtx = tempCanvas.getContext('2d');
        tempCtx.drawImage(sigPadCanvas, 0, 0);
    }

    const targetW = Math.round(rect.width);
    const targetH = Math.round(rect.height) || 185;

    sigPadCanvas.width = Math.round(targetW * dpr);
    sigPadCanvas.height = Math.round(targetH * dpr);
    sigPadCanvas.style.width = targetW + 'px';
    sigPadCanvas.style.height = targetH + 'px';

    sigPadCtx.scale(dpr, dpr);
    sigPadCtx.strokeStyle = '#0f172a'; // Deep slate / charcoal ink
    sigPadCtx.lineWidth = 2.8;
    sigPadCtx.lineCap = 'round';
    sigPadCtx.lineJoin = 'round';

    if (tempCanvas) {
        sigPadCtx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, 0, 0, targetW, targetH);
    }
}

function getCanvasCoordinates(e) {
    const rect = sigPadCanvas.getBoundingClientRect();
    return {
        x: Math.max(0, Math.min(rect.width, e.clientX - rect.left)),
        y: Math.max(0, Math.min(rect.height, e.clientY - rect.top))
    };
}

function startDrawing(e) {
    if (e.button !== undefined && e.button !== 0) return;
    e.preventDefault();
    if (e.target && e.target.setPointerCapture && e.pointerId) {
        try { e.target.setPointerCapture(e.pointerId); } catch(err) {}
    }
    isDrawing = true;
    const wrapper = document.getElementById('digiSigCanvasWrapper');
    if (wrapper) wrapper.classList.add('is-drawing');

    const pos = getCanvasCoordinates(e);
    lastX = pos.x;
    lastY = pos.y;

    sigPadCtx.beginPath();
    sigPadCtx.arc(pos.x, pos.y, sigPadCtx.lineWidth / 2, 0, Math.PI * 2);
    sigPadCtx.fillStyle = sigPadCtx.strokeStyle;
    sigPadCtx.fill();

    updateBounds(pos.x, pos.y);
    markHasStrokes(true);
}

function drawStroke(e) {
    if (!isDrawing) return;
    e.preventDefault();
    const pos = getCanvasCoordinates(e);

    sigPadCtx.beginPath();
    sigPadCtx.moveTo(lastX, lastY);
    const midX = (lastX + pos.x) / 2;
    const midY = (lastY + pos.y) / 2;
    sigPadCtx.quadraticCurveTo(lastX, lastY, midX, midY);
    sigPadCtx.lineTo(pos.x, pos.y);
    sigPadCtx.stroke();

    updateBounds(pos.x, pos.y);
    lastX = pos.x;
    lastY = pos.y;
}

function stopDrawing(e) {
    if (isDrawing) {
        isDrawing = false;
        if (e && e.target && e.target.releasePointerCapture && e.pointerId) {
            try { e.target.releasePointerCapture(e.pointerId); } catch(err) {}
        }
        const wrapper = document.getElementById('digiSigCanvasWrapper');
        if (wrapper) wrapper.classList.remove('is-drawing');
    }
}

function updateBounds(x, y) {
    if (x < minX) minX = x;
    if (x > maxX) maxX = x;
    if (y < minY) minY = y;
    if (y > maxY) maxY = y;
}

function markHasStrokes(state) {
    hasStrokes = state;
    const submitBtn = document.getElementById('saveCanvasSignatureBtn');
    const badge = document.getElementById('sigpadStatusBadge');
    if (submitBtn) {
        submitBtn.disabled = !state;
        submitBtn.style.opacity = state ? '1' : '0.5';
        submitBtn.style.cursor = state ? 'pointer' : 'not-allowed';
    }
    if (badge) {
        if (state) {
            badge.innerHTML = '<span style="color:#16a34a; font-weight:700;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block; vertical-align:-1px; margin-right:3px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Siap disimpan</span>';
        } else {
            badge.innerHTML = '<span style="color:#94a3b8;">Belum ada goresan</span>';
        }
    }
}

function clearSignaturePad() {
    if (!sigPadCanvas || !sigPadCtx) return;
    const dpr = Math.min(window.devicePixelRatio || 1, 2.5);
    sigPadCtx.clearRect(0, 0, sigPadCanvas.width / dpr, sigPadCanvas.height / dpr);
    minX = Infinity; minY = Infinity; maxX = -Infinity; maxY = -Infinity;
    markHasStrokes(false);
}

function prepareAndSubmitCanvasSignature() {
    if (!hasStrokes) {
        alert('Silakan goreskan tanda tangan Anda terlebih dahulu pada kanvas.');
        return false;
    }

    const dpr = window.devicePixelRatio || 1;
    const pad = 16;
    const cropX = Math.max(0, (minX - pad) * dpr);
    const cropY = Math.max(0, (minY - pad) * dpr);
    const cropW = Math.min(sigPadCanvas.width - cropX, (maxX - minX + (pad * 2)) * dpr);
    const cropH = Math.min(sigPadCanvas.height - cropY, (maxY - minY + (pad * 2)) * dpr);

    let base64Output = '';
    if (cropW > 10 && cropH > 10) {
        const cropCanvas = document.createElement('canvas');
        cropCanvas.width = cropW;
        cropCanvas.height = cropH;
        const cropCtx = cropCanvas.getContext('2d');
        cropCtx.drawImage(sigPadCanvas, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
        base64Output = cropCanvas.toDataURL('image/png');
    } else {
        base64Output = sigPadCanvas.toDataURL('image/png');
    }

    const input = document.getElementById('signature_base64_input');
    if (!input) return false;
    input.value = base64Output;
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    initSignaturePad();
});

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
