@extends('layouts.app')

@section('title', 'Panduan Penggunaan Sistem')

@section('content')
<style>
    /* ========================================================================= */
    /* STYLING PUSAT PANDUAN & TUTORIAL DIGIFUEL                                 */
    /* ========================================================================= */
    .digi-guide-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        border-radius: 16px;
        padding: 28px 24px;
        margin-bottom: 22px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
        position: relative;
        overflow: hidden;
    }
    .digi-guide-hero::after {
        content: "";
        position: absolute;
        top: -40px;
        right: -40px;
        width: 180px;
        height: 180px;
        background: rgba(2, 132, 199, 0.15);
        border-radius: 50%;
        filter: blur(40px);
        pointer-events: none;
    }
    .digi-guide-nav-pills {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 6px;
        margin-bottom: 20px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    .digi-guide-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        user-select: none;
    }
    .digi-guide-pill:hover {
        background: #f8fafc;
        border-color: #0284c7;
        color: #0284c7;
    }
    .digi-guide-pill.is-active {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
    }
    .digi-guide-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .digi-guide-step-row {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
        align-items: flex-start;
    }
    .digi-step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #0284c7;
        color: #ffffff;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(2, 132, 199, 0.3);
    }
    .digi-step-content {
        flex: 1;
        min-width: 0;
    }
    .digi-step-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px 0;
    }
    .digi-step-desc {
        font-size: 13.5px;
        color: #475569;
        line-height: 1.6;
        margin: 0;
    }
    .digi-flow-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .digi-flow-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        position: relative;
    }
    .digi-flow-box.is-highlight {
        background: #f0f9ff;
        border-color: #bae6fd;
    }
    .digi-flow-step-num {
        font-size: 11px;
        font-weight: 700;
        color: #0284c7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .digi-flow-step-name {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }
    .digi-flow-step-actor {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 2px;
    }
    .digi-callout {
        border-radius: 10px;
        padding: 12px 16px;
        margin: 14px 0;
        font-size: 13px;
        line-height: 1.5;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .digi-callout-info {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        color: #0369a1;
    }
    .digi-callout-warning {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }
    .digi-callout-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }
    .digi-faq-item {
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 0;
    }
    .digi-faq-item:last-child {
        border-bottom: none;
    }
    .digi-faq-q {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .digi-faq-a {
        font-size: 13.5px;
        color: #475569;
        line-height: 1.6;
        margin: 0;
    }
    @media (max-width: 640px) {
        .digi-guide-hero {
            padding: 20px 16px;
        }
        .digi-guide-card {
            padding: 16px 14px;
        }
        .digi-step-row {
            gap: 12px;
        }
    }
</style>

<div class="digi-card" style="margin-bottom: 20px;">
    <!-- HERO HEADER -->
    <div class="digi-guide-hero">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <span style="background: rgba(2, 132, 199, 0.25); border: 1px solid rgba(56, 189, 248, 0.4); padding: 3px 10px; border-radius: 9999px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                Pusat Edukasi & Bantuan
            </span>
        </div>
        <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 8px 0; color: #ffffff;">
            Panduan Penggunaan Sistem FuelLink
        </h1>
        <p style="font-size: 13.5px; color: #cbd5e1; margin: 0; max-width: 720px; line-height: 1.6;">
            Panduan praktis langkah demi langkah yang dirancang agar mudah dipahami oleh siapa saja — mulai dari Driver di lapangan, Pengawas (SPV), Pimpinan Departemen, Petugas Vendor BBM, hingga Administrator sistem.
        </p>
    </div>

    <!-- TAB PILIHAN PERAN (ROLE-BASED TABS) -->
    <div class="digi-guide-nav-pills" id="guideTabsNav">
        <button type="button" class="digi-guide-pill is-active" onclick="switchGuideTab('alur', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            <span>Alur Cepat (4 Tahap)</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('driver', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            <span>Driver / Operator</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('spv', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
            <span>Supervisor (SPV)</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('manager', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            <span>Dept Head / Manager</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('vendor', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v6h6V2H3z"></path><path d="M14 2v6h7V2h-7z"></path><path d="M14 13h7v9h-7z"></path><path d="M3 13h6v9H3z"></path></svg>
            <span>Vendor BBM</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('admin', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span>Administrator</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('ttd', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"></path><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path><path d="M2 2l7.586 7.586"></path><circle cx="11" cy="11" r="2"></circle></svg>
            <span>Tanda Tangan Digital</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('faq', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>Tanya Jawab (FAQ)</span>
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 1: ALUR CEPAT (INFOGRAFIS 5 TAHAP)                        -->
    <!-- ============================================================== -->
    <div id="guide-panel-alur" class="digi-guide-panel">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">
                Bagaimana Pengajuan BBM Bekerja dari Awal Sampai Selesai?
            </h2>
            <p style="font-size: 13.5px; color: #64748b; margin: 0 0 16px 0;">
                Sistem FuelLink menggantikan kupon kertas manual menjadi alur digital terpadu dalam 4 langkah sekuensial:
            </p>

            <div class="digi-flow-bar">
                <div class="digi-flow-box is-highlight">
                    <div class="digi-flow-step-num">Langkah 1</div>
                    <div class="digi-flow-step-name">Pengajuan Permohonan</div>
                    <div class="digi-flow-step-actor">Oleh: Driver / Pemohon</div>
                </div>
                <div class="digi-flow-box">
                    <div class="digi-flow-step-num">Langkah 2</div>
                    <div class="digi-flow-step-name">Verifikasi SPV</div>
                    <div class="digi-flow-step-actor">Oleh: Supervisor (SPV)</div>
                </div>
                <div class="digi-flow-box">
                    <div class="digi-flow-step-num">Langkah 3</div>
                    <div class="digi-flow-step-name">Approval Dept Head</div>
                    <div class="digi-flow-step-actor">Oleh: Dept Head / Manager</div>
                </div>
                <div class="digi-flow-box is-highlight">
                    <div class="digi-flow-step-num">Langkah 4</div>
                    <div class="digi-flow-step-name">Persetujuan & Realisasi</div>
                    <div class="digi-flow-step-actor">Oleh: Petugas Vendor BBM</div>
                </div>
            </div>

            <div class="digi-callout digi-callout-info" style="margin-top: 18px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <strong>Pemberitahuan Otomatis:</strong> Setiap kali ada pengajuan yang membutuhkan persetujuan, sistem otomatis mengirim notifikasi ke nomor WhatsApp pejabat terkait melalui gerbang resmi WhatsApp FuelLink.
                </div>
            </div>
        </div>

        <!-- ARTI WARNA STATUS -->
        <div class="digi-guide-card">
            <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 12px 0;">Arti Warna Status Pengajuan</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <div style="padding: 10px 12px; border-radius: 8px; background: #fffbeb; border: 1px solid #fde68a;">
                    <span style="font-weight: 700; color: #b45309; font-size: 12.5px;">🟡 Kuning (Draft / Menunggu)</span>
                    <p style="font-size: 11.5px; color: #78350f; margin: 3px 0 0 0;">Pengajuan baru dibuat dan sedang menunggu pemeriksaan pejabat berwenang.</p>
                </div>
                <div style="padding: 10px 12px; border-radius: 8px; background: #f0f9ff; border: 1px solid #bae6fd;">
                    <span style="font-weight: 700; color: #0369a1; font-size: 12.5px;">🔵 Biru (Disetujui Berjenjang)</span>
                    <p style="font-size: 11.5px; color: #0c4a6e; margin: 3px 0 0 0;">Telah disetujui oleh SPV / Dept Head dan berlanjut ke tahap berikutnya.</p>
                </div>
                <div style="padding: 10px 12px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0;">
                    <span style="font-weight: 700; color: #15803d; font-size: 12.5px;">🟢 Hijau (Siap Isi / Selesai)</span>
                    <p style="font-size: 11.5px; color: #14532d; margin: 3px 0 0 0;">Vendor telah menyetujui kupon BBM atau Fuelman telah selesai mengisi solar ke unit.</p>
                </div>
                <div style="padding: 10px 12px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca;">
                    <span style="font-weight: 700; color: #b91c1c; font-size: 12.5px;">🔴 Merah (Ditolak / Batal)</span>
                    <p style="font-size: 11.5px; color: #7f1d1d; margin: 3px 0 0 0;">Pengajuan ditolak oleh penilai dengan alasan yang tertera pada catatan pengajuan.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 2: PANDUAN DRIVER / OPERATOR                              -->
    <!-- ============================================================== -->
    <div id="guide-panel-driver" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Cara Mengajukan BBM (Khusus Driver & Pemohon)
            </h2>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">1</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Buka Menu "Form Request"</h3>
                    <p class="digi-step-desc">
                        Di bar navigasi atas, klik menu <strong>Form Request</strong>. Formulir pengajuan baru akan terbuka.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">2</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pilih Unit Kendaraan & Isi Kebutuhan Liter</h3>
                    <p class="digi-step-desc">
                        - <strong>Pilih Unit:</strong> Ketik atau pilih kode unit kendaraan Anda (misal: <em>UT 208 DA 8191</em>). Informasi model dan kapasitas tangki akan muncul otomatis.<br>
                        - <strong>Area & Pos:</strong> Pilih lokasi site kerja Anda.<br>
                        - <strong>Jumlah Liter:</strong> Masukkan jumlah BBM yang ingin diisi (pastikan tidak melebihi kapasitas tangki).<br>
                        - <strong>Angka Odometer / Jam (KM/HM):</strong> Masukkan angka kilometer atau hour meter terkini dari speedometer kendaraan.<br>
                        - <strong>Keperluan:</strong> Tuliskan keterangan singkat, misalnya <em>"Operasional Pengawasan Hauling Shift Pagi"</em>.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">3</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Kirim Pengajuan atau Simpan Draft</h3>
                    <p class="digi-step-desc">
                        - Klik <strong>"Kirim Permohonan"</strong> jika data sudah yakin benar. Pengajuan akan langsung masuk ke antrean Supervisor Anda.<br>
                        - Atau klik <strong>"Simpan sebagai Draft"</strong> jika Anda masih ingin melengkapinya nanti.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">4</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pantau Status & Cetak Dokumen di Menu "History"</h3>
                    <p class="digi-step-desc">
                        Buka menu <strong>History</strong> untuk melihat progres persetujuan. Jika status sudah <strong>Approved by Vendor</strong>, Anda dapat menunjukkan halaman ini ke Fuelman atau menekan tombol <strong>Cetak PDF</strong> untuk dokumen fisik resmi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 3: PANDUAN SUPERVISOR (SPV)                               -->
    <!-- ============================================================== -->
    <div id="guide-panel-spv" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Cara Memeriksa & Menyetujui Pengajuan (Supervisor)
            </h2>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">1</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Buka Menu "Pengajuan"</h3>
                    <p class="digi-step-desc">
                        Daftar seluruh permohonan BBM yang diajukan oleh anggota tim/driver di area pengawasan Anda akan tampil rapi di tabel antrean.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">2</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Periksa Rincian & Sesuaikan Liter Bila Diperlukan</h3>
                    <p class="digi-step-desc">
                        Klik tombol <strong>Periksa / Detail</strong> pada baris pengajuan. Anda dapat melihat riwayat pengisian sebelumnya, odometer terakhir, dan estimasi rasio konsumsi.<br>
                        <span style="color: #0284c7; font-weight: 600;">Fitur Khusus SPV:</span> Jika Driver meminta liter berlebih, SPV dapat mengoreksi angka pada kolom <em>"Sesuaikan Jumlah Liter Disetujui"</em> sebelum menyetujui.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">3</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Klik "Setujui Permohonan" atau "Tolak"</h3>
                    <p class="digi-step-desc">
                        - <strong>Setujui:</strong> Pengajuan akan naik ke Tahap 2 (Dept Head / Manager) dan notifikasi WA otomatis terkirim.<br>
                        - <strong>Tolak:</strong> Wajib sertakan alasan penolakan (misal: <em>"Odometer belum sesuai foto fisik"</em>) agar driver dapat memperbaikinya.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 4: PANDUAN DEPT HEAD / MANAGER                            -->
    <!-- ============================================================== -->
    <div id="guide-panel-manager" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Persetujuan Tingkat Departemen (Dept Head / Manager)
            </h2>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">1</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Notifikasi WhatsApp Masuk</h3>
                    <p class="digi-step-desc">
                        Saat SPV menyetujui pengajuan, Anda akan menerima pesan WhatsApp ringkas berisi nomor pengajuan, nama driver, kode unit, dan total liter yang diajukan.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">2</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Buka Menu "Pengajuan" & Verifikasi Manajerial</h3>
                    <p class="digi-step-desc">
                        Pastikan pengajuan sesuai dengan alokasi anggaran proyek dan rencana kerja unit pada shift tersebut.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">3</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Klik "Approve Manager"</h3>
                    <p class="digi-step-desc">
                        Tanda tangan digital Anda akan tersemat resmi ke dokumen PDF pengajuan, dan sistem langsung meneruskan tiket ke Vendor penyedia solar industri.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">4</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pengajuan Mandiri Dept Head (Hierarki Atasan & Smart Bypass)</h3>
                    <p class="digi-step-desc">
                        Jika Anda mengajukan permohonan BBM untuk kendaraan operasional Anda sendiri, sistem secara otomatis membypass verifikasi SPV (Bawahan). Pengajuan Anda langsung siap direalisasikan oleh Vendor BBM dan kolom SPV pada lembar cetak PDF berstatus <code>N/A</code> dengan tata letak simetris dan sejajar.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 5: PANDUAN VENDOR BBM                                     -->
    <!-- ============================================================== -->
    <div id="guide-panel-vendor" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Persetujuan & Realisasi Pengisian oleh Petugas Vendor BBM (Tahap 4)
            </h2>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">1</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pemeriksaan & Konfirmasi Pengajuan</h3>
                    <p class="digi-step-desc">
                        1. Login menggunakan akun vendor (misal: <em>CV. Adya Mandiri</em>).<br>
                        2. Buka menu <strong>Persetujuan BBM</strong> untuk melihat antrean pengisian yang telah disetujui SPV & Dept Head.<br>
                        3. Periksa nomor pengajuan pada smartphone Driver atau lembar cetak PDF Surat Permintaan Bahan Bakar.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">2</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pengisian Fisik & Pencatatan Realisasi (1 Kali Proses)</h3>
                    <p class="digi-step-desc">
                        1. Lakukan pengisian solar ke tangki unit melalui dispenser BBM / fuel truck.<br>
                        2. Pada detail pengajuan di FuelLink, klik tombol <strong>"Setuju (Tahap 4: Vendor BBM)"</strong>.<br>
                        3. Isi data dispenser fisik: <strong>Liter Aktual Terisi</strong>, <strong>KM / HM Odometer</strong>, dan <strong>Waktu Pengisian Selesai</strong>.<br>
                        4. Klik <strong>"Setujui & Selesaikan Pengisian"</strong>. Persetujuan vendor dan realisasi pengisian langsung tercatat selesai sekaligus tanpa perlu akun terpisah!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 6: PANDUAN ADMINISTRATOR                                  -->
    <!-- ============================================================== -->
    <div id="guide-panel-admin" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Panduan Pengelolaan Master Data & Audit Trail (Admin)
            </h2>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">1</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Navigasi Master Data Cepat (Tombol Burger Bar ☰)</h3>
                    <p class="digi-step-desc">
                        Di halaman Master Data, terdapat tombol bulat <strong>☰</strong> di sisi kiri bar navigasi. Klik tombol ini kapan saja untuk membuka katalog cepat seluruh 7 kategori master data tanpa perlu menggeser layar.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">2</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pencarian & Pengurutan Tabel Otomatis</h3>
                    <p class="digi-step-desc">
                        - <strong>Pencarian Cepat:</strong> Ketik kata kunci pada kotak pencarian di atas tabel untuk memfilter data seketika tanpa me-refresh halaman.<br>
                        - <strong>Urutkan Kolom (A-Z / 1-9):</strong> Klik pada kepala kolom tabel mana saja (seperti No Polisi, Model, Harga) untuk mengurutkan data naik atau turun secara instan.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">3</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Master Unit & Kolom Divisi / Beban Account</h3>
                    <p class="digi-step-desc">
                        Saat mendaftarkan atau memperbarui unit kendaraan di Tab 2, admin dapat mengisi kolom <strong>Divisi / Beban Account</strong> (misal: <em>Service</em>, <em>Spareparts</em>, <em>Hauling</em>). Informasi ini langsung terhubung ke tabel <strong>Evaluasi Konsumsi BBM</strong> serta terunduh di berkas Excel Multi-Sheet (Sheet <em>Master_Kendaraan</em> & <em>Dashboard</em>).
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">4</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Periode Evaluasi BBM Otomatis Berjalan</h3>
                    <p class="digi-step-desc">
                        Menu <strong>Evaluasi</strong> kini secara default selalu membuka periode bulan berjalan saat diakses (misal November 2026), sehingga admin tidak perlu repot memindahkan kalender filter secara manual.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">5</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Ekspor Arsip & Pembersihan Audit Trail</h3>
                    <p class="digi-step-desc">
                        Pada Tab 7 (Audit Trail), terdapat 2 tombol khusus:<br>
                        - <strong>Ekspor Arsip Excel (Hijau):</strong> Klik untuk mengunduh seluruh log audit ke file Excel (.xlsx) sebagai cadangan offline.<br>
                        - <strong>Kelola Retensi Log (Abu-abu):</strong> Digunakan jika ingin membersihkan log lama (misal log > 6 bulan) dengan verifikasi keamanan mengetik <code>BERSIHKAN</code>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 7: TANDA TANGAN DIGITAL (CANVAS & UNGGAH FILE)           -->
    <!-- ============================================================== -->
    <div id="guide-panel-ttd" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Panduan Tanda Tangan Digital Langsung di Layar (Canvas Pad)
            </h2>
            <p style="font-size: 13.5px; color: #64748b; margin: 0 0 16px 0;">
                Tanda tangan digital digunakan pada dokumen PDF resmi (Surat Permintaan Bahan Bakar). Setiap pengguna (Driver, SPV, Dept Head, Vendor) dapat mengatur tanda tangan di menu <strong>Profil Pengguna</strong> (klik nama Anda di pojok kanan atas > Profil).
            </p>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">1</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Pilih Mode "Gambar di Layar (Canvas)"</h3>
                    <p class="digi-step-desc">
                        Di bagian Tanda Tangan Digital pada halaman Profil, pilih tab <strong>"Gambar di Layar (Canvas)"</strong>.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">2</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Goreskan Tanda Tangan Menggunakan Kursor atau Layar Sentuh</h3>
                    <p class="digi-step-desc">
                        - <strong>Di Komputer / Laptop:</strong> Klik dan tahan tombol kiri mouse / touchpad untuk menggambar.<br>
                        - <strong>Di Smartphone / Tablet:</strong> Sentuh langsung permukaan layar menggunakan jari atau stylus pen.<br>
                        - Terdapat <em>garis panduan abu-abu</em> (baseline guide) sebagai patokan posisi tanda tangan agar simetris.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">3</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Koreksi & Pemotongan Otomatis (Smart Auto-Crop)</h3>
                    <p class="digi-step-desc">
                        - Jika goresan salah, klik tombol merah <strong>"Bersihkan"</strong> untuk mengosongkan kanvas dan mengulang kembali.<br>
                        - Saat tombol <strong>"Simpan Tanda Tangan"</strong> diklik, sistem DigiFuel secara cerdas memotong area kosong transparan di sekeliling goresan (<em>auto-crop bounding box</em>), sehingga tanda tangan Anda tampil jelas, proporsional, dan tidak buram pada dokumen resmi PDF.
                    </p>
                </div>
            </div>

            <div class="digi-callout digi-callout-info">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" cy="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <strong>Pilihan Alternatif:</strong> Anda juga tetap dapat mengunggah berkas gambar tanda tangan (PNG transparan atau JPG) melalui tab <em>"Unggah Berkas Gambar"</em> jika sudah memiliki scan tanda tangan di perangkat.
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 8: TANYA JAWAB UMUM (FAQ)                                 -->
    <!-- ============================================================== -->
    <div id="guide-panel-faq" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Pertanyaan yang Sering Diajukan (FAQ)
            </h2>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Apakah data log Audit Trail terhapus setiap bulan?
                </div>
                <p class="digi-faq-a">
                    <strong>Tidak.</strong> Data log audit tersimpan secara permanen di database. Ukuran data teks audit sangat kecil (~1 MB per 5.000 transaksi) sehingga tidak akan memenuhi memori. Jika sewaktu-waktu ingin dirapikan, admin dapat mengunduh <em>Ekspor Arsip Excel</em> terlebih dahulu sebelum menjalankan pembersihan log yang berusia lebih dari 6 bulan.
                </p>
            </div>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Mengapa kolom Mengetahui (SPV) tertulis "N/A" pada lembar PDF?
                </div>
                <p class="digi-faq-a">
                    Kolom SPV berstatus <strong>N/A (Bypassed)</strong> terjadi apabila permohonan diajukan langsung oleh level manajerial (Dept Head / Manager). Sesuai hierarki kerja, pimpinan tidak memerlukan persetujuan dari bawahan (SPV). Tata letak garis tanda tangan tetap dibuat sejajar dan rapi dengan kolom lainnya.
                </p>
            </div>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Bagaimana jika saya salah mengetik jumlah liter saat mengajukan?
                </div>
                <p class="digi-faq-a">
                    Jangan khawatir! Supervisor (SPV) Anda memiliki wewenang untuk memeriksa dan mengoreksi jumlah liter secara langsung sebelum memberikan persetujuan Tahap 1. Anda juga dapat membatalkan pengajuan selama statusnya masih berstatus *Draft* atau *Pending SPV*.
                </p>
            </div>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Bagaimana cara membuka FuelLink dari HP / Smartphone?
                </div>
                <p class="digi-faq-a">
                    Pastikan HP Anda terhubung ke jaringan internet atau Wi-Fi kantor/site. Buka aplikasi Google Chrome atau Safari di HP, lalu akses alamat website resmi FuelLink (atau IP server lokal). Tampilan FuelLink akan otomatis menyesuaikan layar HP Anda dengan nyaman.
                </p>
            </div>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Bagaimana cara mengelompokkan biaya konsumsi BBM per Divisi?
                </div>
                <p class="digi-faq-a">
                    Administrator dapat mengisi kolom <strong>Divisi / Beban Account</strong> pada menu <em>Master Data > Unit Kendaraan</em>. Setelah diisi, seluruh evaluasi konsumsi BBM bulanan di menu <em>Evaluasi</em> maupun unduhan berkas Excel Multi-Sheet akan otomatis menampilkan klasifikasi divisi unit tersebut.
                </p>
            </div>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Kapan notifikasi WhatsApp akan terkirim?
                </div>
                <p class="digi-faq-a">
                    Notifikasi WhatsApp terkirim otomatis hanya kepada penanggung jawab yang gilirannya sedang ditunggu (misal: saat pengajuan baru dibuat, SPV menerima pesan; saat SPV setuju, Manager menerima pesan). Pengguna juga dapat memeriksa notifikasi langsung di ikon lonceng pada bar atas aplikasi.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function switchGuideTab(tabId, btn) {
    // Hide all panels
    document.querySelectorAll('.digi-guide-panel').forEach(panel => {
        panel.style.display = 'none';
    });
    // Remove active class from all pills
    document.querySelectorAll('.digi-guide-pill').forEach(pill => {
        pill.classList.remove('is-active');
    });

    // Show target panel
    const target = document.getElementById('guide-panel-' + tabId);
    if (target) {
        target.style.display = 'block';
    }
    // Set active pill
    if (btn) {
        btn.classList.add('is-active');
    }
}
</script>
@endsection
