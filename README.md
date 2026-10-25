# SIM Keuangan & Donasi Online UPZ BAZNAS Petrokimia Gresik (Laravel Edition)

Sistem Informasi Manajemen Keuangan dan Layanan Donasi Online Unit Pengumpul Zakat (UPZ) BAZNAS PT Petrokimia Gresik. Aplikasi ini telah dirombak (*refactored*) secara terstruktur dari arsitektur PHP Native ke **Framework Laravel 13** dan telah dilengkapi konfigurasi **Docker & Fly.io** dengan dukungan basis data **PostgreSQL**.

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
- **Verifikasi Email Registrasi:** Email wajib diisi saat mendaftar. Link verifikasi berlaku 60 menit dan bisa dikirim ulang dari banner atau halaman verifikasi.
- **Reset Password via Email:** Donatur dan petugas dapat meminta link reset password dari halaman login; token berlaku 60 menit, hanya sekali pakai, dan hanya dikirim ke email yang sudah diverifikasi.
- **Daftar Donatur Beranda (Opt-in):** Donatur yang telah terverifikasi dan menyetujui ditampilkan di landing page.

### 2. Back-Office & Manajemen Keuangan
- **Dashboard Keuangan Eksekutif:** Statistik total saldo, pemasukan, penyaluran, notifikasi donasi masuk, dan visualisasi grafik arus kas 6 bulan (Chart.js).
- **Verifikasi Donasi Masuk:** Petugas dapat memeriksa bukti pembayaran, menerima (otomatis mencatat ke jurnal kas pemasukan), atau menolak donasi.
- **Manajemen Transaksi Kas:** Pencatatan kas masuk dan keluar secara langsung, dilengkapi fitur pembatalan (*void*) bersyarat dan pencatatan alasan batal.
- **Pengaturan Rekening Pembayaran:** Administrator dapat mengaktifkan/menonaktifkan rekening bank atau e-wallet serta mengubah nomor dan atas nama.
- **Data Penyaluran:** Rekapitulasi penyaluran donasi per program beserta riwayat penerimaan.
- **Data Donatur & Donasi:** Pencarian terpadu, filter program, dan hak hapus bagi Administrator.
- **Laporan & Ekspor:** Unduh rekapitulasi pembukuan kas dalam format spreadsheet (kompatibel Excel).
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
├── bootstrap/app.php                # Konfigurasi middleware & routing Laravel 13
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
└── .env.example                     # Contoh environment (SQLite lokal, PostgreSQL siap produksi)
```

---

## 🛠️ Panduan Instalasi Lokal

### 1. Kebutuhan Sistem
- PHP >= 8.3 (dengan ekstensi `pdo`, `pdo_sqlite` / `pdo_pgsql` / `pdo_mysql`, `mbstring`, `gd`, `zip`)
- Composer >= 2.x
- Node.js & npm hanya diperlukan bila ingin mengembangkan aset Vite. Aset produksi saat ini sudah tersedia di `public/css` dan `public/js`.

### 2. Langkah Instalasi
1. Clone repositori:
   ```bash
   git clone https://github.com/IamVyt/upzbaznaspg.git
   cd upzbaznaspg
   ```

2. Salin environment dan generate app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Buat file database SQLite lokal (default `.env.example`):
   ```bash
   php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
   ```

4. Jalankan migrasi dan seeder database:
   ```bash
   php artisan migrate --seed
   ```

5. Buat tautan storage berkas:
   ```bash
   php artisan storage:link
   ```

6. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser: `http://localhost:8000`

> Pintasan: `composer setup` menjalankan instalasi dependency, membuat `.env`/SQLite, generate key, migrasi, dan storage link. Setelah itu jalankan `php artisan db:seed` bila ingin akun contoh.

### 3. Menjalankan Tes
```bash
php artisan test
```
Tes memakai SQLite in-memory (lihat `phpunit.xml`), jadi tidak mengubah database lokal.

