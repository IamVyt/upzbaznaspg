<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\KategoriDana;
use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik kas aktif (tidak dibatalkan)
        $totalMasuk = Transaksi::aktif()->masuk()->sum('jumlah');
        $totalKeluar = Transaksi::aktif()->keluar()->sum('jumlah');
        $saldoKas = $totalMasuk - $totalKeluar;

        // Bulan berjalan
        $awalBulan = now()->startOfMonth();
        $akhirBulan = now()->endOfMonth();

        $masukBulanIni = Transaksi::aktif()
            ->masuk()
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->sum('jumlah');

        $keluarBulanIni = Transaksi::aktif()
            ->keluar()
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->sum('jumlah');

        // Donasi online yang menunggu verifikasi petugas
        $donasiMenungguCount = Donasi::whereIn('status', ['menunggu', 'dibayar'])->count();

        // 10 transaksi kas terbaru
        $transaksiTerbaru = Transaksi::with(['kategori', 'pembuat'])
            ->latest('tanggal')
            ->take(10)
            ->get();

        // Data Chart 6 bulan terakhir
        $chartLabels = [];
        $chartMasuk = [];
        $chartKeluar = [];

        for ($i = 5; $i >= 0; $i--) {
            $bln = Carbon::now()->subMonths($i);
            $namaBln = $bln->translatedFormat('M Y');
            $start = $bln->copy()->startOfMonth();
            $end = $bln->copy()->endOfMonth();

            $chartLabels[] = $namaBln;
            $chartMasuk[] = (float) Transaksi::aktif()->masuk()->whereBetween('tanggal', [$start, $end])->sum('jumlah');
            $chartKeluar[] = (float) Transaksi::aktif()->keluar()->whereBetween('tanggal', [$start, $end])->sum('jumlah');
        }

        // Master data untuk modal transaksi kas cepat
        $kategoriMasuk = KategoriDana::aktif()->masuk()->get();
        $kategoriKeluar = KategoriDana::aktif()->keluar()->get();
        $muzakkiList = Muzakki::where('aktif', true)->orderBy('nama')->get();
        $mustahikList = Mustahik::where('aktif', true)->orderBy('nama')->get();

        return view('dashboard.index', compact(
            'saldoKas',
            'totalMasuk',
            'totalKeluar',
            'masukBulanIni',
            'keluarBulanIni',
            'donasiMenungguCount',
            'transaksiTerbaru',
            'chartLabels',
            'chartMasuk',
            'chartKeluar',
            'kategoriMasuk',
            'kategoriKeluar',
            'muzakkiList',
            'mustahikList'
        ));
    }
}
