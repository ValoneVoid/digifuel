@extends('layouts.app')

@section('title', 'Manajemen Master Data & Audit Trail')

@section('content')
<style>
    /* ========================================================================= */
    /* MASTER DATA MODERN UI & MOBILE RESPONSIVE ENHANCEMENTS                  */
    /* ========================================================================= */

    /* 1. Contextual Quick-Links */
    .digi-quick-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11.5px;
        font-weight: 600;
        color: #0284c7;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        padding: 2px 9px;
        border-radius: 9999px;
        text-decoration: none;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .digi-quick-link:hover {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }

    /* 2. Modern Segmented Stepper Navigation with Left-Right Scroll & Visible Scrollbar */
    .digi-master-tabs-nav-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        width: 100%;
    }
    .digi-tabs-scroll-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        flex-shrink: 0;
        user-select: none;
        margin-bottom: 6px;
    }
    .digi-tabs-scroll-btn:hover:not(:disabled) {
        background: #f1f5f9;
        border-color: #0284c7;
        color: #0284c7;
        transform: scale(1.06);
    }
    .digi-tabs-scroll-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    .digi-master-tabs-scroll {
        flex: 1;
        min-width: 0;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 8px;
        scroll-behavior: smooth;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
    }
    .digi-master-tabs-scroll::-webkit-scrollbar {
        height: 7px;
        display: block;
    }
    .digi-master-tabs-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 9999px;
    }
    .digi-master-tabs-scroll::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 9999px;
        border: 1.5px solid #f1f5f9;
    }
    .digi-master-tabs-scroll::-webkit-scrollbar-thumb:hover {
        background: #0284c7;
    }

    /* Burger Bar Menu & Dropdown Drawer */
    .digi-burger-menu-wrap {
        position: relative;
        flex-shrink: 0;
        z-index: 45;
    }
    .digi-burger-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 50%;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        user-select: none;
        margin-bottom: 6px;
        flex-shrink: 0;
    }
    .digi-burger-btn:hover {
        background: #f1f5f9;
        border-color: #0284c7;
        color: #0284c7;
        transform: scale(1.06);
    }
    .digi-burger-btn.is-open {
        background: #0284c7;
        border-color: #0284c7;
        color: #ffffff;
        transform: scale(1.06);
        box-shadow: 0 3px 10px rgba(2, 132, 199, 0.3);
    }
    .digi-burger-btn.is-open .digi-burger-icon {
        stroke: #ffffff;
    }
    .digi-tabs-divider {
        width: 1px;
        height: 20px;
        background: #cbd5e1;
        margin: 0 3px 6px 3px;
        flex-shrink: 0;
    }

    /* Dropdown Popover (Desktop) */
    .digi-burger-dropdown {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        width: 340px;
        max-width: calc(100vw - 32px);
        max-height: 500px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 16px 38px -6px rgba(15, 23, 42, 0.16), 0 6px 14px -3px rgba(15, 23, 42, 0.08);
        z-index: 100;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: digiDropdownFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes digiDropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .digi-burger-dropdown-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .digi-burger-dropdown-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
    }
    .digi-burger-badge-pill {
        font-size: 11px;
        font-weight: 600;
        color: #0284c7;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        padding: 2px 7px;
        border-radius: 9999px;
    }
    .digi-burger-close-btn {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .digi-burger-close-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .digi-burger-dropdown-body {
        padding: 8px;
        overflow-y: auto;
        max-height: 440px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }
    .digi-burger-dropdown-body::-webkit-scrollbar {
        width: 6px;
    }
    .digi-burger-dropdown-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
    }
    .digi-burger-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px;
        border-radius: 9px;
        text-decoration: none;
        color: inherit;
        border: 1px solid transparent;
        transition: all 0.15s ease;
        background: #ffffff;
    }
    .digi-burger-item:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
        transform: translateX(2px);
    }
    .digi-burger-item.is-active {
        background: #f0f9ff;
        border-color: #bae6fd;
    }
    .digi-burger-item-left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
        flex: 1;
    }
    .digi-burger-item-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        font-size: 11px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .digi-burger-item.is-active .digi-burger-item-step {
        background: #0284c7;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
    }
    .digi-burger-item-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        gap: 1px;
    }
    .digi-burger-item-header {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .digi-burger-item-name {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
    }
    .digi-burger-item.is-active .digi-burger-item-name {
        color: #0369a1;
        font-weight: 700;
    }
    .digi-burger-active-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 10px;
        font-weight: 700;
        color: #0284c7;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        padding: 1px 6px;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .digi-burger-item-desc {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .digi-burger-item-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        margin-left: 8px;
    }
    .digi-burger-item-count {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        background: #f1f5f9;
        padding: 2px 7px;
        border-radius: 6px;
    }
    .digi-burger-item.is-active .digi-burger-item-count {
        color: #0284c7;
        background: #e0f2fe;
    }
    .digi-burger-item-arrow {
        color: #cbd5e1;
        transition: transform 0.15s ease, color 0.15s ease;
    }
    .digi-burger-item:hover .digi-burger-item-arrow {
        color: #0284c7;
        transform: translateX(2px);
    }

    .digi-bottom-sheet-handle {
        display: none;
    }
    .digi-burger-backdrop {
        display: none;
    }

    /* Mobile Bottom-Sheet Drawer */
    @media (max-width: 640px) {
        .digi-bottom-sheet-handle {
            display: block;
            width: 38px;
            height: 4px;
            background: #cbd5e1;
            border-radius: 9999px;
            margin: 10px auto 4px auto;
            flex-shrink: 0;
        }
        .digi-burger-dropdown {
            position: fixed;
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            max-width: 100vw;
            max-height: 80vh;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -12px 36px rgba(15, 23, 42, 0.25);
            z-index: 1050;
            border-bottom: none;
            animation: digiBottomSheetSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes digiBottomSheetSlideUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }
        .digi-burger-dropdown-header {
            padding: 12px 18px 10px 18px;
        }
        .digi-burger-dropdown-body {
            max-height: calc(80vh - 75px);
            padding: 8px 14px calc(24px + env(safe-area-inset-bottom, 16px)) 14px;
            -webkit-overflow-scrolling: touch;
        }
        .digi-burger-item {
            padding: 10px 12px;
            min-height: 50px;
        }
        .digi-burger-item-desc {
            white-space: normal;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.35;
        }
        .digi-burger-backdrop.is-open {
            display: block !important;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: 1040;
            animation: digiBackdropFadeIn 0.2s ease;
        }
        @keyframes digiBackdropFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    }

    /* Table Horizontal Scrollbar Styling */
    .digi-table-responsive {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
        -webkit-overflow-scrolling: touch;
    }
    .digi-table-responsive::-webkit-scrollbar {
        height: 8px;
        display: block;
    }
    .digi-table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
    }
    .digi-table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
        border: 2px solid #f1f5f9;
    }
    .digi-table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .digi-tab-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        flex-shrink: 0;
    }
    .digi-tab-pill:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
    }
    .digi-tab-pill.is-active {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
    }
    .digi-tab-pill .tab-step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 11px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
    }
    .digi-tab-pill.is-active .tab-step-num {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }
    .digi-tab-pill .tab-badge-count {
        font-size: 11.5px;
        font-weight: 600;
        opacity: 0.85;
    }

    /* 3. Form Visual States (Create Mode vs Edit Mode) */
    .digi-form-box {
        transition: all 0.25s ease;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        background: #ffffff;
        margin-bottom: 24px;
    }
    .digi-form-box.is-editing {
        border-color: #f59e0b !important;
        background: #fffbeb !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15), 0 8px 24px rgba(245, 158, 11, 0.08) !important;
    }
    .digi-edit-pill-badge {
        display: none;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        font-weight: 700;
        color: #b45309;
        background: #fef3c7;
        border: 1px solid #fde68a;
        padding: 3px 10px;
        border-radius: 9999px;
        margin-left: 8px;
    }
    .digi-form-box.is-editing .digi-edit-pill-badge {
        display: inline-flex;
    }

    /* 4. Responsive Grid Classes */
    .digi-responsive-grid-4 {
        display: grid;
        grid-template-columns: 1.2fr 1.2fr 1fr 1fr;
        gap: 14px;
        align-items: flex-end;
    }
    .digi-responsive-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        align-items: flex-end;
    }
    @media (max-width: 900px) {
        .digi-responsive-grid-4,
        .digi-responsive-grid-3,
        .digi-form-grid-3,
        .digi-form-grid-4 {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }
        .digi-form-box {
            padding: 14px;
        }
        .digi-input, .digi-select {
            min-height: 42px !important;
            font-size: 14px !important;
        }
    }

    /* 5. Table Instant Client-Side Search Bar */
    .digi-table-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }
    .digi-table-search-input {
        min-width: 260px;
        max-width: 380px;
        padding: 8px 12px 8px 34px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat 10px center;
        color: #1e293b;
        outline: none;
        transition: all 0.2s ease;
    }
    .digi-table-search-input:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    @media (max-width: 640px) {
        .digi-table-search-input {
            width: 100% !important;
            max-width: 100% !important;
        }
    }

    /* 6. Soft Pastel Status Badges with Glowing Dot */
    .digi-status-dot-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }
    .digi-status-dot-pill.is-active {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .digi-status-dot-pill.is-inactive {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .digi-status-dot-pill .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }
    .digi-status-dot-pill.is-active .dot {
        background: #22c55e;
        box-shadow: 0 0 6px #22c55e;
    }
    .digi-status-dot-pill.is-inactive .dot {
        background: #ef4444;
    }

    /* 7. Action Button Group */
    .digi-action-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
    }
    .btn-action-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 5px 11px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        text-decoration: none;
    }
    .btn-action-pill:active {
        transform: scale(0.97);
    }

    /* 8. Thumbnail Background */
    .digi-bg-thumb {
        width: 125px;
        height: 68px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #cbd5e1;
        display: block;
    }
    .digi-bg-thumb.is-active {
        border-color: #22c55e;
    }

    /* 9. Smooth Hover on Table Rows */
    .digi-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .digi-table tbody tr:hover {
        background-color: #f8fafc;
    }
</style>

