# 📖 Panduan Penggunaan Sistem FuelLink
### PT United Tractors Tbk — Site Satui
*Panduan Praktis Pengoperasian Sistem untuk Seluruh Peran (Driver, Pengawas SPV, Dept Head/Manager, Vendor BBM, Fuelman, dan Administrator)*

---

## 📌 Daftar Isi
1. [Sekilas Tentang DigiFuel](#-sekilas-tentang-digifuel)
2. [Alur 5 Langkah Pengisian BBM](#-alur-5-langkah-pengisian-bbm)
3. [Panduan Berdasarkan Peran](#-panduan-berdasarkan-peran)
   - [A. Driver / Operator Lapangan](#a-driver--operator-lapangan)
   - [B. Supervisor (SPV) / Pengawas](#b-supervisor-spv--pengawas)
   - [C. Dept Head / Manager](#c-dept-head--manager)
   - [D. Vendor Penyedia BBM](#d-vendor-penyedia-bbm)
   - [E. Fuelman (Petugas Dispenser)](#e-fuelman-petugas-dispenser)
   - [F. Administrator Sistem](#f-administrator-sistem)
4. [Tanya Jawab Umum (FAQ & Troubleshooting)](#-tanya-jawab-umum-faq--troubleshooting)
   - [Apakah Audit Trail Terhapus Tiap Bulan?](#apakah-audit-trail-terhapus-tiap-bulan)
   - [Cara Akses DigiFuel dari HP / Smartphone](#cara-akses-digifuel-dari-hp--smartphone)
   - [Bagaimana Jika Ada Salah Ketik Jumlah Liter?](#bagaimana-jika-ada-salah-ketik-jumlah-liter)

---

## 🌟 Sekilas Tentang DigiFuel

**DigiFuel** adalah sistem digital terpadu yang dirancang untuk menggantikan kupon bensin/solar kertas manual di PT United Tractors Tbk Site Satui.

Dengan DigiFuel:
- Pengajuan permohonan BBM dilakukan lewat aplikasi (bisa via HP atau komputer).
- Persetujuan berjenjang berjalan otomatis disertai notifikasi WhatsApp resmi.
- Tidak ada lagi risiko kupon kertas hilang, rusak, atau tercecer.
- Bukti pengisian BBM tercatat rapi, akurat, dan dapat dicetak ke dokumen PDF resmi berstandar korporat.

---

## 🔄 Alur 5 Langkah Pengisian BBM

```text
[1. DRIVER]            [2. SPV]              [3. DEPT HEAD]         [4. VENDOR]           [5. FUELMAN]
Membuat Permohonan ──▶ Memeriksa & Setuju ──▶ Persetujuan Akhir ──▶ Konfirmasi Kuota ──▶ Mengisi Tangki Fisik
(Isi KM & Liter)       (Bisa Sesuaikan Liter) (Tanda Tangan PDF)    (Kupon BBM Aktif)     (Input Liter Aktual)
```

### Arti Warna Status Pengajuan:
- 🟡 **Kuning (Draft / Menunggu)**: Baru diajukan, sedang menunggu giliran persetujuan pejabat terkait.
- 🔵 **Biru (Disetujui Berjenjang)**: Sedang dalam proses persetujuan SPV atau Dept Head.
- 🟢 **Hijau (Siap Diisi / Selesai)**: Kupon solar sudah aktif dan telah selesai diisi oleh Fuelman.
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
   - Setelah status berubah menjadi **Approved by Vendor**, Anda dapat menuju fuel truck / dispenser BBM dan menunjukkan nomor pengajuan atau lembar cetak PDF ke petugas Fuelman.

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
4. Tanda tangan digital resmi Anda akan otomatis tersemat ke dokumen bukti pengajuan, dan notifikasi otomatis diteruskan ke pihak vendor penyedia solar.

---

### D. Vendor Penyedia BBM
*Tugas: Memvalidasi ketersediaan stok bahan bakar dan menerbitkan kuota kupon digital.*

1. Login menggunakan akun resmi perwakilan vendor (misal: `CV. Sudan Mandiri`).
2. Buka menu **Pengajuan** (tiket yang berstatus *Pending Stage 3*).
3. Periksa kesiapan suplai solar di storage / fuel truck.
4. Klik **"Approve Vendor"**. Kupon pengisian BBM dinyatakan resmi aktif dan siap disalurkan ke dispenser.

---

### E. Fuelman (Petugas Dispenser)
*Tugas: Mengisikan solar ke tangki unit dan mendokumentasikan liter aktual fisik.*

1. Saat driver tiba di titik pengisian, minta nomor tiket pengajuan (misal: `REQ-202611-001`) atau scan barcode kupon.
2. Lakukan pengisian fisik solar industri ke tangki kendaraan.
3. Buka menu **Realisasi Pengisian** di DigiFuel:
   - Masukkan **Liter Aktual Terisi** sesuai angka dispenser.
   - Masukkan **Angka KM/HM Odometer** unit terkini.
   - Catat **Waktu Pengisian Selesai**.
4. Klik **"Simpan Realisasi Pengisian"**. Status pengajuan otomatis berubah menjadi **Completed** (Selesai).

---

### F. Administrator Sistem
*Tugas: Mengelola konfigurasi master data, hak akses, audit trail, dan kebersihan sistem.*

1. **Navigasi Cepat Master Data (Tombol Burger Bar ☰)**:
   - Pada halaman **Master Data**, klik tombol bulat **☰** di pojok kiri bar navigasi untuk melompat langsung ke 7 kategori konfigurasi tanpa harus menggeser layar.
2. **Pencarian Cepat & Pengurutan Tabel (Sort A-Z / 1-9)**:
   - Ketik di kotak pencarian untuk menyaring data seketika.
   - Klik kepala kolom tabel (`<th>`) mana saja untuk mengurutkan data (angka, abjad, harga rupiah, dan tanggal) secara instan.
3. **Ekspor Arsip & Pembersihan Audit Trail**:
   - Di Tab 7 (Audit Trail), terdapat tombol hijau **"Ekspor Arsip Excel"** untuk mengunduh seluruh rekam jejak sistem ke format `.xlsx`.
   - Tombol abu-abu **"Kelola Retensi Log"** memungkinkan admin membersihkan log lama (misal log berusia > 6 bulan) dengan verifikasi keamanan mengetik `BERSIHKAN`.

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

---
*DigiFuel — PT United Tractors Tbk Site Satui. Efisiensi, Akurasi, dan Transparansi Operasional.*
