# DigiFuel — Digital Fuel Management & Evaluation System
### PT United Tractors Tbk — Site Satui

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Tests](https://img.shields.io/badge/Tests-67%20Passed%20(685%20Assertions)-success?style=for-the-badge&logo=checkmarx&logoColor=white)]()
[![Security](https://img.shields.io/badge/Security-Hardened%20(OWASP%20Ready)-blue?style=for-the-badge&logo=shield&logoColor=white)]()

> 📘 **Butuh Panduan Penggunaan untuk Pengguna Lapangan?**  
> Baca panduan lengkap dan mudah dipahami untuk orang awam di: **[PANDUAN_PENGGUNAAN.md](PANDUAN_PENGGUNAAN.md)** atau akses langsung di aplikasi web melalui menu **`Panduan`** (`/panduan`).

---

**DigiFuel** adalah sistem informasi berbasis web terintegrasi yang dirancang khusus untuk mengelola, menyetujui, mencatat realisasi, serta mengevaluasi konsumsi Bahan Bakar Minyak (BBM) armada operasional dan unit kendaraan di lingkungan **PT United Tractors Tbk Site Satui**.

Sistem ini menggantikan proses pengajuan manual berbasis kertas menjadi alur kerja digital yang transparan, akuntabel, terlindungi dari pemborosan biaya, dan dapat diakses baik melalui komputer (Desktop) maupun perangkat seluler (Smartphone / Tablet).

---

## 🚀 Fitur Utama

### 1. Alur Persetujuan Berjenjang (*Sequential Multi-Stage Workflow*)
Proses pengajuan BBM berjalan secara sekuensial dan terotomatisasi:
- **Pengajuan Baru / Draft**: Driver mengajukan permohonan BBM untuk unit kendaraan aktif. Dapat disimpan sementara sebagai *Draft* atau langsung diajukan.
- **Stage 1 (Supervisor / SPV Approval)**: SPV berwenang memeriksa kondisi kendaraan, menyetujui, menolak, atau **menyesuaikan jumlah liter** bila diperlukan.
- **Stage 2 (Dept Head / Manager Approval)**: Verifikasi tingkat manajerial sebelum diteruskan ke vendor BBM.
- **Stage 3 (Vendor Approval)**: Validasi oleh perwakilan perusahaan vendor terkait kuota dan kesiapan suplai.
- **Stage 4 (Fuel Man Realization)**: Petugas dispenser/fuel truck mencatat realisasi pengisian aktual (Liter, Jam/KM Meter, dan Waktu pengisian).

### 2. Notifikasi Otomatis WhatsApp (Fonnte API)
- Notifikasi terkirim otomatis ke nomor WhatsApp penanggung jawab hanya pada tahap yang memerlukan aksi:
  - Notifikasi ke **SPV** saat permohonan baru diajukan Driver.
  - Notifikasi ke **Dept Head** saat permohonan disetujui SPV.
  - Notifikasi ke **Vendor** saat permohonan disetujui Dept Head.
- Dilengkapi sistem *fail-safe* (timeout 5 detik) sehingga kendala jaringan WhatsApp tidak akan menghambat transaksi database sistem.

### 3. Evaluasi Konsumsi BBM & Analisis Kerugian (*Fuel Evaluation Engine*)
- Perhitungan rasio konsumsi bahan bakar aktual terhadap standar unit:
  $$\text{Rasio Konsumsi} = \frac{\text{Total Jarak Tempuh (KM) / Jam Operasi (HM)}}{\text{Total Liter Aktual}}$$
- Deteksi otomatis pemborosan BBM (*waste liters*) dan estimasi kerugian finansial (*estimated cost loss*):
  - Kategori **Normal**: Rasio efisiensi berada di dalam ambang batas standar.
  - Kategori **Needs Review**: Unit terindikasi boros melebihi deviasi yang diizinkan.
- Versioning riwayat standar konsumsi per unit kendaraan dan riwayat fluktuasi harga BBM per vendor.

### 4. Pelaporan Dokumen & Ekspor Multi-Sheet Excel
- **Cetak Dokumen Resmi PDF**: Format kop surat standar PT United Tractors Tbk lengkap dengan QR/Tanda Tangan digital, watermark status, dan layout cetak A4.
- **Ekspor Excel Multi-Sheet**: Format laporan identik dengan acuan monitoring korporat, mencakup sheet monitoring bulanan, rangkuman performa unit, dan rincian transaksi.

### 5. Tampilan Responsif & Gestur Sentuh Mobile
- Tampilan antarmuka responsif untuk smartphone dan tablet.
- Tabel transaksi dilengkapi scroll horizontal sentuh halus (*touch-friendly smooth scrolling*) dan indikator geser (*swipe hint*) agar data operasional yang lebar tetap terbaca utuh di layar kecil.

### 6. Keamanan Berlapis (*Defensive Security Hardened*)
- **Proteksi Brute-Force**: Pembatasan percobaan login salah (maksimal 5 kali gagal per IP/akun sebelum jeda 60 detik).
- **Role-Based Access Control (RBAC)**: Pembatasan hak akses berbasis peran menggunakan middleware khusus.
- **Isolasi Akun Vendor**: Akun vendor hanya memiliki akses ke pengajuan dan kuota perusahaannya sendiri.
- **HTTP Security Headers**: Header proteksi bawaan (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, dll).
- **Validasi File Ketat**: Unggah foto profil dan tanda tangan divalidasi hingga struktur biner gambar (`getimagesize`) dan disajikan secara aman tanpa risiko *Directory Traversal*.

### 7. Navigasi Cepat Burger Bar & Pengurutan Kolom Master Data
- **Menu Burger Bar Ringkas (☰)**: Tombol pemicu cepat 32px di sisi kiri bar navigasi master data yang membuka katalog seluruh 7 langkah konfigurasi (popover di desktop dan bottom-sheet drawer di mobile).
- **Pencarian Cepat & Click-to-Sort**: Filter instan client-side dan pengurutan dinamis seluruh kolom tabel (A-Z, 1-9, mata uang rupiah, dan tanggal) hanya dengan mengklik kepala kolom (`<th>`).
- **Formulir Terpadu**: Input data master terintegrasi tanpa duplikasi dan dilindungi verifikasi modal 2 langkah.

### 8. Sistem Retensi Audit Trail & Ekspor Arsip Cadangan
- **Ekspor Arsip Excel**: Admin dapat mengunduh seluruh catatan log audit ke format Excel (`.xlsx`) sewaktu-waktu sebagai cadangan offline (*cold storage*).
- **Kebijakan Retensi Otomatis**: Dilengkapi Artisan command `audit:purge --days=180` yang dijadwalkan mingguan untuk menjaga kapasitas database tetap ramping tanpa kehilangan riwayat aktif.
- **Pembersihan Manual dengan Verifikasi Keamanan**: Pilihan retensi di antarmuka web (90, 180, 365 hari) yang dilindungi kata kunci konfirmasi `BERSIHKAN`.

### 9. Pusat Panduan & Edukasi Pengguna Awam (`/panduan`)
- Halaman panduan terpadu yang ramah pengguna awam dengan tab peran (Driver, SPV, Manager, Vendor, Fuelman, Admin) dan tanya jawab populer (FAQ).
- Tombol akses cepat **Panduan** (`?`) disematkan langsung di bilah navigasi atas aplikasi agar dapat dibuka seketika dari halaman manapun.

---

## 👥 Matriks Hak Akses Pengguna (*User Roles*)

| Role | Deskripsi & Wewenang Utama | Halaman Utama |
| :--- | :--- | :--- |
| **Driver** | Mengajukan permohonan BBM, cek riwayat, unduh PDF pengajuan sendiri | Form Fuel, History |
| **SPV** | Memverifikasi pengajuan Driver, mengubah liter bila perlu, approval tahap 1 | Approval Stage 1 |
| **Dept Head** | Menyetujui permohonan yang telah disetujui SPV (approval tahap 2) | Approval Stage 2 |
| **Vendor** | Menyetujui ketersediaan stok BBM dari sisi vendor (approval tahap 3) | Approval Stage 3 |
| **Fuel Man** | Mencatat realisasi pengisian fisik (liter aktual, HM/KM meter, waktu) | Realisasi Pengisian |
| **Admin** | Akses penuh: Master data (karyawan, unit, area, approver, standar, harga), background, evaluasi, audit log | Master Data, Evaluasi |

---

## 🔑 Akun Demo / Pengujian Default

Semua akun default menggunakan password: **`password123`**

| Role | Username | Nama | No. NRP |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | Administrator UT Satui | `10001` |
| **Driver (Pemohon 1)** | `adji` | Adji Tri Irianto | `80124024` |
| **Driver (Pemohon 2)** | `rachmat` | Rachmat Hidayat | `80125512` |
| **Supervisor (SPV)** | `aklis` | Aklis | `98798` |
| **Dept Head (Manager)** | `Pdh-kusan` | Garius | `97110` |
| **Vendor Approver** | `vendor_sudan` | CV. Sudan Mandiri | `VND-001` |
| **Fuel Man (Vendor)** | `adya` | ADYA | `99201` |

*(Pengguna juga dapat login menggunakan Username, Alamat Email resmi, atau Nomor NRP).*

---

## 💻 Kebutuhan Sistem (*Prerequisites*)

- **PHP** : Versi `>= 8.2`
- **Composer** : Versi `>= 2.x`
- **Database** : MySQL `>= 8.0` atau MariaDB `>= 10.4`
- **Web Server** : Apache / Nginx (atau paket Laragon / XAMPP)
- **Ekstensi PHP Wajib** : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `gd`, `zip`

---

## 🛠️ Panduan Instalasi & Menjalankan Proyek

### 1. Clone Repositori
```bash
git clone https://github.com/username/digifuel.git
cd digifuel
```

### 2. Pasang Dependensi PHP
```bash
composer install
```

### 3. Konfigurasi Environment (`.env`)
Salin file konfigurasi contoh:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan pengaturan database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digifuel
DB_USERNAME=root
DB_PASSWORD=
```

Buat application key:
```bash
php artisan key:generate
```

### 4. Konfigurasi WhatsApp Gateway (Opsional)
Untuk mengaktifkan notifikasi WhatsApp nyata melalui Fonnte, tambahkan token Anda pada `.env`:
```env
FONNTE_TOKEN=token_fonnte_anda_disini
```

### 5. Jalankan Migrasi & Seeder Database
Jalankan migrasi dan isi database dengan data awal lengkap (karyawan, unit, approver, standar BBM, dan riwayat demo):
```bash
php artisan migrate:fresh --seed
```

### 6. Menjalankan Server Aplikasi
Jalankan server pengembangan bawaan Laravel:
```bash
php artisan serve
```
Akses aplikasi melalui browser di: **`http://127.0.0.1:8000`**

> **Tips Akses via Smartphone (Jaringan Wi-Fi yang Sama):**
> Jalankan server dengan bind host IP laptop Anda:
> ```bash
> php artisan serve --host=0.0.0.0 --port=8000
> ```
> Buka browser di ponsel dengan alamat `http://<IP-LAPTOP-ANDA>:8000` (contoh: `http://10.2.75.151:8000`).

---

## 🧪 Pengujian Otomatis (*Automated Testing Suite*)

DigiFuel dilengkapi dengan suite pengujian otomatis menyeluruh (Feature & Unit Tests) yang mencakup seluruh alur workflow, batasan hak akses, keamanan IDOR, ekspor PDF/Excel, rate limiting, dan integrasi WhatsApp:

```bash
php artisan test
```

**Hasil Pengujian:**
```text
Tests:    67 passed (685 assertions)
Duration: ~20 detik
Status:   100% Passed
```

---

## 📁 Struktur Direktori Penting

```text
digifuel/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controller modul (FuelRequest, Approval, Evaluation, Master, dll)
│   │   └── Middleware/        # RoleMiddleware & SecurityHeadersMiddleware
│   ├── Models/                # Eloquent Models (FuelRequest, Vehicle, User, Evaluation, dll)
│   ├── Policies/              # FuelRequestPolicy (Validasi hak akses & anti-IDOR)
│   └── Services/              # FuelRequestService, ExcelExportService, FonnteWhatsappService
├── bootstrap/                 # Inisialisasi framework & registrasi middleware
├── config/                    # File konfigurasi aplikasi
├── database/
│   ├── migrations/            # Skema tabel database
│   └── seeders/               # DigiFuelSeeder (Data master, approver, unit, dan riwayat)
├── public/
│   ├── css/digifuel.css       # Stylesheet responsif utama & tema DigiFuel
│   ├── images/                # Logo resmi UT, ikon, dan background pertambangan
│   └── uploads/               # Direktori publik untuk avatar & tanda tangan
├── resources/
│   └── views/                 # Template Blade UI (Dashboard, Approval, Fuel, Evaluasi, Master)
├── routes/
│   └── web.php                # Definisi rute web & proteksi hak akses
├── storage/                   # Berkas log, sesi, dan penyimpanan privat tanda tangan
└── tests/
    └── Feature/               # Suite pengujian otomatis sistem
```

---

## 🛡️ Catatan Keamanan Produksi (*Production Checklist*)

Saat mendistribusikan aplikasi ke server produksi:
1. Pastikan `APP_ENV=production` dan `APP_DEBUG=false` pada `.env`.
2. Gunakan protokol aman **HTTPS** dan setel `SESSION_SECURE_COOKIE=true`.
3. Jalankan optimasi cache Laravel:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. Pastikan folder `storage/` dan `bootstrap/cache/` memiliki izin tulis (*write permission*) yang tepat untuk web server (`chmod -R 775`).

---

## 📄 Hak Cipta & Lisensi

Dikembangkan untuk kebutuhan operasional **PT United Tractors Tbk Site Satui**.  
Hak cipta dilindungi undang-undang. Seluruh aset grafis dan logo adalah milik PT United Tractors Tbk.