---

## 🔐 Akun Default Sistem

Setelah menjalankan `php artisan migrate --seed`, akun-akun berikut siap digunakan:

| Peran | Username | Password | Deskripsi Akses |
|---|---|---|---|
| **Admin** | `admin` | `admin123` | Akses penuh seluruh sistem, audit log, kelola user |
| **Bendahara** | `bendahara` | `bendahara123` | Kelola kas masuk/keluar & konfirmasi donasi |
| **Ketua** | `ketua` | `ketua123` | Monitor dashboard dan laporan (view-only) |
| **Donatur** | `donatur` | `donatur123` | Akun donatur umum (portal donasi) |

> **Penting:** ganti seluruh password akun default sebelum dipakai produksi. Seeder memakai `firstOrCreate`, sehingga menjalankan seeder ulang **tidak** menimpa password, role, atau nomor rekening yang sudah diubah pengurus.
>
> Akun demo dari seeder ditandai sudah terverifikasi agar alur presentasi tidak terhalang. Ganti emailnya dengan alamat asli melalui **Akun** atau **Kelola pengguna**; perubahan email otomatis membatalkan status verifikasi dan mengirim link verifikasi baru.

---

## ✉️ Verifikasi Email

- Registrasi mewajibkan email. Setelah mendaftar, pengguna diarahkan ke `/email/verify` dan menerima link verifikasi yang berlaku **60 menit**.
- Halaman verifikasi menyediakan tombol **kirim ulang** dengan batas 6 permintaan per menit.
- Selama email belum diverifikasi, banner peringatan tampil di portal maupun panel petugas. Setelah verifikasi, pengguna diarahkan ke dashboard (petugas) atau beranda (donatur).
- Mengubah email di **Akun** atau lewat **Kelola pengguna** otomatis membatalkan status verifikasi lama dan mengirim link baru ke alamat tersebut.
- Akun lama yang belum pernah verifikasi dapat memakai tombol kirim ulang di banner. Status verifikasi juga terlihat di halaman **Akun**.

---

## 🔑 Reset Password via Email

- Halaman permintaan: `/forgot-password` (link “Lupa password?” juga tersedia di halaman login).
- Link reset hanya dikirim ke email yang **sudah diverifikasi**, berlaku **60 menit**, dan hanya bisa dipakai sekali. Setelah password direset, pengguna diarahkan ke halaman login.
- Akun tanpa email atau belum verifikasi tidak dapat memakai fitur ini. Admin dapat mengisi email petugas lewat menu **Kelola pengguna**, sedangkan donatur dapat melengkapi email di menu **Akun**.
- Di lokal, `MAIL_MAILER=log` membuat link reset tercatat di `storage/logs/laravel.log`, bukan dikirim ke inbox.
- Di production, isi konfigurasi SMTP di environment (lihat daftar variabel pada bagian deployment). Gunakan App Password bila penyedia email mewajibkannya, dan pastikan `APP_URL` mengarah ke domain HTTPS aplikasi agar link benar.

---

## 🚀 Panduan Deployment ke Render.com + Supabase

Proyek ini telah dikonfigurasi menggunakan **Docker** dan **Render Blueprint (`render.yaml`)**, sehingga siap di-deploy langsung ke **Render.com** dengan basis data cloud **Supabase (PostgreSQL)**.

