# SIM Keuangan & Donasi Online UPZ BAZNAS Petrokimia Gresik (Laravel Edition)

Sistem Informasi Manajemen Keuangan dan Layanan Donasi Online Unit Pengumpul Zakat (UPZ) BAZNAS PT Petrokimia Gresik. Aplikasi ini telah dirombak (*refactored*) secara terstruktur dari arsitektur PHP Native ke **Framework Laravel (terbaru)** dan telah dilengkapi konfigurasi **Docker & Fly.io** dengan dukungan basis data **PostgreSQL**.

---

## ✨ Fitur Utama Sistem

### 1. Portal Publik & Donasi Online
- **Landing Page Interaktif:** Profil UPZ, program penyaluran, mitra, alur kerja, dan kontak.
- **Formulir Donasi Terpadu:** Pemilihan program, paket nominal (atau nominal bebas), hitung zakat penghasilan 2.5%, dan opsi anonim (*Hamba Allah*).
- **Multi-Metode Pembayaran:**
  - Transfer Bank (BSI, BCA, BNI, BRI, Mandiri).
  - E-Wallet (GoPay, OVO, DANA, ShopeePay).
  - QRIS (Scan QR code).
  - Pembayaran Tunai langsung di kantor UPZ.
- **Instruksi Transfer & Unggah Bukti:** Halaman petunjuk pembayaran unik dan form upload bukti transfer aman.
- **Kuitansi Digital Siap Cetak:** Format tanda terima sementara & bukti setor donasi resmi (`window.print()`).
- **Portal Donatur:** Riwayat donasi pribadi donatur dan pengaturan profil akun.
- **Daftar Donatur Beranda (Opt-in):** Donatur yang telah terverifikasi dan menyetujui ditampilkan di landing page.

### 2. Back-Office & Manajemen Keuangan
- **Dashboard Keuangan Eksekutif:** Statistik total saldo, pemasukan, penyaluran, notifikasi donasi masuk, dan visualisasi grafik arus kas 6 bulan (Chart.js).
- **Verifikasi Donasi Masuk:** Petugas dapat memeriksa bukti pembayaran, menerima (otomatis mencatat ke jurnal kas pemasukan), atau menolak donasi.
- **Manajemen Transaksi Kas:** Pencatatan kas masuk dan keluar secara langsung, dilengkapi fitur pembatalan (*void*) bersyarat dan pencatatan alasan batal.
- **Pengaturan Rekening Pembayaran:** Administrator dapat mengaktifkan/menonaktifkan rekening bank atau e-wallet serta mengubah nomor dan atas nama.
- **Data Penyaluran:** Rekapitulasi penyaluran donasi per program beserta riwayat penerimaan.
- **Data Donatur & Donasi:** Pencarian terpadu, filter program, ekspor, dan hak hapus bagi Administrator.
- **Laporan & Ekspor:** Unduh rekapitulasi pembukuan kas dalam format spreadsheet/CSV resmi.
- **Manajemen Pengguna:** Pengelolaan akun petugas (Admin, Bendahara, Ketua).

### 3. Keamanan & Tata Kelola
- **Role-Based Access Control (RBAC):**
  - `admin`: Akses penuh konfigurasi sistem, kelola pengguna, audit log, dan hapus data.
  - `bendahara`: Akses kelola transaksi kas masuk/keluar dan verifikasi donasi.
  - `ketua`: Hak akses monitor data (view-only laporan dan dashboard).
  - `user`: Akses donatur umum (portal donasi dan riwayat pribadi).
- **Audit Log Terperinci:** Mencatat setiap aktivitas penting (login, transaksi kas, void, verifikasi donasi, ubah konfigurasi) lengkap dengan data snapshot JSON, timestamp, dan IP address.
- **Proteksi Standar Industri:** Token CSRF otomatis, proteksi SQL Injection via Eloquent ORM, Password Bcrypt hashing, dan validasi berkas unggahan ketat.

---

## 📁 Struktur Direktori Laravel

