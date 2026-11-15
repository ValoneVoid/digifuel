# 📖 Panduan Penggunaan Sistem FuelLink
### PT United Tractors Tbk — Site Satui
*Panduan Praktis Pengoperasian Sistem untuk Seluruh Peran (Driver, Pengawas SPV, Dept Head/Manager, Petugas Vendor BBM, dan Administrator)*

---

## 📌 Daftar Isi
1. [Sekilas Tentang FuelLink](#-sekilas-tentang-fuellink)
2. [Alur 4 Langkah Pengisian BBM](#-alur-4-langkah-pengisian-bbm)
3. [Panduan Berdasarkan Peran](#-panduan-berdasarkan-peran)
   - [A. Driver / Operator Lapangan](#a-driver--operator-lapangan)
   - [B. Supervisor (SPV) / Pengawas](#b-supervisor-spv--pengawas)
   - [C. Dept Head / Manager](#c-dept-head--manager)
   - [D. Vendor Penyedia BBM (Persetujuan & Realisasi)](#d-vendor-penyedia-bbm-persetujuan--realisasi)
   - [E. Administrator Sistem](#e-administrator-sistem)
   - [F. Semua Pengguna: Tanda Tangan Digital Langsung](#f-semua-pengguna-tanda-tangan-digital-langsung)
4. [Tanya Jawab Umum (FAQ & Troubleshooting)](#-tanya-jawab-umum-faq--troubleshooting)
   - [Apakah Audit Trail Terhapus Tiap Bulan?](#apakah-audit-trail-terhapus-tiap-bulan)
   - [Cara Akses FuelLink dari HP / Smartphone](#cara-akses-fuellink-dari-hp--smartphone)
   - [Bagaimana Jika Ada Salah Ketik Jumlah Liter?](#bagaimana-jika-ada-salah-ketik-jumlah-liter)
   - [Mengapa Kolom SPV Tertulis N/A pada Lembar PDF?](#mengapa-kolom-spv-tertulis-na-pada-lembar-pdf)
   - [Cara Membuat Tanda Tangan Digital Rapi & Proporsional](#cara-membuat-tanda-tangan-digital-rapi--proporsional)
   - [Bagaimana Cara Mengelompokkan Biaya BBM Berdasarkan Divisi?](#bagaimana-cara-mengelompokkan-biaya-bbm-berdasarkan-divisi)

---

## 🌟 Sekilas Tentang FuelLink

**FuelLink** adalah sistem digital terpadu yang dirancang untuk menggantikan kupon bensin/solar kertas manual di PT United Tractors Tbk Site Satui.

Dengan FuelLink:
- Pengajuan permohonan BBM dilakukan lewat aplikasi (bisa via HP atau komputer).
- Persetujuan berjenjang berjalan otomatis disertai notifikasi WhatsApp resmi.
- Tidak ada lagi risiko kupon kertas hilang, rusak, atau tercecer.
- Bukti pengisian BBM tercatat rapi, akurat, dan dapat dicetak ke dokumen PDF resmi berstandar korporat.

---

## 🔄 Alur 4 Langkah Pengisian BBM

```text
[1. DRIVER]            [2. SPV]              [3. DEPT HEAD]         [4. VENDOR BBM]
Membuat Permohonan ──▶ Memeriksa & Setuju ──▶ Persetujuan Akhir ──▶ Persetujuan & Realisasi Fisik
(Isi KM & Liter)       (Bisa Sesuaikan Liter) (Tanda Tangan PDF)    (Input Liter Aktual Dispenser)
```

### Arti Warna Status Pengajuan:
- 🟡 **Kuning (Draft / Menunggu)**: Baru diajukan, sedang menunggu giliran persetujuan pejabat terkait.
- 🔵 **Biru (Disetujui Berjenjang)**: Sedang dalam proses persetujuan SPV atau Dept Head.
- 🟢 **Hijau (Siap Diisi / Selesai)**: Transaksi pengisian telah selesai divalidasi dan dicatat oleh Vendor BBM.
- 🔴 **Merah (Ditolak / Dibatalkan)**: Pengajuan ditolak oleh penilai dengan alasan yang tercantum pada catatan.

---

## 👥 Panduan Berdasarkan Peran

### A. Driver / Operator Lapangan
*Tugas: Mengajukan BBM saat tangki unit kendaraan membutuhkan pengisian.*

1. **Buka Menu "Form Request"**:
   - Klik menu **Form Request** di baris navigasi atas.
2. **Pilih Unit & Masukkan Data**:
   - Pilih **Unit Kendaraan** (misal: `UT 208 DA 8191`). Kapasitas tangki dan model unit akan muncul otomatis.
   - Pilih **Area Kerja** Anda (misal: *Office Satui* atau *Rayon Kusan*).
   - Masukkan **Jumlah Liter** yang dibutuhkan.
   - Masukkan **Angka Kilometer / Jam (KM/HM)** terkini dari odometer unit.
   - Masukkan **Keterangan Keperluan** (misal: *"Operasional Hauling Shift Pagi"*).
3. **Kirimkan Permohonan**:
   - Klik tombol biru **"Kirim Permohonan"**.
   - Pengajuan Anda akan otomatis masuk ke antrean Supervisor (SPV) Anda.
4. **Pantau Status & Ambil BBM**:
   - Buka menu **History** untuk memantau status.
   - Setelah status disetujui Dept Head, Anda dapat menuju fuel truck / dispenser BBM dan menunjukkan nomor pengajuan atau lembar cetak PDF ke petugas Vendor BBM.

---

### B. Supervisor (SPV) / Pengawas
*Tugas: Memverifikasi kewajaran pemakaian BBM unit kerja sebelum diajukan ke Manager.*

1. **Menerima Pemberitahuan**:
   - Saat driver mengajukan BBM, Anda akan menerima notifikasi WhatsApp otomatis.
2. **Buka Menu "Pengajuan"**:
   - Lihat daftar tiket pengajuan yang berstatus *Pending Stage 1*.
   - Klik tombol **Detail / Periksa**.
3. **Pemeriksaan Data & Penyesuaian Liter**:
   - Periksa angka odometer dan rasio pemakaian sebelumnya.
   - **Fitur Khusus SPV**: Jika driver meminta liter berlebih, Anda dapat langsung mengoreksi angka pada kolom *"Sesuaikan Jumlah Liter Disetujui"*.
4. **Keputusan Persetujuan**:
   - Klik **"Setujui Permohonan"** untuk meneruskan ke Dept Head.
   - Atau klik **"Tolak"** jika data tidak sesuai dan tuliskan alasan penolakannya.

---

### C. Dept Head / Manager
*Tugas: Memberikan otorisasi manajerial tingkat departemen.*

1. Buka menu **Pengajuan** (tiket yang berstatus *Pending Stage 2*).
2. Periksa ringkasan permohonan yang telah divalidasi oleh SPV.
3. Klik tombol hijau **"Approve Manager"**.
4. Tanda tangan digital resmi Anda akan otomatis tersemat ke dokumen bukti pengajuan, dan tiket pengajuan siap direalisasikan oleh Vendor BBM.
5. **Alur Khusus Pengajuan Mandiri (*Smart Bypass*)**:
   - Jika Anda (Dept Head / Manager) mengajukan permohonan BBM untuk kendaraan operasional Anda sendiri, sistem akan secara otomatis melompati (*bypass*) tahap persetujuan SPV.
   - Permohonan langsung berstatus disetujui manajerial dan diteruskan ke Vendor BBM, sehingga tidak terjadi kebingungan struktural atasan meminta persetujuan bawahan.
   - Pada lembar cetak dokumen resmi PDF, kolom persetujuan SPV akan tertera rapi dengan status `N/A` (Garis tanda tangan tetap simetris dan sejajar).

---

### D. Vendor Penyedia BBM (Persetujuan & Realisasi)
*Tugas: Memvalidasi ketersediaan stok, melakukan pengisian solar fisik, dan mendokumentasikan liter aktual dispenser dalam satu langkah mudah.*

1. Login menggunakan akun resmi perwakilan vendor (misal: `CV. Adya Mandiri`).
2. Buka menu **Pengajuan** (tiket yang berstatus antrean Vendor).
3. Saat driver tiba di dispenser BBM / fuel truck, lakukan pengisian fisik solar ke tangki unit.
4. Klik tombol hijau **"Setuju (Tahap 4: Vendor BBM)"**:
   - Sistem akan menampilkan formulir pengisian dispenser.
   - Masukkan **Liter Aktual Terisi** sesuai display meter dispenser.
   - Masukkan **Angka KM/HM Odometer** unit terkini.
   - Masukkan **Waktu Pengisian Selesai**.
5. Klik **"Setujui & Selesaikan Pengisian"**. Status pengajuan otomatis berubah menjadi **Completed** (Selesai 100%) dan tanda tangan Vendor langsung tercatat pada dokumen resmi!

---

### E. Administrator Sistem
*Tugas: Mengelola konfigurasi master data, hak akses, audit trail, dan kebersihan sistem.*

1. **Navigasi Cepat Master Data (Tombol Burger Bar ☰)**:
   - Pada halaman **Master Data**, klik tombol bulat **☰** di pojok kiri bar navigasi untuk melompat langsung ke 7 kategori konfigurasi tanpa harus menggeser layar.
2. **Pencarian Cepat & Pengurutan Tabel (Sort A-Z / 1-9)**:
   - Ketik di kotak pencarian untuk menyaring data seketika.
   - Klik kepala kolom tabel (`<th>`) mana saja untuk mengurutkan data (angka, abjad, harga rupiah, dan tanggal) secara instan.
3. **Pengelolaan Unit & Kolom Divisi / Beban Account**:
   - Saat mendaftarkan atau mengedit unit kendaraan di Tab 2 (Master Unit), isi kolom **Divisi / Beban Account** (contoh: *Service*, *Spareparts*, *Hauling*, atau *Supporting*).
   - Data divisi ini otomatis tersinkronisasi ke:
     - Tabel **Evaluasi Konsumsi BBM** (`/evaluation`) untuk memilah beban biaya per bagian.
     - Berkas **Ekspor Excel Multi-Sheet** (`.xlsx`) pada Sheet *Master_Kendaraan* dan Sheet *Dashboard*.
4. **Periode Evaluasi BBM Dinamis**:
   - Filter periode evaluasi konsumsi BBM otomatis membuka bulan berjalan saat diakses (misal November 2026), tanpa perlu memilih bulan secara manual.
5. **Ekspor Arsip & Pembersihan Audit Trail**:
   - Di Tab 7 (Audit Trail), terdapat tombol hijau **"Ekspor Arsip Excel"** untuk mengunduh seluruh rekam jejak sistem ke format `.xlsx`.
   - Tombol abu-abu **"Kelola Retensi Log"** memungkinkan admin membersihkan log lama (misal log berusia > 6 bulan) dengan verifikasi keamanan mengetik `BERSIHKAN`.

---

### F. Semua Pengguna: Tanda Tangan Digital Langsung
*Tanda tangan digital digunakan pada lembar bukti persetujuan dan pengisian BBM resmi (format PDF A4).*

Setiap pengguna (Driver, SPV, Dept Head, Petugas Vendor BBM) dapat mengatur tanda tangannya melalui menu **Profil Pengguna** (`/profile`):

1. **Cara 1: Gambar Langsung di Layar (Sangat Direkomendasikan)**:
   - Pilih tab **"Gambar di Layar (Canvas)"**.
   - Gunakan **mouse / kursor** (di komputer/laptop) atau **sentuhan jari / stylus pen** (di HP/tablet).
   - Bubuhkan tanda tangan Anda di atas kotak kanvas mengikuti garis panduan abu-abu (*baseline guide*).
   - Jika goresan kurang rapi, klik tombol merah **"Bersihkan"** untuk mengulang.
   - Klik tombol hijau **"Simpan Tanda Tangan"**.
   - *Keunggulan*: Sistem secara otomatis memotong area kosong di sekeliling goresan (*smart auto-crop*), sehingga tanda tangan Anda tampak presisi, proporsional, dan tidak buram pada dokumen resmi PDF.
2. **Cara 2: Unggah Berkas Gambar**:
   - Pilih tab **"Unggah Berkas Gambar"**.
   - Pilih file gambar tanda tangan dari perangkat Anda (format PNG berlatar transparan atau JPG).
   - Klik tombol simpan.

---

## ❓ Tanya Jawab Umum (FAQ & Troubleshooting)

### Apakah Audit Trail Terhapus Tiap Bulan?
> **Jawabannya: TIDAK.**
> - Data audit disimpan permanen di database untuk kepentingan audit internal/eksternal.
> - Data audit berupa teks berukuran sangat kecil (~1 MB per 5.000 transaksi) sehingga tidak akan membuat server penuh.
> - Demi menjaga kebersihan server, sistem dilengkapi:
>   1. **Ekspor Arsip Excel**: Admin dapat mengunduh rekapan log kapan saja sebagai arsip offline.
>   2. **Auto-Retensi Mingguan**: Sistem otomatis merapikan data yang sudah melewati batas usia aman (180 hari / 6 bulan).

### Cara Akses DigiFuel dari HP / Smartphone
> 1. Hubungkan HP ke jaringan Wi-Fi kantor/site yang sama dengan server komputer.
> 2. Buka Google Chrome atau Safari di HP.
> 3. Masukkan alamat IP komputer lokal (contoh: `http://10.2.75.153:8000`).
> 4. Halaman akan tampil rapi dan nyaman untuk layar sentuh ponsel.

### Bagaimana Jika Ada Salah Ketik Jumlah Liter?
> Jangan panik! Supervisor (SPV) Anda dapat melihat dan mengoreksi jumlah liter secara langsung di formulir persetujuan sebelum menekan tombol setuju.

### Mengapa Kolom SPV Tertulis N/A pada Lembar PDF?
> Kolom SPV berstatus `N/A` (Not Applicable / Bypassed) terjadi ketika permohonan diajukan langsung oleh level manajerial (Dept Head / Manager). Sesuai aturan hierarki organisasi, atasan tidak memerlukan tanda tangan bawahan (SPV). Garis tanda tangan pada PDF dirancang tetap sejajar dan simetris secara tipografi agar dokumen tetap rapi dan profesional.

### Cara Membuat Tanda Tangan Digital Rapi & Proporsional
> Buka menu **Profil**, lalu pilih tab **Gambar di Layar**. Gambarlah dengan goresan yang jelas di atas kanvas. Sistem DigiFuel dilengkapi teknologi *Auto-Crop Bounding Box* yang otomatis mendeteksi dan memotong ruang kosong di sekeliling tanda tangan Anda sebelum dicetak ke dokumen resmi.

### Bagaimana Cara Mengelompokkan Biaya BBM Berdasarkan Divisi?
> 1. Masuk ke menu **Master Data** > Tab **Unit Kendaraan**.
> 2. Isi kolom **Divisi / Beban Account** pada masing-masing unit (misal: *Service*, *Spareparts*, *Supporting*).
> 3. Buka menu **Evaluasi**, kolom divisi akan otomatis muncul dan dapat difilter atau diunduh ke Excel multi-sheet.

---
*DigiFuel — PT United Tractors Tbk Site Satui. Efisiensi, Akurasi, dan Transparansi Operasional.*
