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
            Panduan Penggunaan Sistem DigiFuel
        </h1>
        <p style="font-size: 13.5px; color: #cbd5e1; margin: 0; max-width: 720px; line-height: 1.6;">
            Panduan praktis langkah demi langkah yang dirancang agar mudah dipahami oleh siapa saja — mulai dari Driver di lapangan, Pengawas (SPV), Pimpinan Departemen, Petugas Vendor BBM, hingga Administrator sistem.
        </p>
    </div>

    <!-- TAB PILIHAN PERAN (ROLE-BASED TABS) -->
    <div class="digi-guide-nav-pills" id="guideTabsNav">
        <button type="button" class="digi-guide-pill is-active" onclick="switchGuideTab('alur', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            <span>Alur Cepat (5 Tahap)</span>
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
            <span>Vendor & Fuelman</span>
        </button>
        <button type="button" class="digi-guide-pill" onclick="switchGuideTab('admin', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span>Administrator</span>
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
                Sistem DigiFuel menggantikan kupon kertas manual menjadi alur digital terpadu dalam 5 langkah sekuensial:
            </p>

            <div class="digi-flow-bar">
                <div class="digi-flow-box is-highlight">
                    <div class="digi-flow-step-num">Langkah 1</div>
                    <div class="digi-flow-step-name">Pengajuan Permohonan</div>
                    <div class="digi-flow-step-actor">Oleh: Driver / Pemohon</div>
                </div>
                <div class="digi-flow-box">
                    <div class="digi-flow-step-num">Langkah 2</div>
                    <div class="digi-flow-step-name">Approval Tahap 1</div>
                    <div class="digi-flow-step-actor">Oleh: Supervisor (SPV)</div>
                </div>
                <div class="digi-flow-box">
                    <div class="digi-flow-step-num">Langkah 3</div>
                    <div class="digi-flow-step-name">Approval Tahap 2</div>
                    <div class="digi-flow-step-actor">Oleh: Dept Head / Manager</div>
                </div>
                <div class="digi-flow-box">
                    <div class="digi-flow-step-num">Langkah 4</div>
                    <div class="digi-flow-step-name">Approval Vendor</div>
                    <div class="digi-flow-step-actor">Oleh: PIC Vendor Solar</div>
                </div>
                <div class="digi-flow-box is-highlight">
                    <div class="digi-flow-step-num">Langkah 5</div>
                    <div class="digi-flow-step-name">Realisasi Pengisian</div>
                    <div class="digi-flow-step-actor">Oleh: Fuelman (Dispenser)</div>
                </div>
            </div>

            <div class="digi-callout digi-callout-info" style="margin-top: 18px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <strong>Pemberitahuan Otomatis:</strong> Setiap kali ada pengajuan yang membutuhkan persetujuan, sistem otomatis mengirim notifikasi ke nomor WhatsApp pejabat terkait melalui gerbang resmi WhatsApp DigiFuel.
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
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- PANEL 5: PANDUAN VENDOR & FUELMAN                               -->
    <!-- ============================================================== -->
    <div id="guide-panel-vendor" class="digi-guide-panel" style="display: none;">
        <div class="digi-guide-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 14px 0;">
                Validasi Vendor & Realisasi Pengisian oleh Fuelman
            </h2>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">A</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Untuk Perwakilan Vendor BBM (Persetujuan Kuota)</h3>
                    <p class="digi-step-desc">
                        1. Login menggunakan akun vendor (misal: <em>CV. Sudan Mandiri</em>).<br>
                        2. Buka menu <strong>Pengajuan</strong>.<br>
                        3. Periksa ketersediaan stok BBM di storage/fuel truck.<br>
                        4. Klik <strong>"Approve Vendor"</strong>. Kupon resmi dinyatakan aktif dan siap diisi oleh Fuelman di dispenser.
                    </p>
                </div>
            </div>

            <div class="digi-guide-step-row">
                <span class="digi-step-badge">B</span>
                <div class="digi-step-content">
                    <h3 class="digi-step-title">Untuk Petugas Fuelman (Pengisian Fisik Solar)</h3>
                    <p class="digi-step-desc">
                        1. Saat driver tiba di dispenser BBM / Fuel Truck, periksa nomor pengajuan pada HP driver atau lembar cetak PDF.<br>
                        2. Lakukan pengisian solar ke tangki unit.<br>
                        3. Buka menu <strong>Realisasi Pengisian</strong> di DigiFuel.<br>
                        4. Masukkan data aktual: <strong>Liter Aktual Terisi</strong>, <strong>Jam/KM Odometer Terkini</strong>, dan <strong>Waktu Pengisian Selesai</strong>.<br>
                        5. Klik <strong>"Simpan Realisasi Pengisian"</strong>. Transaksi selesai 100%!
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
    <!-- PANEL 7: TANYA JAWAB UMUM (FAQ)                                 -->
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
                    Bagaimana jika saya salah mengetik jumlah liter saat mengajukan?
                </div>
                <p class="digi-faq-a">
                    Jangan khawatir! Supervisor (SPV) Anda memiliki wewenang untuk memeriksa dan mengoreksi jumlah liter secara langsung sebelum memberikan persetujuan Tahap 1. Anda juga dapat membatalkan pengajuan selama statusnya masih berstatus *Draft* atau *Pending SPV*.
                </p>
            </div>

            <div class="digi-faq-item">
                <div class="digi-faq-q">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Bagaimana cara membuka DigiFuel dari HP / Smartphone?
                </div>
                <p class="digi-faq-a">
                    Pastikan HP Anda terhubung ke jaringan Wi-Fi kantor/site yang sama dengan server. Buka aplikasi Google Chrome atau Safari di HP, lalu ketik alamat IP server lokal (contoh: <code>http://10.2.75.153:8000</code>). Tampilan DigiFuel akan otomatis menyesuaikan layar HP Anda dengan nyaman.
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
