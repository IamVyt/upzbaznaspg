# SIM Keuangan & Donasi Online UPZ BAZNAS Petrokimia Gresik

Sistem Informasi Manajemen Keuangan dan Layanan Donasi Online untuk Unit Pengumpul Zakat (UPZ) BAZNAS PT Petrokimia Gresik. Aplikasi web ini dibangun menggunakan **PHP 8 (native)**, **MySQL / MariaDB**, antarmuka modern yang responsif dan elegan, serta dilengkapi sistem keamanan berlapis.

---

## ✨ Fitur Utama

### 1. Portal Publik & Donasi Online
- **Landing Page Interaktif:** Informasi profil UPZ, program penyaluran, mitra, dan kontak.
- **Kalkulator & Pilihan Program Donasi:** Menyalurkan Zakat Maal, Zakat Fitrah, Infak, Sedekah, Dana Sosial, Fidyah, dan Wakaf.
- **Metode Pembayaran Lengkap:**
  - **Transfer Bank:** Rekening resmi BCA, BNI, BRI, Mandiri, dan BSI.
  - **E-Wallet:** GoPay, OVO, DANA, dan ShopeePay.
  - **QRIS:** Pembayaran instan via scan QR code.
- **Unggah Bukti Transfer:** Donatur dapat mengunggah bukti pembayaran secara aman.
- **Kuitansi / Bukti Donasi Digital:** Bukti setor resmi dengan format siap cetak (`window.print()`).
- **Portal Donatur (Muzakki):** Pendaftaran akun donatur dan pelacakan status riwayat donasi pribadi.
- **Donatur Terbaru di Beranda:** Section di landing page menampilkan donatur yang sudah tersalurkan donasinya dan **menyetujui** tampil lewat kotak centang di form donasi (bawaan tidak tampil). Yang tampil hanya nama (atau Hamba Allah), program, dan waktu, tanpa nominal, kontak, atau NIP. Catatan donatur tampil sebagai doa hanya bila dicentang terpisah. Jika yang bersedia tampil masih kurang dari 3, section memakai testimoni statis dari `php/data.php`.
- **Pengaturan Akun:** Menu dropdown di header setelah login (Pengaturan akun dan Keluar). Pengguna dapat memperbarui profil (nama, username, email, nomor WhatsApp, NIP) dan mengganti password. Data profil otomatis mengisi form donasi.

### 2. Back-Office & Manajemen Keuangan
- **Dashboard Keuangan:** Statistik ringkasan kas (Saldo, Pemasukan, Penyaluran), grafik arus kas 6 bulan terakhir dengan Chart.js.
- **Verifikasi Donasi Masuk:** Petugas dapat memeriksa bukti pembayaran, menerima (otomatis mencatat ke kas dan menambah saldo), atau menolak donasi dengan alasan tertentu.
- **Manajemen Transaksi Terpadu:** Pencatatan kas masuk dan keluar secara langsung, dilengkapi fitur pembatalan (*void*) bersyarat dan pencatatan alasan batal.
- **Pengaturan Metode Pembayaran:** Admin dapat mengaktifkan, menonaktifkan, dan mengatur nomor rekening bank atau e-wallet.
- **Data Penyaluran:** Ringkasan donasi yang sudah tersalurkan per program, lengkap dengan riwayat siapa yang berdonasi dan donasi apa.
- **Data Donasi:** Daftar donasi yang berhasil tersalurkan beserta data donaturnya, dengan pencarian dan filter program. Admin dapat menghapus satu per satu atau semuanya. Datanya ikut hilang dari Donasi masuk dan Donasi saya, sedangkan pembukuan kas tidak berubah. Setiap penghapusan tercatat di Riwayat aktivitas.
- **Program & Kategori Dana:** Daftar program yang sama dengan landing page (sumbernya `php/data.php`), lengkap dengan kategori kas dan total donasi tiap program. Halaman ini hanya untuk melihat.
- **Master Data Mustahik dan Muzakki:** Tabelnya tetap dipakai sebagai pilihan di form transaksi kas, tetapi halaman `mustahik.php` dan `muzakki.php` sudah tidak ada di sidebar.
- **Laporan & Ekspor Excel:** Unduh rekapitulasi laporan transaksi kas dalam format spreadsheet dengan format resmi.

### 3. Keamanan & Tata Kelola
- **Role-Based Access Control (RBAC):**
  - `admin`: Akses penuh semua modul, manajemen pengguna, pengaturan pembayaran, dan audit log.
  - `bendahara`: Akses kelola transaksi kas, verifikasi donasi, dan master data.
  - `ketua`: Hak akses monitor data (view-only laporan dan dashboard).
  - `user`: Akses donatur umum (portal donasi dan riwayat transaksi pribadi).