```text
├── app/
│   ├── Helpers/helpers.php           # Helper format rupiah, tanggal Indonesia, audit log
│   ├── Http/
│   │   ├── Controllers/             # Seluruh logika controller (Home, Donasi, Auth, Dashboard, dll.)
│   │   └── Middleware/              # Middleware otorisasi peran (EnsureStaff, EnsureRole)
│   └── Models/                      # Eloquent Models (User, Donasi, Transaksi, KategoriDana, Rekening, dll.)
├── bootstrap/app.php                # Konfigurasi middleware & routing Laravel 11/12
├── config/upz.php                   # Konfigurasi program, penyaluran, dan profil UPZ
├── database/
│   ├── migrations/                  # Skema tabel database (PostgreSQL / MySQL / SQLite)
│   └── seeders/                     # Seeder akun awal, kategori kas, dan metode pembayaran
├── docker/
│   ├── entrypoint.sh                # Script inisialisasi container & cache Laravel
│   ├── nginx.conf                   # Web server konfigurasi Nginx port 8080
│   └── supervisord.conf             # Process manager Nginx + PHP-FPM
├── public/                          # Root dokumen web server, CSS, JS, dan Gambar
├── resources/views/                 # Template Blade (layouts, donasi, dashboard, auth, profile)
├── routes/web.php                   # Definisi seluruh rute aplikasi
├── Dockerfile                       # Multi-stage production container untuk Fly.io
├── fly.toml                         # Konfigurasi deployment Fly.io
└── .env.example                     # Contoh template environment (PostgreSQL ready)
```

---

## 🛠️ Panduan Instalasi Lokal

### 1. Kebutuhan Sistem
- PHP >= 8.2 (dengan ekstensi `pdo`, `pdo_pgsql` / `pdo_sqlite` / `pdo_mysql`, `mbstring`, `gd`, `zip`)
- Composer >= 2.x
- Node.js & npm (opsional untuk build frontend)

### 2. Langkah Instalasi
1. Clone repositori:
   ```bash
   git clone https://github.com/IamVyt/Magang-Baznaz.git
   cd Magang-Baznaz
   ```

2. Salin environment dan generate app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Jalankan migrasi dan seeder database:
   ```bash
   php artisan migrate --seed
   ```

4. Buat tautan storage berkas:
   ```bash
   php artisan storage:link
   ```

5. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser: `http://localhost:8000`

---

## 🔐 Akun Default Sistem

Setelah menjalankan `php artisan migrate --seed`, akun-akun berikut siap digunakan:

| Peran | Username | Password | Deskripsi Akses |
|---|---|---|---|
| **Admin** | `admin` | `admin123` | Akses penuh seluruh sistem, audit log, kelola user |
| **Bendahara** | `bendahara` | `bendahara123` | Kelola kas masuk/keluar & konfirmasi donasi |
| **Ketua** | `ketua` | `ketua123` | Monitor dashboard dan laporan (view-only) |
| **Donatur** | `donatur` | `donatur123` | Akun donatur umum (portal donasi) |

---

## 🚀 Panduan Deployment ke Fly.io

Proyek ini telah dilengkapi `Dockerfile`, `docker/entrypoint.sh`, dan `fly.toml` yang siap pakai untuk deployment ke Fly.io dengan basis data **PostgreSQL**.

### Langkah-langkah Deploy:

1. **Login ke Fly CLI:**
   ```bash
   fly auth login
   ```

2. **Buat Database PostgreSQL di Fly.io (jika belum ada):**
   ```bash
   fly postgres create --name magang-baznaz-db --region sin
   ```
   *(Atau gunakan PostgreSQL cloud eksternal seperti Supabase / Neon).*

3. **Inisialisasi atau Sambungkan Aplikasi:**
   ```bash
   fly launch --copy-config
   ```
   *Ketika ditanya setup database, Anda dapat menghubungkan PostgreSQL yang dibuat pada langkah 2 atau set URL koneksinya.*

4. **Hubungkan Database PostgreSQL ke Aplikasi Fly:**
   ```bash
   fly postgres attach magang-baznaz-db --app magang-baznaz
   ```
   *Perintah ini otomatis mengisikan `DATABASE_URL` ke Fly Secrets.*

5. **Set Kunci Enkripsi dan Variabel Environment Penting:**
   ```bash
   fly secrets set APP_KEY=$(php artisan key:generate --show)
   fly secrets set DB_CONNECTION=pgsql
   ```

6. **Deploy Aplikasi ke Fly.io:**
   ```bash
   fly deploy
   ```
   Saat deployment berjalan, `fly.toml` secara otomatis menjalankan `php artisan migrate --force` untuk memperbarui skema tabel di PostgreSQL.

7. **Jalankan Seeder Awal di Fly.io:**
   ```bash
   fly ssh console -C "php artisan db:seed --force"
   ```

8. **Buka Aplikasi:**
   ```bash
   fly open
   ```

Aplikasi Anda kini berjalan secara live di jaringan global Fly.io! 🎉