### 1. Persiapan Database di Supabase
1. Buka [Supabase Dashboard](https://supabase.com/dashboard) dan buat proyek baru (misal: `upz-baznas-pg`).
2. Masuk ke menu **Project Settings** > **Database**.
3. Di bagian **Connection String**, pilih mode **URI** atau salin parameter koneksi:
   - **Host:** misal `aws-0-ap-southeast-1.pooler.supabase.com`
   - **Port:** `5432` (atau `6543`)
   - **Database:** `postgres`
   - **User:** `postgres.[project-id]`
   - **Password:** Password yang Anda buat saat inisialisasi proyek.

---

### 2. Langkah Deploy ke Render.com
1. Buka [Render Dashboard](https://dashboard.render.com).
2. Klik **New +** > **Web Service**.
3. Sambungkan repositori GitHub Anda: **`IamVyt/upzbaznaspg`**.
4. Pilih **Docker** sebagai *Runtime / Environment*.
5. Di bagian **Environment Variables**, tambahkan:
   - `APP_NAME` = `UPZ BAZNAS Petrokimia Gresik`
   - `APP_URL` = `https://nama-app-anda.onrender.com`
   - `APP_ENV` = `production`
   - `APP_DEBUG` = `false`
   - `APP_KEY` = `(Hasil dari 'php artisan key:generate --show')` **wajib; container berhenti bila kosong**
   - `APP_TIMEZONE` = `Asia/Jakarta`
   - `SESSION_SECURE_COOKIE` = `true`
   - `DB_CONNECTION` = `pgsql`
   - `DB_HOST` = `[Host Supabase Anda]`
   - `DB_PORT` = `5432`
   - `DB_DATABASE` = `postgres`
   - `DB_USERNAME` = `[Username Supabase Anda]`
   - `DB_PASSWORD` = `[Password Supabase Anda]`
   - `DB_SSLMODE` = `require`
   - *(Atau cukup isi satu variabel `DB_URL` dengan Connection String dari Supabase)*.
   - Untuk reset password via email, tambahkan `MAIL_MAILER` = `smtp`, `MAIL_HOST`, `MAIL_PORT` (mis. `587`), `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` = `null`, dan `MAIL_FROM_ADDRESS` sesuai akun pengirim.
6. Klik **Create Web Service**.

Container Docker akan otomatis dibangun oleh Render. Saat container menyala, skrip `docker/entrypoint.sh` akan:
- Menyesuaikan port Nginx secara otomatis dengan port internal Render (`$PORT`).
- Memeriksa `APP_KEY`; container berhenti dengan pesan jelas bila variabel ini kosong.
- Menjalankan migrasi tabel ke Supabase (`php artisan migrate --force`).
- Mempersiapkan symbolic link storage dan cache produksi.
- **Tidak** menjalankan seeder otomatis, agar password petugas tidak di-reset setiap restart/deploy.

---

### 3. Inisialisasi Data Awal (Seeder) di Render
Setelah deploy berstatus *Live*:
1. Masuk ke halaman Web Service Anda di Render > tab **Shell**.
2. Jalankan perintah seeder:
   ```bash
   php artisan db:seed --force
   ```
3. Akun petugas (`admin`, `bendahara`, `ketua`) dan master kategori/metode pembayaran langsung aktif di Supabase! 🎉

> Seeder aman dijalankan ulang karena hanya membuat data yang belum ada. Bila ingin seed otomatis sekali saat container start, set `RUN_SEEDERS=true` pada environment, lalu hapus lagi setelah berhasil.

---

## 🔒 Catatan Keamanan
- Bukti transfer disimpan di `storage/app/private` dan hanya bisa diakses oleh pemilik donasi atau petugas melalui route `bukti/{kode}`.
- Login, registrasi, permintaan reset password, kirim ulang verifikasi email, pembuatan donasi, dan upload bukti dibatasi rate limit untuk mengurangi brute force/spam.
- Link verifikasi memakai signed URL dan hash email; mengubah email membatalkan verifikasi agar status lama tidak bisa dipakai ulang.
- Zona waktu aplikasi mengikuti `APP_TIMEZONE` (default `Asia/Jakarta`).
- Session dan cache memakai driver file; aplikasi ini dirancang untuk satu instance. Untuk banyak instance, pindahkan session/cache ke Redis atau database secara terpusat.
- Jangan commit `.env`, bukti transfer, atau file upload lain ke repositori.

