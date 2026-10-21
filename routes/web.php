<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataDonasiController;
use App\Http\Controllers\DataPenyaluranController;
use App\Http\Controllers\DonasiController;
use App\Http\Controllers\DonasiMasukController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RekeningBayarController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Portal Publik ---
Route::get('/', [HomeController::class, 'index'])->name('home');

// Langkah 1: Pilih Paket
Route::get('/salurkan', [DonasiController::class, 'salurkan'])->name('donasi.create');

// Langkah 2: Isi Data
Route::get('/bayar', [DonasiController::class, 'bayarForm'])->name('donasi.bayarForm');
Route::post('/bayar', [DonasiController::class, 'prosesBayar'])->name('donasi.prosesBayar');

// Langkah 3: Detail, Instruksi Bayar, dan Bukti Donasi
Route::get('/donasi/sukses/{kode}', [DonasiController::class, 'sukses'])->name('donasi.sukses');
Route::get('/donasi/bayar/{kode}', fn($kode) => redirect()->route('donasi.sukses', $kode))->name('donasi.bayar');

// Aksi Donatur (ganti metode, lapor bayar, ubah tampilan beranda, batalkan)
Route::post('/donasi/lapor-bayar/{kode}', [DonasiController::class, 'uploadBukti'])->name('donasi.laporBayar');
Route::post('/donasi/ganti-metode/{kode}', [DonasiController::class, 'gantiMetode'])->name('donasi.gantiMetode');
Route::post('/donasi/ubah-tampilan/{kode}', [DonasiController::class, 'ubahTampilan'])->name('donasi.ubahTampilan');
Route::post('/donasi/batal/{kode}', [DonasiController::class, 'batal'])->name('donasi.batal');
Route::get('/donasi/cetak/{kode}', [DonasiController::class, 'cetak'])->name('donasi.cetak');
Route::get('/bukti/{kode}', [DonasiController::class, 'showBukti'])->name('donasi.bukti');

// --- Tamu (Guest) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// --- Terautentikasi (Semua Pengguna) ---
Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/akun', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/akun/profil', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/akun/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/donasi-saya', [DonasiController::class, 'riwayat'])->name('donasi.saya');
});

// --- Back-Office Petugas (Admin, Bendahara, Ketua) ---
Route::middleware(['auth', 'staff'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard', [DashboardController::class, 'proses'])->name('dashboard.proses');

    // Donasi Masuk
    Route::get('/donasi-masuk', [DonasiMasukController::class, 'index'])->name('donasi_masuk.index');
    Route::post('/donasi-masuk', [DonasiMasukController::class, 'proses'])->name('donasi_masuk.proses');
    Route::post('/donasi-masuk/{id}/konfirmasi', [DonasiMasukController::class, 'konfirmasi'])->name('donasi_masuk.konfirmasi');
    Route::post('/donasi-masuk/{id}/tolak', [DonasiMasukController::class, 'tolak'])->name('donasi_masuk.tolak');

    // Data Donasi Tersalurkan
    Route::get('/data-donasi', [DataDonasiController::class, 'index'])->name('data_donasi.index');
    Route::post('/data-donasi', [DataDonasiController::class, 'proses'])->name('data_donasi.proses');
    Route::delete('/data-donasi/{id}', [DataDonasiController::class, 'destroy'])->name('data_donasi.destroy');
    Route::post('/data-donasi/hapus-semua', [DataDonasiController::class, 'destroyAll'])->name('data_donasi.destroyAll');

    // Data Penyaluran
    Route::get('/data-penyaluran', [DataPenyaluranController::class, 'index'])->name('data_penyaluran.index');

    // Katalog Program & Kategori Kas
    Route::get('/program', [ProgramController::class, 'index'])->name('program.index');

    // Transaksi Kas
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::post('/transaksi/{id}/void', [TransaksiController::class, 'void'])->name('transaksi.void');
    Route::get('/transaksi/export', [TransaksiController::class, 'export'])->name('transaksi.export');

    // Pengaturan Metode Pembayaran
    Route::get('/pembayaran', [RekeningBayarController::class, 'index'])->name('pembayaran.index');
    Route::post('/pembayaran', [RekeningBayarController::class, 'proses'])->name('pembayaran.proses');
    Route::put('/pembayaran', [RekeningBayarController::class, 'update'])->name('pembayaran.update');

    // Riwayat Aktivitas (Audit Log)
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::post('/audit/clear', [AuditLogController::class, 'clear'])->name('audit.clear');

    // Manajemen Pengguna Petugas
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Shortcut redirect /dashboard -> /admin/dashboard
Route::get('/dashboard', fn() => redirect()->route('dashboard'));