- **Audit Log Terperinci:** Mencatat setiap aktivitas krusial (login, transaksi kas, void, perubahan pengguna) beserta snapshot data JSON, waktu, dan IP address. Admin dapat membersihkan riwayat lewat tombol **Hapus riwayat** (1 minggu terakhir, 1 bulan terakhir, atau semua). Penghapusan itu sendiri tercatat sebagai satu baris `audit_hapus`.
- **Proteksi Keamanan:**
  - Token CSRF pada seluruh form POST.
  - Prepared statements untuk mencegah SQL Injection.
  - Password hashing menggunakan algoritma `PASSWORD_DEFAULT` (Bcrypt).
  - Validasi ketat unggahan berkas (tipe MIME, ukuran berkas, penamaan acak unik).

---

## 📁 Struktur Direktori

```text
├── css/                   # Stylesheet utama & stylesheet modul aplikasi
│   ├── app.css
│   └── style.css
├── database/              # Skrip skema dan migrasi database
│   └── migrasi.sql
├── img/                   # Aset gambar & maskot SVG
│   └── maskot.svg
├── js/                    # Skrip JavaScript interaktif
│   ├── app.js
│   └── main.js
├── php/                   # Logika backend, partials, dan fungsi inti
│   ├── app_footer.php
│   ├── app_header.php
│   ├── crud.php
│   ├── data.php
│   ├── footer.php
│   ├── fungsi.php
│   ├── header.php
│   ├── koneksi.php
│   ├── menu_akun.php
│   ├── pilih_metode.php
│   └── sprite.php
├── uploads/               # Direktori penyimpanan bukti transfer (terproteksi)
├── akun.php               # Pengaturan akun: profil & ganti password
├── audit.php              # Log aktivitas sistem
├── bayar.php              # Halaman instruksi transfer & upload bukti
├── bukti.php              # Secure image viewer untuk bukti pembayaran
├── cetak_donasi.php       # Kuitansi donasi resmi
├── dashboard.php          # Dashboard keuangan & ringkasan transaksi
├── data_donasi.php        # Data donasi berhasil beserta data donatur
├── data_penyaluran.php    # Ringkasan & riwayat donasi per program
├── donasi_aksi.php        # Endpoint aksi pembatalan / penanganan donasi
├── donasi_masuk.php       # Verifikasi donasi oleh petugas
├── donasi_saya.php        # Riwayat donasi pengguna
├── donasi_sukses.php      # Halaman konfirmasi setelah donasi diajukan
├── export_excel.php       # Ekspor laporan kas ke Excel
├── ganti_password.php     # Dialihkan ke akun.php (tautan lama)
├── index.php              # Beranda / Landing page publik
├── login.php              # Halaman masuk
├── logout.php             # Halaman keluar sistem
├── mustahik.php           # Kelola data mustahik (tidak ada di sidebar)
├── muzakki.php            # Kelola data muzakki (tidak ada di sidebar)
├── pembayaran.php         # Pengaturan rekening bank & e-wallet
├── program.php            # Daftar program (sama dengan landing page)
├── register.php           # Pendaftaran akun publik
├── salurkan.php           # Formulir donasi online
└── users.php              # Manajemen akun & hak akses petugas
```

---

## 🚀 Panduan Instalasi

### 1. Kebutuhan Sistem
- **Web Server:** Apache (XAMPP / Laragon) atau PHP Built-in Server
- **PHP:** Versi 8.1 atau lebih baru (ekstensi `mysqli`, `gd`, `json`, `mbstring` aktif)
- **Database:** MySQL / MariaDB

### 2. Langkah Pemasangan
1. Tempatkan proyek ini di direktori web server Anda (misal `C:\xampp\htdocs\magang`).
2. Nyalakan layanan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) dan buat database baru dengan nama `magang`.
4. Import file migrasi database:
   - Pilih database `magang` → klik tab **Import** → pilih berkas `database/migrasi.sql` → klik **Import**.
5. Sesuaikan konfigurasi database jika diperlukan pada file `php/koneksi.php`:
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db   = 'magang';
   ```
6. Jalankan aplikasi melalui browser:
   - Via XAMPP: `http://localhost/magang/`
   - Atau via PHP built-in server:
     ```bash
     php -S localhost:8080
     ```
     Lalu akses di `http://localhost:8080/`.

---

## 👥 Hak Cipta & Lisensi
Dikembangkan untuk kegiatan magang di **UPZ BAZNAS PT Petrokimia Gresik**.  
Seluruh hak cipta dilindungi.