<div class="digi-card">
    <div class="digi-table-top" style="margin-bottom: 18px;">
        <div>
            <h1 class="digi-table-title">Manajemen Master Data & Konfigurasi DigiFuel</h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 4px 0 0 0;">
                Kelola Data Unit, Employee (DIGISIM Ready), Area, Vendor BBM, Penugasan Approver per Area, Standar Konsumsi BBM, Harga BBM per Vendor, Akun Pengguna, dan Audit Trail.
            </p>
        </div>
    </div>

    <!-- Navigation Tabs Sesuai Alur Kebutuhan Operasional (Desktop & Mobile Swipeable) -->
    @php
        $getCount = fn($col) => method_exists($col, 'total') ? $col->total() : (is_countable($col) ? count($col) : 0);
        $primaryTabs = [
            'areas' => [
                'step' => '1',
                'name' => 'Wilayah & Area',
                'desc' => 'Wilayah kerja operasional, site penugasan, pool & rayon',
                'count' => $getCount($areas)
            ],
            'vendors' => [
                'step' => '2',
                'name' => 'Perusahaan BBM & Harga',
                'desc' => 'Penyedia solar industri, PIC vendor & penetapan harga aktif',
                'count' => $getCount($vendors)
            ],
            'unit_vendors' => [
                'step' => '3',
                'name' => 'Perusahaan Rental Unit',
                'desc' => 'Perusahaan pemilik / penyedia sewa unit kendaraan operasional',
                'count' => $getCount($unitVendors)
            ],
            'units' => [
                'step' => '4',
                'name' => 'Unit Kendaraan & Standar',
                'desc' => 'Plat nomor, tipe unit, kapasitas tangki & standar rasio KM/L',
                'count' => $getCount($vehicles)
            ],
            'users' => [
                'step' => '5',
                'name' => 'Pengguna, Karyawan & Penugasan',
                'desc' => 'Akun login, karyawan driver / employee & matriks approval per area',
                'count' => $getCount($users)
            ],
            'backgrounds' => [
                'step' => '6',
                'name' => 'Background Sistem',
                'desc' => 'Kustomisasi gambar wallpaper aplikasi & halaman login',
                'count' => $getCount($backgrounds ?? collect())
            ],
            'audit' => [
                'step' => '7',
                'name' => 'Audit Trail',
                'desc' => 'Catatan log aktivitas perubahan & riwayat aksi sistem',
                'count' => $getCount($auditLogs)
            ],
        ];
    @endphp

    <div class="digi-master-tabs-nav-wrapper">
        <!-- Burger Menu Bar (Clean Universal Master Data Selector) -->
        <div class="digi-burger-menu-wrap" id="masterBurgerWrap">
            <button type="button" 
                    class="digi-burger-btn" 
                    id="masterBurgerBtn" 
                    onclick="toggleMasterBurgerMenu(event)" 
                    aria-expanded="false" 
                    aria-controls="masterBurgerDropdown" 
                    title="Daftar Semua Master Data (Langkah 1 - 7)"
                    aria-label="Daftar Semua Master Data">
                <svg class="digi-burger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Dropdown Popover & Drawer -->
            <div class="digi-burger-dropdown" id="masterBurgerDropdown" style="display: none;">
                <div class="digi-bottom-sheet-handle"></div>
                <div class="digi-burger-dropdown-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="digi-burger-dropdown-title">Katalog Master Data</span>
                        <span class="digi-burger-badge-pill">7 Kategori</span>
                    </div>
                    <button type="button" class="digi-burger-close-btn" onclick="closeMasterBurgerMenu(event)" title="Tutup menu">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="digi-burger-dropdown-body">
                    @foreach($primaryTabs as $key => $tabInfo)
                        <a href="{{ route('admin.master', array_merge(request()->query(), ['tab' => $key, 'page' => 1])) }}"
                           class="digi-burger-item {{ $tab === $key ? 'is-active' : '' }}"
                           title="Buka Langkah {{ $tabInfo['step'] }}: {{ $tabInfo['name'] }}">
                            <div class="digi-burger-item-left">
                                <span class="digi-burger-item-step">{{ $tabInfo['step'] }}</span>
                                <div class="digi-burger-item-text">
                                    <div class="digi-burger-item-header">
                                        <span class="digi-burger-item-name">{{ $tabInfo['name'] }}</span>
                                        @if($tab === $key)
                                            <span class="digi-burger-active-tag">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Aktif
                                            </span>
                                        @endif
                                    </div>
                                    <span class="digi-burger-item-desc">{{ $tabInfo['desc'] }}</span>
                                </div>
                            </div>
                            <div class="digi-burger-item-right">
                                <span class="digi-burger-item-count">{{ $tabInfo['count'] }} data</span>
                                <svg class="digi-burger-item-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <!-- Mobile Backdrop -->
            <div class="digi-burger-backdrop" id="masterBurgerBackdrop" onclick="closeMasterBurgerMenu(event)" style="display: none;"></div>
        </div>

        <div class="digi-tabs-divider"></div>

        <button type="button" class="digi-tabs-scroll-btn btn-left" id="masterTabsScrollLeft" onclick="scrollMasterTabs(-240)" title="Geser tab ke kiri" aria-label="Geser tab ke kiri">
            ‹
        </button>
        <div class="digi-master-tabs-scroll" id="masterTabsScroll">
            @foreach($primaryTabs as $key => $tabInfo)
                <a href="{{ route('admin.master', array_merge(request()->query(), ['tab' => $key, 'page' => 1])) }}"
                   class="digi-tab-pill {{ $tab === $key ? 'is-active' : '' }}"
                   title="Langkah {{ $tabInfo['step'] }}: {{ $tabInfo['name'] }}">
                    <span class="tab-step-num">{{ $tabInfo['step'] }}</span>
                    <span>{{ $tabInfo['name'] }}</span>
                    <span class="tab-badge-count">({{ $tabInfo['count'] }})</span>
                </a>
            @endforeach
        </div>
        <button type="button" class="digi-tabs-scroll-btn btn-right" id="masterTabsScrollRight" onclick="scrollMasterTabs(240)" title="Geser tab ke kanan" aria-label="Geser tab ke kanan">
            ›
        </button>
    </div>

    <div class="digi-mobile-swipe-hint">
        <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>Geser tab & tabel ke samping untuk melihat seluruh data</span>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 1: UNITS / VEHICLES                                      -->
    <!-- ============================================================ -->
    @if($tab === 'units')
        <form id="unitForm" action="{{ route('admin.master.vehicle') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="unit_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="unitFormTitle" style="margin: 0;">Langkah 4: Tambah Data Unit Kendaraan & Standar Konsumsi BBM</h3>
                    <span class="digi-edit-pill-badge" id="unitEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                    <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0 0; width: 100%;">
                        Daftarkan unit operasional, vendor rental, area default, kapasitas tangki, serta target rasio konsumsi BBM (KM/L) sekaligus.
                    </p>
                </div>
                <button type="button" id="unitCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetUnitForm()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Unit Code (Unik) <span class="digi-required">*</span></label>
                    <input type="text" name="unit_code" id="unit_code" class="digi-input" placeholder="Contoh: UT 208 DA 8191" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nomor Polisi <span class="digi-required">*</span></label>
                    <input type="text" name="police_no" id="unit_police_no" class="digi-input" placeholder="Contoh: DA 8191 UT" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Model / Tipe Unit <span class="digi-required">*</span></label>
                    <input type="text" name="model" id="unit_model" class="digi-input" placeholder="Contoh: B/U FMC JB HDX TRITON" required>
                </div>
            </div>
            <div class="digi-responsive-grid-4" style="margin-bottom: 0;">
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Perusahaan Rental Unit <span class="digi-required">*</span></span>
                        <a href="{{ route('admin.master', ['tab' => 'unit_vendors']) }}" class="digi-quick-link" title="Buka master Perusahaan Rental Unit">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="22" x2="9" y2="22.01"></line><line x1="15" y1="22" x2="15" y2="22.01"></line><line x1="9" y1="6" x2="9" y2="6.01"></line><line x1="15" y1="6" x2="15" y2="6.01"></line><line x1="9" y1="10" x2="9" y2="10.01"></line><line x1="15" y1="10" x2="15" y2="10.01"></line><line x1="9" y1="14" x2="9" y2="14.01"></line><line x1="15" y1="14" x2="15" y2="14.01"></line><line x1="9" y1="18" x2="9" y2="18.01"></line><line x1="15" y1="18" x2="15" y2="18.01"></line></svg>Kelola Rental Unit &rarr;
                        </a>
                    </label>
                    <select name="unit_vendor_id" id="unit_vendor_id" class="digi-select" required>
                        <option value="">-- Pilih Perusahaan Rental Unit --</option>
                        @foreach($allUnitVendors as $uv)
                            <option value="{{ $uv->id }}">{{ $uv->name }} ({{ $uv->code ?? 'Perusahaan' }})</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="meter_type" id="unit_meter_type" value="km">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Area Default</span>
                        <a href="{{ route('admin.master', ['tab' => 'areas']) }}" class="digi-quick-link" title="Buka master Area Operasional">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>Kelola Area &rarr;
                        </a>
                    </label>
                    <select name="default_area_id" id="unit_default_area_id" class="digi-select">
                        <option value="">-- Pilih Area Default --</option>
                        @foreach($allAreas as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kapasitas Tangki (L)</label>
                    <input type="number" name="tank_capacity" id="unit_tank_capacity" class="digi-input" value="80">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Standar Rasio (KM/L)</label>
                    <input type="number" step="0.01" min="0.01" name="standard_consumption" id="unit_standard_consumption" class="digi-input" placeholder="Misal: 8.0">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                <button type="submit" id="unitSubmitBtn" class="btn-digi btn-digi-blue">Simpan Data Unit</button>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari kode unit, nomor polisi, model, rental, area..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($vehicles, 'total') ? $vehicles->total() : count($vehicles) }} unit</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $vehicles])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Unit Code</th>
                        <th>Nomor Polisi</th>
                        <th>Tipe / Model Unit</th>
                        <th>Perusahaan Rental Unit (Rental / Internal)</th>
                        <th>Area Default</th>
                        <th>Kapasitas Tangki</th>
                        <th>Standar BBM</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $idx => $v)
                        <tr>
                            <td>{{ $vehicles->firstItem() ? ($vehicles->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight: 700; color: #0284c7;">{{ $v->unit_code }}</td>
                            <td>{{ $v->police_no }}</td>
                            <td>{{ $v->model ?? $v->unit_type }}</td>
                            <td>
                                @if($v->unitVendor)
                                    <span class="digi-badge digi-badge-blue" title="{{ $v->unitVendor->name }}">{{ $v->unitVendor->name }}</span>
                                @else
                                    <span class="digi-badge digi-badge-gray">Internal UT</span>
                                @endif
                            </td>
                            <td>
                                @if($v->defaultArea)
                                    <span class="digi-badge digi-badge-green">{{ $v->defaultArea->name }}</span>
                                @else
                                    <span style="color: #94a3b8; font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td>{{ (float)($v->tank_capacity ?? 80) }} L</td>
                            <td>
                                @if($v->activeFuelStandard)
                                    <span class="digi-badge digi-badge-blue" style="font-weight: 700;">{{ (float)$v->activeFuelStandard->standard_value }} KM/L</span>
                                @else
                                    <span style="color: #94a3b8; font-size: 12px;">Belum diatur</span>
                                @endif
                            </td>
                            <td>
                                <span class="digi-status-dot-pill {{ $v->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($v) }}" onclick="editUnit(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $v->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="unit" data-id="{{ $v->id }}" data-tab="units" data-name="{{ $v->unit_code }}" data-active="{{ $v->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.units.destroy', $v->id) }}" data-entity="Data Unit / Kendaraan" data-name="{{ $v->unit_code }} ({{ $v->police_no }})"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $vehicles])
    @endif

    <!-- ============================================================ -->
    <!-- TAB: UNIT VENDORS (PENYEDIA UNIT KENDARAAN)                  -->
    <!-- ============================================================ -->
    @if($tab === 'unit_vendors')
        <form id="unitVendorForm" action="{{ route('admin.master.unit-vendor') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="unit_vendor_id_input" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="unitVendorFormTitle" style="margin: 0;">Langkah 3: Tambah Data Perusahaan Rental Unit (Penyedia Unit)</h3>
                    <span class="digi-edit-pill-badge" id="unitVendorEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                    <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0 0; width: 100%;">
                        Daftarkan rekanan perusahaan penyedia unit sewa / rental kendaraan operasional.
                    </p>
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <a href="{{ route('admin.master', ['tab' => 'units']) }}" class="digi-quick-link" title="Buka master Unit Kendaraan">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>Kelola Unit Terdaftar &rarr;
                    </a>
                    <button type="button" id="unitVendorCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetUnitVendorForm()">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                    </button>
                </div>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Kode Perusahaan</label>
                    <input type="text" name="code" id="uv_code" class="digi-input" placeholder="Contoh: 01 (Kosongkan utk auto)">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Perusahaan Rental Unit <span class="digi-required">*</span></label>
                    <input type="text" name="name" id="uv_name" class="digi-input" placeholder="Contoh: PT Serasi Autoraya (TRAC)" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kontak PIC / Petugas</label>
                    <input type="text" name="contact_person" id="uv_contact" class="digi-input" placeholder="Contoh: Bpk. Wahyu TRAC">
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">No. Telepon / WhatsApp PIC</label>
                    <input type="text" name="phone" id="uv_phone" class="digi-input" placeholder="0812xxxxxxx">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Alamat / Pool Unit</label>
                    <input type="text" name="address" id="uv_address" class="digi-input" placeholder="Contoh: Site Satui Km 4">
                </div>
                <div>
                    <button type="submit" id="unitVendorSubmitBtn" class="btn-digi btn-digi-blue">Simpan Perusahaan Rental</button>
                </div>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari kode perusahaan, nama rental, PIC, telepon..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($unitVendors, 'total') ? $unitVendors->total() : count($unitVendors) }} perusahaan</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $unitVendors])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kode Perusahaan</th>
                        <th>Nama Perusahaan Rental Unit</th>
                        <th>Kontak PIC</th>
                        <th>Telepon</th>
                        <th>Total Unit Terdaftar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unitVendors as $idx => $uv)
                        <tr>
                            <td>{{ $unitVendors->firstItem() ? ($unitVendors->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $uv->code ?? 'UVND-0' . $uv->id }}</td>
                            <td style="font-weight:600;">{{ $uv->name }}</td>
                            <td>{{ $uv->contact_person ?? '-' }}</td>
                            <td>{{ $uv->phone ?? '-' }}</td>
                            <td>
                                <span class="digi-badge digi-badge-blue">{{ $uv->vehicles_count ?? $uv->vehicles()->count() }} Unit</span>
                            </td>
                            <td>
                                <span class="digi-status-dot-pill {{ $uv->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $uv->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($uv) }}" onclick="editUnitVendor(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $uv->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="unit_vendor" data-id="{{ $uv->id }}" data-tab="unit_vendors" data-name="{{ $uv->name }}" data-active="{{ $uv->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $uv->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.unit-vendors.destroy', $uv->id) }}" data-entity="Data Perusahaan Rental Unit" data-name="{{ ($uv->code ? $uv->code . ' - ' : '') . $uv->name }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 24px;">Belum ada data Perusahaan Rental Unit. Silakan tambahkan pada formulir di atas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $unitVendors])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 2: EMPLOYEES (DIGISIM READY)                             -->
    <!-- ============================================================ -->
    @if($tab === 'employees')
        <form id="employeeForm" action="{{ route('admin.master.employee') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="emp_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="empFormTitle" style="margin: 0;">Tambah Data Employee (Lokal & Integrasi DIGISIM)</h3>
                    <span class="digi-edit-pill-badge" id="empEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <a href="{{ route('admin.master', ['tab' => 'users']) }}" class="digi-quick-link" title="Buka master Akun Pengguna">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>Kelola Akun Pengguna &rarr;
                    </a>
                    <button type="button" id="empCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetEmployeeForm()">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                    </button>
                </div>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">NRP / Nomor Karyawan <span class="digi-required">*</span></label>
                    <input type="text" name="nrp" id="emp_nrp" class="digi-input" placeholder="Contoh: 80124024" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Lengkap <span class="digi-required">*</span></label>
                    <input type="text" name="name" id="emp_name" class="digi-input" placeholder="Nama Karyawan" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jabatan & Departemen</label>
                    <input type="text" name="position" id="emp_position" class="digi-input" placeholder="Driver / Operator">
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Perusahaan Asal</label>
                    <input type="text" name="company" id="emp_company" class="digi-input" value="PT UNITED TRACTORS Tbk - SITE SATUI" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Sumber Data & Ref Eksternal (DIGISIM)</label>
                    <div style="display:flex; gap:6px;">
                        <select name="source" id="emp_source" class="digi-select" style="width:120px;">
                            <option value="local">Lokal</option>
                            <option value="digisim">DIGISIM</option>
                        </select>
                        <input type="text" name="external_reference" id="emp_ext_ref" class="digi-input" placeholder="Ref ID simpers">
                    </div>
                </div>
                <div>
                    <button type="submit" id="empSubmitBtn" class="btn-digi btn-digi-blue">Simpan Employee</button>
                </div>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari NRP, nama employee, jabatan, perusahaan..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($employees, 'total') ? $employees->total() : count($employees) }} employee</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $employees])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>NRP</th>
                        <th>Nama Employee</th>
                        <th>Jabatan</th>
                        <th>Perusahaan</th>
                        <th>Sumber Integrasi</th>
                        <th>Referensi Eksternal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $idx => $emp)
                        <tr>
                            <td>{{ $employees->firstItem() ? ($employees->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $emp->nrp }}</td>
                            <td style="font-weight:600;">{{ $emp->name }}</td>
                            <td>{{ $emp->position }}</td>
                            <td>{{ $emp->company }}</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($emp->source) }}</span></td>
                            <td>{{ $emp->external_reference ?? '-' }}</td>
                            <td>
                                <span class="digi-status-dot-pill {{ $emp->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $emp->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($emp) }}" onclick="editEmployee(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $emp->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="employee" data-id="{{ $emp->id }}" data-tab="employees" data-name="{{ $emp->name }}" data-active="{{ $emp->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $emp->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.employees.destroy', $emp->id) }}" data-entity="Data Employee" data-name="{{ $emp->nrp . ' - ' . $emp->name }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $employees])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 3: AREAS                                                 -->
    <!-- ============================================================ -->
    @if($tab === 'areas')
        <form id="areaForm" action="{{ route('admin.master.area') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="area_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="areaFormTitle" style="margin: 0;">Langkah 1: Tambah Area Operasional</h3>
                    <span class="digi-edit-pill-badge" id="areaEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <button type="button" id="areaCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetAreaForm()">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                    </button>
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Kode Area</label>
                    <input type="text" name="code" id="area_code" class="digi-input" placeholder="Contoh: 01 (Kosongkan utk auto)">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Area Operasional <span class="digi-required">*</span></label>
                    <input type="text" name="name" id="area_name" class="digi-input" placeholder="Contoh: SDU (Sungai Danau)" required>
                </div>
                <div>
                    <button type="submit" id="areaSubmitBtn" class="btn-digi btn-digi-blue">Simpan Area</button>
                </div>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari kode area, nama area operasional..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($areas, 'total') ? $areas->total() : count($areas) }} area</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $areas])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kode Area</th>
                        <th>Nama Area Operasional</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($areas as $idx => $a)
                        <tr>
                            <td>{{ $areas->firstItem() ? ($areas->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $a->code ?? 'AREA-' . $a->id }}</td>
                            <td style="font-weight:600;">{{ $a->name }}</td>
                            <td>
                                <span class="digi-status-dot-pill {{ $a->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($a) }}" onclick="editArea(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $a->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="area" data-id="{{ $a->id }}" data-tab="areas" data-name="{{ $a->name }}" data-active="{{ $a->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.areas.destroy', $a->id) }}" data-entity="Area Operasional" data-name="{{ ($a->code ? $a->code . ' - ' : '') . $a->name }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $areas])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 4: VENDORS (PERUSAHAAN PENYEDIA BBM & HARGA SOLAR)       -->
    <!-- ============================================================ -->
    @if($tab === 'vendors')
        <form id="vendorForm" action="{{ route('admin.master.vendor') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="vendor_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="vendorFormTitle" style="margin: 0;">Langkah 2: Tambah Data Perusahaan & Harga Solar</h3>
                    <span class="digi-edit-pill-badge" id="vendorEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                    <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0 0; width: 100%;">
                        Kelola data perusahaan penyedia solar industri serta penetapan harga per liter aktif langsung dalam satu formulir terpadu.
                    </p>
                </div>
                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <button type="button" id="vendorCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetVendorForm()">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                    </button>
                </div>
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Kode & Nama Perusahaan <span class="digi-required">*</span></label>
                    <div style="display: flex; gap: 6px;">
                        <input type="text" name="code" id="vendor_code" class="digi-input" placeholder="Kode (01)" style="width: 110px;" title="Kosongkan untuk auto (01, 02, ...)">
                        <input type="text" name="name" id="vendor_name" class="digi-input" placeholder="Contoh: Cv. Sudan Mandiri" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Kontak Petugas & No. Telepon</label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="contact_person" id="vendor_contact" class="digi-input" placeholder="Nama PIC">
                        <input type="text" name="phone" id="vendor_phone" class="digi-input" placeholder="0812xxxx">
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jenis BBM & Harga Aktif (Rp/Liter)</label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" name="fuel_type" id="vendor_fuel_type" class="digi-input" value="Solar Industri B35" style="flex: 1.6; min-width: 160px;" placeholder="Jenis BBM (Solar Industri B35)">
                        <input type="number" step="1" min="1" name="price_per_liter" id="vendor_price_per_liter" class="digi-input" style="flex: 1; min-width: 110px;" placeholder="Rp/Liter (27000)">
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                <button type="submit" id="vendorSubmitBtn" class="btn-digi btn-digi-blue">Simpan Data Perusahaan</button>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari kode perusahaan, nama vendor bbm, PIC, telepon..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($vendors, 'total') ? $vendors->total() : count($vendors) }} perusahaan</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $vendors])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kode Perusahaan</th>
                        <th>Nama Perusahaan</th>
                        <th>Kontak Petugas</th>
                        <th>Telepon</th>
                        <th>Harga Solar Aktif</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $idx => $ven)
                        <tr>
                            <td>{{ $vendors->firstItem() ? ($vendors->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $ven->code ?? 'VND-0' . $ven->id }}</td>
                            <td style="font-weight:600;">{{ $ven->name }}</td>
                            <td>{{ $ven->contact_person ?? 'Petugas Operasional' }}</td>
                            <td>{{ $ven->phone ?? '-' }}</td>
                            <td>
                                @if($ven->activeFuelPrice)
                                    <span class="digi-badge digi-badge-blue" style="font-weight: 700;">
                                        Rp {{ number_format($ven->activeFuelPrice->price_per_liter, 0, ',', '.') }} / L
                                    </span>
                                @else
                                    <span style="color: #94a3b8; font-size: 12px;">Belum diatur</span>
                                @endif
                            </td>
                            <td>
                                <span class="digi-status-dot-pill {{ $ven->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $ven->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($ven) }}" onclick="editVendor(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $ven->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="vendor" data-id="{{ $ven->id }}" data-tab="vendors" data-name="{{ $ven->name }}" data-active="{{ $ven->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $ven->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.vendors.destroy', $ven->id) }}" data-entity="Data Perusahaan" data-name="{{ ($ven->code ? $ven->code . ' - ' : '') . $ven->name }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $vendors])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 5: AREA APPROVERS                                        -->
    <!-- ============================================================ -->
    @if($tab === 'approvers')
        <form id="approverForm" action="{{ route('admin.master.approver') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="aa_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="aaFormTitle" style="margin: 0;">Penugasan Approver & Petugas Berdasarkan Area</h3>
                    <span class="digi-edit-pill-badge" id="aaEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                </div>
                <button type="button" id="aaCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetApproverForm()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                </button>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Area Operasional <span class="digi-required">*</span></span>
                        <a href="{{ route('admin.master', ['tab' => 'areas']) }}" class="digi-quick-link" title="Buka master Area Operasional">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>Kelola Area &rarr;
                        </a>
                    </label>
                    <select name="area_id" id="aa_area_id" class="digi-select" required>
                        @foreach(($allAreas ?? $areas) as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Pengguna & Peran <span class="digi-required">*</span></span>
                        <a href="{{ route('admin.master', ['tab' => 'users']) }}" class="digi-quick-link" title="Buka master Akun Pengguna">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>Kelola Pengguna &rarr;
                        </a>
                    </label>
                    <div style="display:flex; gap:6px;">
                        <select name="user_id" id="aa_user_id" class="digi-select" required>
                            @foreach(($allUsers ?? $users) as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->jabatan }})</option>
                            @endforeach
                        </select>
                        <select name="role_type" id="aa_role_type" class="digi-select" style="width: 145px;" required>
                            <option value="spv">SPV</option>
                            <option value="manager">Dept Head/Mgr</option>
                            <option value="vendor">Vendor BBM</option>
                            <option value="fuelman">Fuel Man</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit" id="aaSubmitBtn" class="btn-digi btn-digi-blue">Simpan Penugasan</button>
                </div>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari area operasional, nama approver, tanggung jawab..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($areaApprovers, 'total') ? $areaApprovers->total() : count($areaApprovers) }} penugasan</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $areaApprovers])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Area Operasional</th>
                        <th>Nama Petugas / Approver</th>
                        <th>Tanggung Jawab</th>
                        <th>Mulai Penugasan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($areaApprovers as $idx => $aa)
                        <tr>
                            <td>{{ $areaApprovers->firstItem() ? ($areaApprovers->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $aa->area?->name }}</td>
                            <td style="font-weight:600;">{{ $aa->user?->name }} ({{ $aa->user?->username }})</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($aa->role_type) }}</span></td>
                            <td>{{ $aa->start_date?->format('d/m/Y') ?? '01/01/2025' }}</td>
                            <td>
                                <span class="digi-status-dot-pill {{ $aa->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $aa->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($aa) }}" onclick="editApprover(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $aa->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="approver" data-id="{{ $aa->id }}" data-tab="approvers" data-name="{{ ($aa->area?->name ?? '') . ' - ' . ($aa->user?->name ?? '') }}" data-active="{{ $aa->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $aa->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.approvers.destroy', $aa->id) }}" data-entity="Penugasan Approver Area" data-name="{{ ($aa->area?->name ?? '') . ' - ' . ($aa->user?->name ?? '') . ' (' . strtoupper($aa->role_type) . ')' }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $areaApprovers])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 6: FUEL STANDARDS                                        -->
    <!-- ============================================================ -->
    @if($tab === 'standards')
        <!-- Penjelasan Singkat Standar Konsumsi BBM Sesuai Aturan Perusahaan -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 20px; line-height: 1.2;">ℹ️</span>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0f172a;">Tentang Standar Konsumsi BBM</h4>
                    <p style="margin: 0 0 8px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                        Standar konsumsi BBM merupakan nilai acuan yang digunakan DigiFuel untuk mengevaluasi penggunaan BBM setiap unit. Nilai standar diperoleh dari ketentuan perusahaan dan dimasukkan oleh Admin secara manual. Standar cukup dimasukkan satu kali untuk setiap unit dan hanya perlu diperbarui apabila terdapat perubahan ketentuan perusahaan atau penambahan unit baru.
                    </p>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px; color: #64748b; line-height: 1.4;">
                        <span>• <strong>KM/Liter (KM/L):</strong> Standar efisiensi jarak tempuh per liter untuk seluruh unit kendaraan & alat berat operasional.</span>
                        <span>• <strong>Komparasi:</strong> Sistem membandingkan konsumsi aktual dengan standar untuk mendeteksi potensi pemborosan BBM.</span>
                        <span>• <strong>Edit vs Tambah Baru:</strong> Gunakan <em>Edit</em> untuk memperbaiki salah ketik. Gunakan <em>Tambah Baru</em> jika perusahaan menetapkan standar baru agar histori periode lama tetap tersimpan utuh.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulir Sederhana Pengaturan Standar BBM -->
        <form id="standardForm" action="{{ route('admin.master.standard') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="std_id" value="">
            <input type="hidden" name="standard_type" id="std_type" value="km_per_liter">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="stdFormTitle" style="margin: 0;">Pengaturan Standar Konsumsi BBM Unit</h3>
                    <span class="digi-edit-pill-badge" id="stdEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                    <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0 0; width: 100%;">
                        Pilih unit, masukkan nilai standar konsumsi, dan tentukan tanggal mulai berlaku.
                    </p>
                </div>
                <button type="button" id="stdCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetStandardForm()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                </button>
            </div>

            <div class="digi-form-grid-3">
                <!-- 1. Unit Code Dropdown (Hanya unit aktif untuk standar baru) -->
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Pilih Unit Kendaraan <span class="digi-required">*</span></span>
                        <a href="{{ route('admin.master', ['tab' => 'units']) }}" class="digi-quick-link" title="Buka master Unit Kendaraan">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>Kelola Unit Kendaraan &rarr;
                        </a>
                    </label>
                    <select name="vehicle_id" id="std_vehicle_id" class="digi-select" onchange="onVehicleSelectChange(this)" required>
                        <option value="" disabled selected>-- Pilih Unit --</option>
                        @foreach(($allVehicles ?? $vehicles) as $v)
                            <option value="{{ $v->id }}"
                                data-type="{{ $v->unit_type ?? $v->model }}"
                                data-code="{{ $v->unit_code }}"
                                data-active="{{ $v->is_active ? '1' : '0' }}">
                                {{ $v->unit_code }} ({{ $v->unit_type ?? $v->model }}){{ !$v->is_active ? ' - [Nonaktif]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <!-- Indikator otomatis jenis unit & tipe meter -->
                    <div id="std_vehicle_info" style="font-size: 12px; color: #0284c7; margin-top: 5px; display: none;">
                        Jenis Unit: <strong id="std_info_type">-</strong> | Meter: <strong>KM</strong>
                    </div>
                </div>

                <!-- 2. Nilai Standar Konsumsi dengan Satuan Dinamis -->
                <div class="digi-form-group">
                    <label class="digi-form-label">Nilai Standar Konsumsi <span class="digi-required">*</span></label>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <input type="number" step="0.01" min="0.01" name="standard_value" id="std_value" class="digi-input" placeholder="Contoh: 8.0" required>
                        <span id="std_unit_badge" style="background: #f1f5f9; padding: 7px 14px; border-radius: 6px; font-weight: 700; color: #334155; font-size: 13px; border: 1px solid #cbd5e1; white-space: nowrap;">
                            KM/L
                        </span>
                    </div>
                    <div id="std_type_hint" style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                        Target efisiensi konsumsi BBM unit operasional.
                    </div>
                </div>

                <!-- 3. Tanggal Mulai Berlaku -->
                <div class="digi-form-group">
                    <label class="digi-form-label">Berlaku Mulai <span class="digi-required">*</span></label>
                    <input type="date" name="effective_start" id="std_start" class="digi-input" value="{{ now()->toDateString() }}" required>
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                        Standar sebelumnya akan ditutup otomatis per tanggal ini.
                    </div>
                </div>
            </div>

            <input type="hidden" name="tolerance_percentage" id="std_tolerance" value="5.0">

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 14px;">
                <div style="flex: 1; min-width: 260px;">
                    <input type="text" name="notes" id="std_notes" class="digi-input" placeholder="Catatan opsional (misal: penyesuaian rasio operasional tambang 2026)">
                </div>
                <div>
                    <button type="submit" id="stdSubmitBtn" class="btn-digi btn-digi-blue">
                        Simpan Standar BBM
                    </button>
                </div>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari unit code, jenis unit, standar..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($fuelStandards, 'total') ? $fuelStandards->total() : count($fuelStandards) }} standar</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $fuelStandards])

        <!-- Tabel Sederhana Standar Konsumsi BBM -->
        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Unit Code</th>
                        <th>Jenis Unit</th>
                        <th style="text-align: right;">Standar Konsumsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fuelStandards as $idx => $std)
                        <tr>
                            <td>{{ $fuelStandards->firstItem() ? ($fuelStandards->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight: 700; color: #0284c7;">
                                {{ $std->vehicle?->unit_code ?? ('Unit #' . $std->vehicle_id) }}
                            </td>
                            <td>
                                {{ $std->vehicle?->unit_type ?? ($std->vehicle?->model ?? 'Light Vehicle') }}
                            </td>
                            <td style="text-align: right;">
                                <span style="font-weight: 700; color: #0f172a; font-size: 13.5px;">{{ (float)$std->standard_value }}</span>
                                <span style="font-size: 12px; font-weight: 600; color: #64748b; margin-left: 2px;">
                                    KM/L
                                </span>
                            </td>
                            <td>
                                <span class="digi-status-dot-pill {{ $std->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $std->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($std) }}" onclick="editStandard(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $std->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="standard" data-id="{{ $std->id }}" data-tab="standards" data-name="{{ ($std->vehicle?->unit_code ?? '') . ' (' . (float)$std->standard_value . ')' }}" data-active="{{ $std->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $std->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.standards.destroy', $std->id) }}" data-entity="Standar Konsumsi BBM" data-name="{{ ($std->vehicle?->unit_code ?? '') . ' (' . (float)$std->standard_value . ')' }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $fuelStandards])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 7: FUEL PRICES                                           -->
    <!-- ============================================================ -->
    @if($tab === 'prices')
        <form id="priceForm" action="{{ route('admin.master.price') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="price_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="priceFormTitle" style="margin: 0;">Tambah Riwayat Harga BBM per Liter</h3>
                    <span class="digi-edit-pill-badge" id="priceEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                </div>
                <button type="button" id="priceCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetPriceForm()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                </button>
            </div>
            <div class="digi-responsive-grid-4" style="margin-bottom: 0; align-items: flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Vendor BBM <span class="digi-required">*</span></span>
                        <a href="{{ route('admin.master', ['tab' => 'vendors']) }}" class="digi-quick-link" title="Buka master Perusahaan BBM">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><rect x="3" y="2" width="12" height="20" rx="2"></rect><line x1="3" y1="10" x2="15" y2="10"></line><path d="M15 7h2a2 2 0 0 1 2 2v6a2 2 0 0 0 2 2v0a2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L20.5 6.5"></path></svg>Kelola Perusahaan BBM &rarr;
                        </a>
                    </label>
                    <select name="vendor_id" id="price_vendor_id" class="digi-select" required>
                        <option value="">- Pilih Vendor BBM -</option>
                        @foreach(($allVendors ?? $vendors) as $ven)
                            <option value="{{ $ven->id }}">{{ ($ven->code ? $ven->code . ' - ' : '') . $ven->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Jenis BBM & Harga per Liter (IDR) <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" name="fuel_type" id="price_fuel_type" class="digi-input" value="Solar Industri B35" style="flex: 1.6; min-width: 160px;" placeholder="Jenis BBM" required>
                        <input type="number" step="1" name="price_per_liter" id="price_per_liter" class="digi-input" value="27000" style="flex: 1; min-width: 110px;" placeholder="Rp/Liter" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Tanggal Mulai Berlaku <span class="digi-required">*</span></label>
                    <input type="date" name="effective_start" id="price_start" class="digi-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <button type="submit" id="priceSubmitBtn" class="btn-digi btn-digi-blue">Simpan Harga BBM</button>
                </div>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari vendor BBM, jenis BBM, harga..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($fuelPrices, 'total') ? $fuelPrices->total() : count($fuelPrices) }} riwayat harga</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $fuelPrices])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Vendor BBM</th>
                        <th>Jenis BBM</th>
                        <th>Harga per Liter</th>
                        <th>Mulai Berlaku</th>
                        <th>Akhir Berlaku</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fuelPrices as $idx => $fp)
                        <tr>
                            <td>{{ $fuelPrices->firstItem() ? ($fuelPrices->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td>
                                <span class="digi-badge digi-badge-blue" style="font-weight:600;">
                                    {{ $fp->vendor?->name ?? 'Semua Vendor' }}
                                </span>
                            </td>
                            <td style="font-weight:700; color: #0284c7;">{{ $fp->fuel_type }}</td>
                            <td><strong>Rp {{ number_format($fp->price_per_liter, 0, ',', '.') }}</strong> / Liter</td>
                            <td>{{ $fp->effective_start?->format('d/m/Y') }}</td>
                            <td>{{ $fp->effective_end ? $fp->effective_end->format('d/m/Y') : 'Sekarang' }}</td>
                            <td>
                                <span class="digi-status-dot-pill {{ $fp->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $fp->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($fp) }}" onclick="editPrice(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $fp->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="price" data-id="{{ $fp->id }}" data-tab="prices" data-name="{{ $fp->fuel_type . ' (Rp ' . number_format($fp->price_per_liter, 0, ',', '.') . ')' }}" data-active="{{ $fp->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $fp->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                        data-action="{{ route('admin.master.prices.destroy', $fp->id) }}" data-entity="Harga BBM per Liter" data-name="{{ $fp->fuel_type . ' (Rp ' . number_format($fp->price_per_liter, 0, ',', '.') . '/L)' }}"
                                        onclick="openDeleteModalFromBtn(this)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $fuelPrices])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 8: USERS & ROLES                                         -->
    <!-- ============================================================ -->
    @if($tab === 'users')
        <form id="userForm" action="{{ route('admin.master.user') }}" method="POST" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="usr_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="usrFormTitle" style="margin: 0;">Langkah 5: Tambah / Kelola Akun Pengguna, Karyawan & Penugasan Area</h3>
                    <span class="digi-edit-pill-badge" id="usrEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                    <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0 0; width: 100%;">
                        1 formulir terpadu: mendaftarkan akun login, sinkronisasi otomatis data Karyawan (Employee) via NRP, serta penugasan wilayah kerja approver.
                    </p>
                </div>
                <button type="button" id="usrCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetUserForm()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                </button>
            </div>

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 12.5px; color: #166534;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-2px; margin-right:4px;"><line x1="9" y1="18" x2="15" y2="18"></line><line x1="10" y1="22" x2="14" y2="22"></line><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"></path></svg><strong>Integrasi 1 Langkah:</strong> Mengisi kolom NRP akan otomatis menyinkronkan data Employee / Karyawan (DIGISIM Ready). Untuk SPV, Dept Head, Vendor BBM, & Fuel Man, centang pilihan area operasional di bawah agar langsung terdaftar pada formulir pengajuan Driver.
            </div>

            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Nama Lengkap & NRP <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="name" id="usr_name" class="digi-input" placeholder="Nama Lengkap" required>
                        <input type="text" name="nrp" id="usr_nrp" class="digi-input" placeholder="NRP" style="width:115px;">
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Username & Email <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" name="username" id="usr_username" class="digi-input" placeholder="Username" required>
                        <input type="email" name="email" id="usr_email" class="digi-input" placeholder="Email" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">No. WhatsApp / HP (08xxx)</label>
                    <input type="text" name="phone" id="usr_phone" class="digi-input" placeholder="Contoh: 081234567890">
                </div>
            </div>
            <div class="digi-form-grid-3" style="margin-bottom:0; align-items:flex-end;">
                <div class="digi-form-group">
                    <label class="digi-form-label">Role & Jabatan <span class="digi-required">*</span></label>
                    <div style="display:flex; gap:6px;">
                        <select name="role" id="usr_role" class="digi-select" required onchange="onUserRoleChange(this)">
                            <option value="driver">Employee / Driver</option>
                            <option value="spv">SPV / Supervisor</option>
                            <option value="depthead">Dept Head / Manager</option>
                            <option value="vendor">Vendor BBM</option>
                            <option value="fuelman">Fuel Man</option>
                            <option value="admin">Admin</option>
                        </select>
                        <input type="text" name="jabatan" id="usr_jabatan" class="digi-input" placeholder="Jabatan" required>
                    </div>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span>Perusahaan Terkait (Khusus Vendor BBM)</span>
                        <a href="{{ route('admin.master', ['tab' => 'vendors']) }}" class="digi-quick-link" title="Buka master Perusahaan BBM">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><rect x="3" y="2" width="12" height="20" rx="2"></rect><line x1="3" y1="10" x2="15" y2="10"></line><path d="M15 7h2a2 2 0 0 1 2 2v6a2 2 0 0 0 2 2v0a2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L20.5 6.5"></path></svg>Kelola Perusahaan BBM &rarr;
                        </a>
                    </label>
                    <select name="vendor_id" id="usr_vendor_id" class="digi-select">
                        <option value="">- Internal PT United Tractors -</option>
                        @foreach(($allVendors ?? $vendors) as $ven)
                            <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Password Baru (Default: password123)</label>
                    <input type="password" name="password" id="usr_password" class="digi-input" placeholder="Kosongkan jika tidak diubah">
                </div>
            </div>

            <!-- Penugasan Area Operasional Terpadu -->
            <div id="usr_area_assignment_box" style="margin-top: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 4px;">
                    <label class="digi-form-label" style="margin-bottom: 0; font-weight: 700; color: #1e293b;">
                        Penugasan Wilayah Kerja / Area Operasional:
                    </label>
                    <a href="{{ route('admin.master', ['tab' => 'areas']) }}" class="digi-quick-link" title="Buka master Area Operasional">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-1px; margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>Kelola Area &rarr;
                    </a>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                    Centang seluruh area yang menjadi wilayah penugasan atau wewenang approver ini. Pilihan di formulir Fuel Request Driver akan otomatis menampilkan approver ini pada area yang dipilih.
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    @foreach($allAreas as $a)
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; background: #fff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; cursor: pointer;">
                            <input type="checkbox" name="assigned_area_ids[]" value="{{ $a->id }}" class="usr-area-cb" id="usr_area_{{ $a->id }}">
                            <span style="font-weight: 600; color: #334155;">{{ $a->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                <button type="submit" id="usrSubmitBtn" class="btn-digi btn-digi-blue">Simpan Akun Pengguna</button>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari pengguna, username, whatsapp, NRP, role, area..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($users, 'total') ? $users->total() : count($users) }} pengguna</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $users])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th>No. WhatsApp</th>
                        <th>NRP</th>
                        <th>Role Sistem</th>
                        <th>Jabatan</th>
                        <th>Perusahaan Terkait</th>
                        <th>Penugasan Area</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $idx => $u)
                        <tr>
                            <td>{{ $users->firstItem() ? ($users->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="font-weight:700; color: #0284c7;">{{ $u->name }}</td>
                            <td style="font-weight:600;">{{ $u->username }}</td>
                            <td>
                                @if($u->phone)
                                    <span style="font-family: monospace; font-weight: 600; color: #0369a1;">{{ $u->phone }}</span>
                                @else
                                    <span style="color: #94a3b8; font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td>{{ $u->nrp ?? '-' }}</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($u->role) }}</span></td>
                            <td>{{ $u->jabatan }}</td>
                            <td>{{ $u->vendor?->name ?? 'Internal UT' }}</td>
                            <td>
                                @forelse($u->areaApprovers as $aa)
                                    <span class="digi-badge digi-badge-green" style="font-size: 11px; margin-bottom: 2px;">{{ $aa->area?->name ?? 'Area #' . $aa->area_id }}</span>
                                @empty
                                    <span style="color: #94a3b8; font-size: 12px;">-</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="digi-status-dot-pill {{ $u->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($u) }}" onclick="editUser(this)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn-digi {{ $u->is_active ? 'btn-digi-red' : 'btn-digi-green' }}" style="padding: 5px 12px; font-size: 12px;"
                                        data-type="user" data-id="{{ $u->id }}" data-tab="users" data-name="{{ $u->name . ' (' . $u->username . ')' }}" data-active="{{ $u->is_active ? '1' : '0' }}"
                                        onclick="openToggleModalFromBtn(this)">
                                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    @if((int)$u->id !== (int)$user->id)
                                        <button type="button" class="btn-digi btn-digi-gray" style="padding: 5px 12px; font-size: 12px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                            data-action="{{ route('admin.master.users.destroy', $u->id) }}" data-entity="Akun Pengguna" data-name="{{ $u->name . ' (' . $u->username . ')' }}"
                                            onclick="openDeleteModalFromBtn(this)">
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $users])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 8B: SYSTEM BACKGROUNDS (CRUD BACKGROUND UT)              -->
    <!-- ============================================================ -->
    @if($tab === 'backgrounds')
        <form id="bgForm" action="{{ route('admin.master.background') }}" method="POST" enctype="multipart/form-data" class="digi-form-box">
            @csrf
            <input type="hidden" name="id" id="bg_id" value="">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <h3 class="digi-section-title" id="bgFormTitle" style="margin: 0;">Upload / Kelola Gambar Background Sistem Sesuai Ketentuan UT</h3>
                    <span class="digi-edit-pill-badge" id="bgEditBadge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Mode Edit Aktif</span>
                </div>
                <button type="button" id="bgCancelBtn" class="btn-digi btn-digi-gray" style="display: none; padding: 5px 14px; font-size: 12px;" onclick="resetBgForm()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Batal Edit
                </button>
            </div>
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: #0369a1;">
                <strong>Ketentuan Gambar Background PT United Tractors Tbk:</strong> Gunakan foto area operasional tambang / alat berat resolusi tinggi (rasio landscape 16:9, format <strong>JPG, PNG, atau WEBP</strong>, ukuran maksimal <strong>5 MB</strong>) dengan bagian atas cerah agar navigasi utama tetap terbaca jelas.
            </div>
            <div class="digi-form-grid-3">
                <div class="digi-form-group">
                    <label class="digi-form-label">Judul / Nama Background <span class="digi-required">*</span></label>
                    <input type="text" name="title" id="bg_title" class="digi-input" placeholder="Contoh: Dokumentasi Alat Berat Site Satui 2026" required>
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">File Gambar Background <span class="digi-required" id="bg_file_req">*</span></label>
                    <input type="file" name="image" id="bg_image" accept="image/jpeg,image/png,image/webp" class="digi-input" style="padding: 6px;">
                </div>
                <div class="digi-form-group">
                    <label class="digi-form-label">Keterangan / Ketentuan UT</label>
                    <input type="text" name="notes" id="bg_notes" class="digi-input" placeholder="Catatan tema operasional / periode penggunaan">
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="set_active" id="bg_set_active" value="1" checked style="width: 16px; height: 16px;">
                    <span>Langsung terapkan sebagai Background Aktif di seluruh halaman DigiFuel</span>
                </label>
                <button type="submit" id="bgSubmitBtn" class="btn-digi btn-digi-blue">
                    Simpan & Terapkan Background
                </button>
            </div>
        </form>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari judul background, ketentuan, pengunggah..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($backgrounds, 'total') ? $backgrounds->total() : count($backgrounds) }} background</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $backgrounds])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Pratinjau Gambar</th>
                        <th>Judul Background</th>
                        <th>Keterangan / Ketentuan UT</th>
                        <th>Waktu Diunggah</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backgrounds as $idx => $bg)
                        <tr>
                            <td>{{ method_exists($backgrounds, 'firstItem') && $backgrounds->firstItem() ? ($backgrounds->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td>
                                <a href="{{ asset($bg->image_path) }}" target="_blank" title="Klik untuk melihat ukuran penuh">
                                    <img src="{{ asset($bg->image_path) }}" alt="{{ $bg->title }}" class="digi-bg-thumb {{ $bg->is_active ? 'is-active' : '' }}">
                                </a>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0284c7;">{{ $bg->title }}</div>
                                <div style="font-size: 11px; color: #64748b;"><code>{{ $bg->image_path }}</code></div>
                            </td>
                            <td>{{ $bg->notes ?? '-' }}</td>
                            <td>
                                <div>{{ $bg->created_at?->format('d/m/Y H:i') }}</div>
                                <div style="font-size: 11px; color: #64748b;">Oleh: {{ $bg->uploader?->name ?? 'Admin Sistem' }}</div>
                            </td>
                            <td>
                                <span class="digi-status-dot-pill {{ $bg->is_active ? 'is-active' : 'is-inactive' }}">
                                    <span class="dot"></span>{{ $bg->is_active ? 'Sedang Digunakan (Aktif)' : 'Tersimpan (Nonaktif)' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn-digi btn-digi-yellow" style="padding: 5px 12px; font-size: 12px;"
                                        data-item="{{ json_encode($bg) }}" onclick="editBackground(this)">
                                        Edit
                                    </button>
                                    @if(!$bg->is_active)
                                        <button type="button" class="btn-digi btn-digi-green" style="padding: 5px 12px; font-size: 12px;"
                                            data-type="background" data-id="{{ $bg->id }}" data-tab="backgrounds" data-name="{{ $bg->title }}" data-active="0"
                                            onclick="openToggleModalFromBtn(this)">
                                            Gunakan Background Ini
                                        </button>
                                    @endif
                                    @if($bg->image_path !== 'images/mining_bg.jpg')
                                        <form action="{{ route('admin.master.background.delete', $bg->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus gambar background ini?')">
                                            @csrf
                                            <button type="submit" class="btn-digi btn-digi-red" style="padding: 5px 12px; font-size: 12px;">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 28px; color: #94a3b8;">
                                Belum ada data gambar background yang tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $backgrounds])
    @endif

    <!-- ============================================================ -->
    <!-- TAB 9: AUDIT TRAIL                                           -->
    <!-- ============================================================ -->
    @if($tab === 'audit')
        <h3 class="digi-section-title">Log Aktivitas & Audit Trail Sistem</h3>

        <div class="digi-table-filter-bar">
            <input type="text" class="digi-table-search-input" placeholder="Cari log aktivitas, pengguna, aksi, nomor pengajuan, status..." onkeyup="filterMasterTable(this)">
            <div style="font-size: 12.5px; color: #64748b;">
                Total: <strong>{{ method_exists($auditLogs, 'total') ? $auditLogs->total() : count($auditLogs) }} log tercatat</strong>
            </div>
        </div>

        @include('partials.pagination_top', ['paginator' => $auditLogs])

        <div class="digi-table-responsive" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="digi-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>No. Pengajuan</th>
                        <th>Transisi Status</th>
                        <th>Keterangan Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $idx => $log)
                        <tr>
                            <td>{{ $auditLogs->firstItem() ? ($auditLogs->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td style="white-space:nowrap;">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td style="font-weight:600;">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td><span class="digi-badge digi-badge-blue">{{ strtoupper($log->action) }}</span></td>
                            <td>
                                @if($log->fuelRequest)
                                    <a href="{{ route('approval.show', $log->fuelRequest->id) }}" style="color:#0284c7; font-weight:700;">{{ $log->fuelRequest->request_no }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $log->from_status ?? '-' }} → {{ $log->to_status ?? '-' }}</td>
                            <td>{{ $log->note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Belum ada catatan audit trail.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination_bottom', ['paginator' => $auditLogs])
    @endif
</div>

<!-- MODAL KONFIRMASI AKTIFKAN / NONAKTIFKAN STATUS MASTER DATA -->
<div id="toggleStatusModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 460px;">
        <div class="digi-modal-header">
            <h3 class="digi-modal-title" id="toggleModalTitle">Konfirmasi Perubahan Status</h3>
            <button type="button" class="digi-modal-close" onclick="closeToggleModal()">&times;</button>
        </div>
        <form action="{{ route('admin.master.toggle') }}" method="POST" style="margin: 0;">
            @csrf
            <input type="hidden" name="entity" id="toggle_entity" value="">
            <input type="hidden" name="id" id="toggle_id" value="">
            <input type="hidden" name="tab" id="toggle_tab" value="">
            <div class="digi-modal-body">
                <p id="toggleModalMessage" style="margin: 0; font-size: 14px; color: #334155; line-height: 1.5;"></p>
            </div>
            <div class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeToggleModal()">Batal</button>
                <button type="submit" id="toggleConfirmBtn" class="btn-digi btn-digi-red">Ya, Ubah Status</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VERIFIKASI 2 LANGKAH PENGHAPUSAN MASTER DATA -->
<div id="deleteMasterModal" class="digi-modal-backdrop" style="display: none;">
    <div class="digi-modal" style="max-width: 510px;">
        <div class="digi-modal-header" style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span id="deleteStepBadge" class="digi-badge digi-badge-red" style="font-size: 11px; padding: 3px 9px;">LANGKAH 1 / 2</span>
                <h3 class="digi-modal-title" id="deleteModalTitle" style="color: #991b1b;">Konfirmasi Hapus Master Data</h3>
            </div>
            <button type="button" class="digi-modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>

        <form id="deleteMasterForm" action="" method="POST" style="margin: 0;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="verification_step" id="delete_verification_step" value="1">

            <!-- STEP 1: PERINGATAN AWAL -->
            <div id="deleteStep1Body" class="digi-modal-body">
                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 13px; color: #9a3412; line-height: 1.5;">
                    <strong>Peringatan Penghapusan Data:</strong><br>
                    Anda akan menghapus <strong id="deleteEntityCategory">Master Data</strong> berikut dari sistem DigiFuel:
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
                    <div style="font-size: 11.5px; color: #64748b; text-transform: uppercase; font-weight: 700;">Target Data yang Akan Dihapus</div>
                    <div id="deleteTargetLabel" style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;">-</div>
                </div>
                <p style="margin: 0; font-size: 13px; color: #475569; line-height: 1.5;">
                    Jika Anda hanya ingin menghentikan penggunaan data ini untuk pengajuan baru tanpa menghapusnya, disarankan menggunakan tombol <strong>Nonaktifkan</strong>. Apakah Anda tetap ingin melanjutkan ke tahap verifikasi penghapusan?
                </p>
            </div>

            <!-- STEP 2: VERIFIKASI KATA KUNCI & CHECKBOX -->
            <div id="deleteStep2Body" class="digi-modal-body" style="display: none;">
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 13px; color: #991b1b; line-height: 1.5;">
                    <strong>Verifikasi Keamanan Tahap Akhir (Langkah 2 / 2):</strong><br>
                    Untuk mencegah ketidaksengajaan penghapusan pada <strong id="deleteTargetLabelStep2">-</strong>, silakan centang pernyataan di bawah dan ketik kata kunci <strong>HAPUS</strong>.
                </div>

                <label style="display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: #1e293b; cursor: pointer; margin-bottom: 14px; user-select: none;">
                    <input type="checkbox" id="deleteConfirmCheckbox" onchange="validateDeleteStep2()" style="margin-top: 3px; width: 16px; height: 16px;">
                    <span>Saya memahami konsekuensi penghapusan data ini dan bertanggung jawab atas perubahan pada Master Data.</span>
                </label>

                <div class="digi-form-group" style="margin-bottom: 4px;">
                    <label class="digi-form-label" style="color: #991b1b;">
                        Ketik kata kunci <code style="background: #fee2e2; padding: 2px 6px; border-radius: 4px; font-weight: 800; color: #b91c1c;">HAPUS</code> untuk membuka kunci tombol:
                    </label>
                    <input type="text" name="confirm_keyword" id="deleteConfirmKeyword" class="digi-input"
                        placeholder="Ketik HAPUS dengan huruf kapital" autocomplete="off"
                        oninput="validateDeleteStep2()" style="border-color: #fca5a5; font-weight: 700; letter-spacing: 1px;">
                </div>
            </div>

            <!-- FOOTER STEP 1 -->
            <div id="deleteStep1Footer" class="digi-modal-footer">
                <button type="button" class="btn-digi btn-digi-gray" onclick="closeDeleteModal()">Batal</button>
                <button type="button" class="btn-digi btn-digi-yellow" onclick="proceedToDeleteStep2()" style="font-weight: 700;">
                    Lanjut ke Verifikasi Langkah 2 &rarr;
                </button>
            </div>

            <!-- FOOTER STEP 2 -->
            <div id="deleteStep2Footer" class="digi-modal-footer" style="display: none; justify-content: space-between;">
                <button type="button" class="btn-digi btn-digi-gray" onclick="backToDeleteStep1()">&larr; Kembali</button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-digi btn-digi-gray" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" id="deleteFinalSubmitBtn" class="btn-digi btn-digi-red" disabled style="opacity: 0.5; cursor: not-allowed;">
                        Hapus Data Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Master Data Burger Bar Menu Handler
function toggleMasterBurgerMenu(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const dropdown = document.getElementById('masterBurgerDropdown');
    const btn = document.getElementById('masterBurgerBtn');
    const backdrop = document.getElementById('masterBurgerBackdrop');
    if (!dropdown || !btn) return;

    const isOpen = dropdown.style.display !== 'none';
    if (isOpen) {
        closeMasterBurgerMenu();
    } else {
        dropdown.style.display = 'flex';
        btn.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        if (backdrop) {
            backdrop.classList.add('is-open');
        }
        if (window.innerWidth <= 640) {
            document.body.style.overflow = 'hidden';
        }
    }
}

function closeMasterBurgerMenu(event) {
    if (event) {
        event.stopPropagation();
    }
    const dropdown = document.getElementById('masterBurgerDropdown');
    const btn = document.getElementById('masterBurgerBtn');
    const backdrop = document.getElementById('masterBurgerBackdrop');
    if (dropdown) dropdown.style.display = 'none';
    if (btn) {
        btn.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
    }
    if (backdrop) {
        backdrop.classList.remove('is-open');
    }
    document.body.style.overflow = '';
}

// Global click outside & Escape key listeners for Burger Menu
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('masterBurgerWrap');
    if (wrap && !wrap.contains(e.target)) {
        closeMasterBurgerMenu();
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMasterBurgerMenu();
    }
});

// Horizontal scroll function for Master Tabs navigation
function scrollMasterTabs(offset) {
    const container = document.getElementById('masterTabsScroll');
    if (!container) return;
    container.scrollBy({ left: offset, behavior: 'smooth' });
}

// Update left/right scroll arrows state
function updateMasterTabsArrows() {
    const container = document.getElementById('masterTabsScroll');
    const leftBtn = document.getElementById('masterTabsScrollLeft');
    const rightBtn = document.getElementById('masterTabsScrollRight');
    if (!container || !leftBtn || !rightBtn) return;

    const maxScroll = container.scrollWidth - container.clientWidth;
    if (maxScroll <= 6) {
        leftBtn.style.display = 'none';
        rightBtn.style.display = 'none';
    } else {
        leftBtn.style.display = 'inline-flex';
        rightBtn.style.display = 'inline-flex';
        leftBtn.disabled = container.scrollLeft <= 5;
        rightBtn.disabled = container.scrollLeft >= maxScroll - 5;
    }
}

// Instant Client-side Table Filter Search
function filterMasterTable(input) {
    if (!input) return;
    const filter = (input.value || '').toLowerCase().trim();
    let container = input.closest('.digi-table-filter-bar');
    let table = null;
    if (container) {
        let el = container.nextElementSibling;
        while (el) {
            table = el.querySelector ? el.querySelector('.digi-table tbody') : null;
            if (table) break;
            if (el.classList && el.classList.contains('digi-table')) {
                table = el.querySelector('tbody') || el;
                break;
            }
            el = el.nextElementSibling;
        }
    }
    if (!table) {
        const root = input.closest('.digi-card') || document;
        table = root.querySelector('.digi-table tbody');
    }
    if (!table) return;
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Helper: Resolve data item from HTML button (data-item attribute) or direct JS object
function resolveItemData(arg) {
    if (arg && arg.getAttribute && arg.getAttribute('data-item')) {
        try {
            return JSON.parse(arg.getAttribute('data-item'));
        } catch (e) {
            console.error('Failed to parse data-item', e);
        }
    }
    return arg;
}

// Generic Button Trigger for Status Toggle Modal
function openToggleModalFromBtn(btn) {
    if (!btn) return;
    const entity = btn.getAttribute('data-type') || btn.getAttribute('data-entity');
    const id = btn.getAttribute('data-id');
    const tab = btn.getAttribute('data-tab');
    const label = btn.getAttribute('data-name') || btn.getAttribute('data-label');
    const activeRaw = btn.getAttribute('data-active');
    const isCurrentlyActive = (activeRaw === '1' || activeRaw === 'true');
    openToggleModal(entity, id, tab, label, isCurrentlyActive);
}

// Generic Button Trigger for Delete Master Data Modal
function openDeleteModalFromBtn(btn) {
    if (!btn) return;
    const actionUrl = btn.getAttribute('data-action');
    const categoryLabel = btn.getAttribute('data-entity') || btn.getAttribute('data-category');
    const targetLabel = btn.getAttribute('data-name') || btn.getAttribute('data-label');
    openDeleteModal(actionUrl, categoryLabel, targetLabel);
}

// 1. Unit Form Auto-Fill & Reset
function editUnit(v) {
    v = resolveItemData(v);
    if (!v) return;
    document.getElementById('unit_id').value = v.id;
    document.getElementById('unit_code').value = v.unit_code || '';
    document.getElementById('unit_police_no').value = v.police_no || '';
    document.getElementById('unit_model').value = v.model || v.unit_type || '';
    if (document.getElementById('unit_vendor_id')) {
        document.getElementById('unit_vendor_id').value = v.unit_vendor_id || '';
    }
    if (document.getElementById('unit_default_area_id')) {
        document.getElementById('unit_default_area_id').value = v.default_area_id || '';
    }
    if (document.getElementById('unit_meter_type')) {
        document.getElementById('unit_meter_type').value = v.meter_type || 'km';
    }
    document.getElementById('unit_tank_capacity').value = v.tank_capacity || 80;
    if (document.getElementById('unit_standard_consumption')) {
        document.getElementById('unit_standard_consumption').value = v.active_fuel_standard ? parseFloat(v.active_fuel_standard.standard_value) : '';
    }
    document.getElementById('unitFormTitle').textContent = 'Edit Data Unit: ' + v.unit_code;
    document.getElementById('unitSubmitBtn').textContent = 'Perbarui Unit';
    document.getElementById('unitCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('unitForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('unitEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetUnitForm() {
    const form = document.getElementById('unitForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('unit_id').value = '';
    if (document.getElementById('unit_vendor_id')) {
        document.getElementById('unit_vendor_id').value = '';
    }
    if (document.getElementById('unit_default_area_id')) {
        document.getElementById('unit_default_area_id').value = '';
    }
    if (document.getElementById('unit_standard_consumption')) {
        document.getElementById('unit_standard_consumption').value = '';
    }
    if (document.getElementById('unit_meter_type')) {
        document.getElementById('unit_meter_type').value = 'km';
    }
    document.getElementById('unitFormTitle').textContent = 'Langkah 4: Tambah Data Unit Kendaraan & Standar Konsumsi BBM';
    document.getElementById('unitSubmitBtn').textContent = 'Simpan Data Unit';
    document.getElementById('unitCancelBtn').style.display = 'none';
    const badge = document.getElementById('unitEditBadge');
    if (badge) badge.style.display = 'none';
}

// 1b. Unit Vendor Form Auto-Fill & Reset
function editUnitVendor(uv) {
    uv = resolveItemData(uv);
    if (!uv) return;
    document.getElementById('unit_vendor_id_input').value = uv.id;
    document.getElementById('uv_code').value = uv.code || '';
    document.getElementById('uv_name').value = uv.name || '';
    document.getElementById('uv_contact').value = uv.contact_person || '';
    document.getElementById('uv_phone').value = uv.phone || '';
    document.getElementById('uv_address').value = uv.address || '';
    document.getElementById('unitVendorFormTitle').textContent = 'Edit Perusahaan Rental: ' + uv.name;
    document.getElementById('unitVendorSubmitBtn').textContent = 'Perbarui Perusahaan Rental';
    document.getElementById('unitVendorCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('unitVendorForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('unitVendorEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetUnitVendorForm() {
    const form = document.getElementById('unitVendorForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('unit_vendor_id_input').value = '';
    document.getElementById('unitVendorFormTitle').textContent = 'Langkah 3: Tambah Data Perusahaan Rental Unit (Penyedia Unit)';
    document.getElementById('unitVendorSubmitBtn').textContent = 'Simpan Perusahaan Rental';
    document.getElementById('unitVendorCancelBtn').style.display = 'none';
    const badge = document.getElementById('unitVendorEditBadge');
    if (badge) badge.style.display = 'none';
}

// 2. Employee Form Auto-Fill & Reset
function editEmployee(emp) {
    emp = resolveItemData(emp);
    if (!emp) return;
    document.getElementById('emp_id').value = emp.id;
    document.getElementById('emp_nrp').value = emp.nrp || '';
    document.getElementById('emp_name').value = emp.name || '';
    document.getElementById('emp_position').value = emp.position || '';
    document.getElementById('emp_company').value = emp.company || 'PT UNITED TRACTORS Tbk - SITE SATUI';
    document.getElementById('emp_source').value = emp.source || 'local';
    document.getElementById('emp_ext_ref').value = emp.external_reference || '';
    document.getElementById('empFormTitle').textContent = 'Edit Data Employee: ' + emp.name;
    document.getElementById('empSubmitBtn').textContent = 'Perbarui Employee';
    document.getElementById('empCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('employeeForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('empEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetEmployeeForm() {
    const form = document.getElementById('employeeForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('emp_id').value = '';
    document.getElementById('empFormTitle').textContent = 'Tambah Data Employee (Lokal & Integrasi DIGISIM)';
    document.getElementById('empSubmitBtn').textContent = 'Simpan Employee';
    document.getElementById('empCancelBtn').style.display = 'none';
    const badge = document.getElementById('empEditBadge');
    if (badge) badge.style.display = 'none';
}

// 3. Area Form Auto-Fill & Reset
function editArea(a) {
    a = resolveItemData(a);
    if (!a) return;
    document.getElementById('area_id').value = a.id;
    document.getElementById('area_code').value = a.code || '';
    document.getElementById('area_name').value = a.name || '';
    document.getElementById('areaFormTitle').textContent = 'Edit Area Operasional: ' + a.name;
    document.getElementById('areaSubmitBtn').textContent = 'Perbarui Area';
    document.getElementById('areaCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('areaForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('areaEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetAreaForm() {
    const form = document.getElementById('areaForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('area_id').value = '';
    document.getElementById('areaFormTitle').textContent = 'Langkah 1: Tambah Area Operasional';
    document.getElementById('areaSubmitBtn').textContent = 'Simpan Area';
    document.getElementById('areaCancelBtn').style.display = 'none';
    const badge = document.getElementById('areaEditBadge');
    if (badge) badge.style.display = 'none';
}

// 4. Vendor Form Auto-Fill & Reset
function editVendor(ven) {
    ven = resolveItemData(ven);
    if (!ven) return;
    document.getElementById('vendor_id').value = ven.id;
    document.getElementById('vendor_code').value = ven.code || '';
    document.getElementById('vendor_name').value = ven.name || '';
    document.getElementById('vendor_contact').value = ven.contact_person || '';
    document.getElementById('vendor_phone').value = ven.phone || '';
    if (document.getElementById('vendor_fuel_type')) {
        document.getElementById('vendor_fuel_type').value = (ven.active_fuel_price ? ven.active_fuel_price.fuel_type : 'Solar Industri B35');
    }
    if (document.getElementById('vendor_price_per_liter')) {
        document.getElementById('vendor_price_per_liter').value = (ven.active_fuel_price ? parseFloat(ven.active_fuel_price.price_per_liter) : '');
    }
    document.getElementById('vendorFormTitle').textContent = 'Edit Data Perusahaan: ' + ven.name;
    document.getElementById('vendorSubmitBtn').textContent = 'Perbarui Data Perusahaan';
    document.getElementById('vendorCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('vendorForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('vendorEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetVendorForm() {
    const form = document.getElementById('vendorForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('vendor_id').value = '';
    if (document.getElementById('vendor_price_per_liter')) {
        document.getElementById('vendor_price_per_liter').value = '';
    }
    if (document.getElementById('vendor_fuel_type')) {
        document.getElementById('vendor_fuel_type').value = 'Solar Industri B35';
    }
    document.getElementById('vendorFormTitle').textContent = 'Langkah 2: Tambah Data Perusahaan & Harga Solar';
    document.getElementById('vendorSubmitBtn').textContent = 'Simpan Data Perusahaan';
    document.getElementById('vendorCancelBtn').style.display = 'none';
    const badge = document.getElementById('vendorEditBadge');
    if (badge) badge.style.display = 'none';
}

// 5. Area Approver Form Auto-Fill & Reset
function editApprover(aa) {
    aa = resolveItemData(aa);
    if (!aa) return;
    document.getElementById('aa_id').value = aa.id;
    document.getElementById('aa_area_id').value = aa.area_id;
    document.getElementById('aa_user_id').value = aa.user_id;
    document.getElementById('aa_role_type').value = aa.role_type;
    document.getElementById('aaFormTitle').textContent = 'Edit Penugasan Approver Area #' + aa.id;
    document.getElementById('aaSubmitBtn').textContent = 'Perbarui Penugasan';
    document.getElementById('aaCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('approverForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('aaEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetApproverForm() {
    const form = document.getElementById('approverForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('aa_id').value = '';
    document.getElementById('aaFormTitle').textContent = 'Penugasan Approver & Petugas Berdasarkan Area';
    document.getElementById('aaSubmitBtn').textContent = 'Simpan Penugasan';
    document.getElementById('aaCancelBtn').style.display = 'none';
    const badge = document.getElementById('aaEditBadge');
    if (badge) badge.style.display = 'none';
}

// 6. Fuel Standard Form Auto-Fill, Dynamic Units & Reset
function onVehicleSelectChange(selectElem) {
    if (!selectElem) return;
    const selectedOpt = selectElem.options[selectElem.selectedIndex];
    if (!selectedOpt || !selectedOpt.value) {
        const infoElem = document.getElementById('std_vehicle_info');
        if (infoElem) infoElem.style.display = 'none';
        return;
    }

    const unitType = selectedOpt.getAttribute('data-type') || 'Kendaraan Operasional';

    const infoElem = document.getElementById('std_vehicle_info');
    if (infoElem) {
        document.getElementById('std_info_type').textContent = unitType;
        infoElem.style.display = 'block';
    }

    const unitBadge = document.getElementById('std_unit_badge');
    const stdTypeInput = document.getElementById('std_type');
    const hint = document.getElementById('std_type_hint');

    if (stdTypeInput) stdTypeInput.value = 'km_per_liter';
    if (unitBadge) unitBadge.textContent = 'KM/L';
    if (hint) hint.textContent = 'Target efisiensi bahan bakar (Kilometer per Liter).';
}

function editStandard(std, startDate) {
    std = resolveItemData(std);
    if (!std) return;
    if (!startDate && std.effective_start) {
        startDate = typeof std.effective_start === 'string' ? std.effective_start.substring(0, 10) : '';
    }
    document.getElementById('std_id').value = std.id;
    const vehSelect = document.getElementById('std_vehicle_id');
    if (vehSelect) {
        vehSelect.value = std.vehicle_id;
    }
    document.getElementById('std_value').value = parseFloat(std.standard_value);

    const stdTypeInput = document.getElementById('std_type');
    if (stdTypeInput) {
        stdTypeInput.value = std.standard_type || 'km_per_liter';
    }
    const tolInput = document.getElementById('std_tolerance');
    if (tolInput) {
        tolInput.value = parseFloat(std.tolerance_percentage || 5.0);
    }
    const notesInput = document.getElementById('std_notes');
    if (notesInput) {
        notesInput.value = std.notes || '';
    }
    if (startDate) document.getElementById('std_start').value = startDate;

    const vCode = std.vehicle ? std.vehicle.unit_code : ('Unit #' + std.vehicle_id);
    document.getElementById('stdFormTitle').textContent = 'Edit Standar Konsumsi BBM: ' + vCode;
    document.getElementById('stdSubmitBtn').textContent = 'Perbarui Standar BBM';
    document.getElementById('stdCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('standardForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('stdEditBadge');
    if (badge) badge.style.display = 'inline-flex';

    onVehicleSelectChange(vehSelect);
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function resetStandardForm() {
    const form = document.getElementById('standardForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('std_id').value = '';
    const infoElem = document.getElementById('std_vehicle_info');
    if (infoElem) infoElem.style.display = 'none';
    const unitBadge = document.getElementById('std_unit_badge');
    if (unitBadge) unitBadge.textContent = 'KM/L';
    const stdTypeInput = document.getElementById('std_type');
    if (stdTypeInput) stdTypeInput.value = 'km_per_liter';
    const tolBox = document.getElementById('std_tolerance_box');
    if (tolBox) tolBox.style.display = 'none';
    document.getElementById('stdFormTitle').textContent = 'Pengaturan Standar Konsumsi BBM Unit';
    document.getElementById('stdSubmitBtn').textContent = 'Simpan Standar BBM';
    document.getElementById('stdCancelBtn').style.display = 'none';
    const badge = document.getElementById('stdEditBadge');
    if (badge) badge.style.display = 'none';
}

// 7. Fuel Price Form Auto-Fill & Reset
function editPrice(fp, startDate) {
    fp = resolveItemData(fp);
    if (!fp) return;
    if (!startDate && fp.effective_start) {
        startDate = typeof fp.effective_start === 'string' ? fp.effective_start.substring(0, 10) : '';
    }
    document.getElementById('price_id').value = fp.id;
    document.getElementById('price_vendor_id').value = fp.vendor_id || '';
    document.getElementById('price_fuel_type').value = fp.fuel_type || '';
    document.getElementById('price_per_liter').value = parseFloat(fp.price_per_liter);
    if (startDate) document.getElementById('price_start').value = startDate;
    document.getElementById('priceFormTitle').textContent = 'Edit Harga BBM: ' + fp.fuel_type;
    document.getElementById('priceSubmitBtn').textContent = 'Perbarui Harga BBM';
    document.getElementById('priceCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('priceForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('priceEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetPriceForm() {
    const form = document.getElementById('priceForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('price_id').value = '';
    document.getElementById('price_vendor_id').value = '';
    document.getElementById('priceFormTitle').textContent = 'Tambah Riwayat Harga BBM per Liter';
    document.getElementById('priceSubmitBtn').textContent = 'Simpan Harga BBM';
    document.getElementById('priceCancelBtn').style.display = 'none';
    const badge = document.getElementById('priceEditBadge');
    if (badge) badge.style.display = 'none';
}

function onUserRoleChange(selectElem) {
    if (!selectElem) return;
    const role = selectElem.value;
    const venSelect = document.getElementById('usr_vendor_id');
    if (venSelect && venSelect.parentElement) {
        venSelect.parentElement.style.opacity = (role === 'vendor') ? '1' : '0.7';
    }
}

// 8. User Form Auto-Fill & Reset
function editUser(u) {
    u = resolveItemData(u);
    if (!u) return;
    document.getElementById('usr_id').value = u.id;
    document.getElementById('usr_name').value = u.name || '';
    document.getElementById('usr_nrp').value = u.nrp || '';
    document.getElementById('usr_username').value = u.username || '';
    document.getElementById('usr_email').value = u.email || '';
    document.getElementById('usr_phone').value = u.phone || '';
    document.getElementById('usr_role').value = u.role || 'driver';
    document.getElementById('usr_jabatan').value = u.jabatan || '';
    document.getElementById('usr_vendor_id').value = u.vendor_id || '';
    document.getElementById('usr_password').value = '';

    // Reset all area checkboxes
    document.querySelectorAll('.usr-area-cb').forEach(cb => cb.checked = false);

    // Auto-check assigned areas
    if (u.area_approvers && Array.isArray(u.area_approvers)) {
        u.area_approvers.forEach(aa => {
            const cb = document.getElementById('usr_area_' + aa.area_id);
            if (cb) cb.checked = true;
        });
    }

    onUserRoleChange(document.getElementById('usr_role'));
    document.getElementById('usrFormTitle').textContent = 'Edit Akun Pengguna: ' + u.name;
    document.getElementById('usrSubmitBtn').textContent = 'Perbarui Akun Pengguna';
    document.getElementById('usrCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('userForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('usrEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetUserForm() {
    const form = document.getElementById('userForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('usr_id').value = '';
    document.getElementById('usr_phone').value = '';
    document.querySelectorAll('.usr-area-cb').forEach(cb => cb.checked = false);
    onUserRoleChange(document.getElementById('usr_role'));
    document.getElementById('usrFormTitle').textContent = 'Langkah 5: Tambah / Kelola Akun Pengguna, Karyawan & Penugasan Area';
    document.getElementById('usrSubmitBtn').textContent = 'Simpan Akun Pengguna';
    document.getElementById('usrCancelBtn').style.display = 'none';
    const badge = document.getElementById('usrEditBadge');
    if (badge) badge.style.display = 'none';
}

// 8B. Background Form Auto-Fill & Reset
function editBackground(bg) {
    bg = resolveItemData(bg);
    if (!bg) return;
    document.getElementById('bg_id').value = bg.id;
    document.getElementById('bg_title').value = bg.title || '';
    document.getElementById('bg_notes').value = bg.notes || '';
    document.getElementById('bg_set_active').checked = !!bg.is_active;
    document.getElementById('bg_file_req').style.display = 'none';
    document.getElementById('bgFormTitle').textContent = 'Edit Background Sistem: ' + bg.title;
    document.getElementById('bgSubmitBtn').textContent = 'Perbarui Background';
    document.getElementById('bgCancelBtn').style.display = 'inline-flex';
    const form = document.getElementById('bgForm');
    if (form) form.classList.add('is-editing');
    const badge = document.getElementById('bgEditBadge');
    if (badge) badge.style.display = 'inline-flex';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetBgForm() {
    const form = document.getElementById('bgForm');
    if (form) {
        form.reset();
        form.classList.remove('is-editing');
    }
    document.getElementById('bg_id').value = '';
    document.getElementById('bg_file_req').style.display = 'inline';
    document.getElementById('bgFormTitle').textContent = 'Upload / Kelola Gambar Background Sistem Sesuai Ketentuan UT';
    document.getElementById('bgSubmitBtn').textContent = 'Simpan & Terapkan Background';
    document.getElementById('bgCancelBtn').style.display = 'none';
    const badge = document.getElementById('bgEditBadge');
    if (badge) badge.style.display = 'none';
}

function onUserRoleChange(selectElem) {
    if (!selectElem) return;
    const role = selectElem.value;
    const venSelect = document.getElementById('usr_vendor_id');
    if (venSelect && venSelect.parentElement) {
        venSelect.parentElement.style.opacity = (role === 'vendor') ? '1' : '0.7';
    }
}

// 8. User Form Auto-Fill & Reset
function editUser(u) {
    u = resolveItemData(u);
    if (!u) return;
    document.getElementById('usr_id').value = u.id;
    document.getElementById('usr_name').value = u.name || '';
    document.getElementById('usr_nrp').value = u.nrp || '';
    document.getElementById('usr_username').value = u.username || '';
    document.getElementById('usr_email').value = u.email || '';
    document.getElementById('usr_phone').value = u.phone || '';
    document.getElementById('usr_role').value = u.role || 'driver';
    document.getElementById('usr_jabatan').value = u.jabatan || '';
    document.getElementById('usr_vendor_id').value = u.vendor_id || '';
    document.getElementById('usr_password').value = '';

    // Reset all area checkboxes
    document.querySelectorAll('.usr-area-cb').forEach(cb => cb.checked = false);

    // Auto-check assigned areas
    if (u.area_approvers && Array.isArray(u.area_approvers)) {
        u.area_approvers.forEach(aa => {
            const cb = document.getElementById('usr_area_' + aa.area_id);
            if (cb) cb.checked = true;
        });
    }

    onUserRoleChange(document.getElementById('usr_role'));
    document.getElementById('usrFormTitle').textContent = 'Edit Akun Pengguna: ' + u.name;
    document.getElementById('usrSubmitBtn').textContent = 'Perbarui Akun Pengguna';
    document.getElementById('usrCancelBtn').style.display = 'inline-flex';
    document.getElementById('userForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetUserForm() {
    document.getElementById('userForm').reset();
    document.getElementById('usr_id').value = '';
    document.getElementById('usr_phone').value = '';
    document.querySelectorAll('.usr-area-cb').forEach(cb => cb.checked = false);
    onUserRoleChange(document.getElementById('usr_role'));
    document.getElementById('usrFormTitle').textContent = 'Langkah 5: Tambah / Kelola Akun Pengguna, Karyawan & Penugasan Area';
    document.getElementById('usrSubmitBtn').textContent = 'Simpan Akun Pengguna';
    document.getElementById('usrCancelBtn').style.display = 'none';
}

// 8B. Background Form Auto-Fill & Reset
function editBackground(bg) {
    bg = resolveItemData(bg);
    if (!bg) return;
    document.getElementById('bg_id').value = bg.id;
    document.getElementById('bg_title').value = bg.title || '';
    document.getElementById('bg_notes').value = bg.notes || '';
    document.getElementById('bg_set_active').checked = !!bg.is_active;
    document.getElementById('bg_file_req').style.display = 'none';
    document.getElementById('bgFormTitle').textContent = 'Edit Background Sistem: ' + bg.title;
    document.getElementById('bgSubmitBtn').textContent = 'Perbarui Background';
    document.getElementById('bgCancelBtn').style.display = 'inline-flex';
    document.getElementById('bgForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function resetBgForm() {
    document.getElementById('bgForm').reset();
    document.getElementById('bg_id').value = '';
    document.getElementById('bg_file_req').style.display = 'inline';
    document.getElementById('bgFormTitle').textContent = 'Upload / Kelola Gambar Background Sistem Sesuai Ketentuan UT';
    document.getElementById('bgSubmitBtn').textContent = 'Simpan & Terapkan Background';
    document.getElementById('bgCancelBtn').style.display = 'none';
}

// 9. Confirmation Modal for Activate / Deactivate Status
function openToggleModal(entity, id, tab, label, isCurrentlyActive) {
    document.getElementById('toggle_entity').value = entity;
    document.getElementById('toggle_id').value = id;
    document.getElementById('toggle_tab').value = tab;

    const actionWord = isCurrentlyActive ? 'menonaktifkan' : 'mengaktifkan kembali';
    const btn = document.getElementById('toggleConfirmBtn');

    document.getElementById('toggleModalTitle').textContent = isCurrentlyActive
        ? 'Konfirmasi Nonaktifkan Data'
        : 'Konfirmasi Aktifkan Data';

    document.getElementById('toggleModalMessage').innerHTML =
        `Apakah Anda yakin ingin <strong>${actionWord}</strong> data <strong>${label}</strong>?<br><span style="font-size:12.5px; color:#64748b;">Perubahan status ini akan dicatat pada Audit Trail sistem.</span>`;

    btn.textContent = isCurrentlyActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan';
    btn.className = isCurrentlyActive ? 'btn-digi btn-digi-red' : 'btn-digi btn-digi-green';

    document.getElementById('toggleStatusModal').style.display = 'flex';
}

function closeToggleModal() {
    document.getElementById('toggleStatusModal').style.display = 'none';
}

// 10. Two-Step Verification Modal for Deleting Master Data
function openDeleteModal(actionUrl, categoryLabel, targetLabel) {
    const form = document.getElementById('deleteMasterForm');
    form.action = actionUrl;

    document.getElementById('deleteEntityCategory').textContent = categoryLabel;
    document.getElementById('deleteTargetLabel').textContent = targetLabel;
    document.getElementById('deleteTargetLabelStep2').textContent = targetLabel;

    backToDeleteStep1();
    document.getElementById('deleteMasterModal').style.display = 'flex';
}

function proceedToDeleteStep2() {
    document.getElementById('delete_verification_step').value = '2';
    document.getElementById('deleteStepBadge').textContent = 'LANGKAH 2 / 2';
    document.getElementById('deleteModalTitle').textContent = 'Verifikasi Akhir Penghapusan Data';

    document.getElementById('deleteStep1Body').style.display = 'none';
    document.getElementById('deleteStep1Footer').style.display = 'none';
    document.getElementById('deleteStep2Body').style.display = 'block';
    document.getElementById('deleteStep2Footer').style.display = 'flex';

    document.getElementById('deleteConfirmCheckbox').checked = false;
    const kwInput = document.getElementById('deleteConfirmKeyword');
    kwInput.value = '';
    validateDeleteStep2();
    setTimeout(() => kwInput.focus(), 80);
}

function backToDeleteStep1() {
    document.getElementById('delete_verification_step').value = '1';
    document.getElementById('deleteStepBadge').textContent = 'LANGKAH 1 / 2';
    document.getElementById('deleteModalTitle').textContent = 'Konfirmasi Hapus Master Data';

    document.getElementById('deleteStep1Body').style.display = 'block';
    document.getElementById('deleteStep1Footer').style.display = 'flex';
    document.getElementById('deleteStep2Body').style.display = 'none';
    document.getElementById('deleteStep2Footer').style.display = 'none';

    document.getElementById('deleteConfirmCheckbox').checked = false;
    document.getElementById('deleteConfirmKeyword').value = '';
    validateDeleteStep2();
}

function validateDeleteStep2() {
    const isChecked = document.getElementById('deleteConfirmCheckbox').checked;
    const keyword = (document.getElementById('deleteConfirmKeyword').value || '').trim().toUpperCase();
    const submitBtn = document.getElementById('deleteFinalSubmitBtn');

    if (isChecked && keyword === 'HAPUS') {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    } else {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
    }
}

function closeDeleteModal() {
    document.getElementById('deleteMasterModal').style.display = 'none';
}

function updatePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Preserve horizontal scroll and vertical page scroll across tab navigation
document.addEventListener('DOMContentLoaded', function() {
    const tabsContainer = document.getElementById('masterTabsScroll');
    if (tabsContainer) {
        const savedX = sessionStorage.getItem('digi_master_tabs_scroll_x');
        if (savedX !== null) {
            tabsContainer.scrollLeft = parseInt(savedX, 10);
        } else {
            // First visit: gently center active tab in viewport
            const activeTab = tabsContainer.querySelector('.digi-tab-pill.is-active');
            if (activeTab) {
                activeTab.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
            }
        }

        tabsContainer.addEventListener('scroll', function() {
            sessionStorage.setItem('digi_master_tabs_scroll_x', tabsContainer.scrollLeft);
            updateMasterTabsArrows();
        }, { passive: true });

        updateMasterTabsArrows();
        window.addEventListener('resize', updateMasterTabsArrows);
    }

    // Save scroll position when clicking any master tab pill, button or quick shortcut
    document.querySelectorAll('.digi-tab-pill, .digi-master-tabs-nav-wrapper a, .digi-quick-link').forEach(link => {
        link.addEventListener('click', function() {
            if (tabsContainer) {
                sessionStorage.setItem('digi_master_tabs_scroll_x', tabsContainer.scrollLeft);
            }
            sessionStorage.setItem('digi_master_page_scroll_y', window.scrollY);
            sessionStorage.setItem('digi_master_tab_switched', '1');
        });
    });

    // Restore page scroll position after switching tabs so user remains at current scroll
    if (sessionStorage.getItem('digi_master_tab_switched') === '1') {
        sessionStorage.removeItem('digi_master_tab_switched');
        const savedY = sessionStorage.getItem('digi_master_page_scroll_y');
        if (savedY !== null) {
            window.scrollTo({ top: parseInt(savedY, 10), behavior: 'instant' });
        }
    }
});
</script>
@endsection
