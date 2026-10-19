<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\KategoriDana;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonasiMasukController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'semua');

        $counts = [
            'semua' => Donasi::count(),
            'menunggu' => Donasi::where('status', 'menunggu')->count(),
            'dibayar' => Donasi::where('status', 'dibayar')->count(),
            'diterima' => Donasi::where('status', 'diterima')->count(),
            'ditolak' => Donasi::where('status', 'ditolak')->count(),
            'dibatalkan' => Donasi::where('status', 'dibatalkan')->count(),
        ];

        $query = Donasi::with(['user', 'transaksi'])->latest();

        if ($status !== 'semua' && array_key_exists($status, $counts)) {
            $query->where('status', $status);
        }

        $donasiList = $query->paginate(20)->withQueryString();

        return view('donasi_masuk.index', compact('donasiList', 'status', 'counts'));
    }

    public function konfirmasi($id)
    {
        if (!Auth::user()->canManageFinance()) {
            return back()->with('error', 'Hanya Admin dan Bendahara yang dapat mengonfirmasi donasi.');
        }

        $donasi = Donasi::findOrFail($id);

        if ($donasi->status === 'diterima') {
            return back()->with('info', 'Donasi ini sudah pernah dikonfirmasi.');
        }

        DB::transaction(function () use ($donasi) {
            // Temukan kategori kas pemasukan yang sesuai
            $kategori = KategoriDana::where('kode', $donasi->kategori_kode)->first()
                ?? KategoriDana::where('tipe', 'masuk')->first();

            // Catat otomatis ke pembukuan kas
            $transaksi = Transaksi::create([
                'tipe' => 'masuk',
                'kategori_id' => $kategori?->id,
                'nama_pihak' => $donasi->nama_tampil,
                'jumlah' => $donasi->jumlah,
                'tanggal' => now(),
                'keterangan' => "Donasi online: {$donasi->kode} ({$donasi->program_nama} - {$donasi->paket})",
                'dibuat_oleh' => Auth::id(),
            ]);

            $dataLama = $donasi->only(['status', 'transaksi_id', 'diproses_oleh', 'diproses_at']);

            $donasi->update([
                'status' => 'diterima',
                'transaksi_id' => $transaksi->id,
                'diproses_oleh' => Auth::id(),
                'diproses_at' => now(),
            ]);

            AuditLog::catat('donasi_terima', 'donasi', $donasi->id, $dataLama, [
                'kode' => $donasi->kode,
                'jumlah' => $donasi->jumlah,
                'transaksi_id' => $transaksi->id,
            ]);
        });

        return back()->with('success', "Donasi {$donasi->kode} berhasil dikonfirmasi dan dicatat ke kas.");
    }

    public function tolak(Request $request, $id)
    {
        if (!Auth::user()->canManageFinance()) {
            return back()->with('error', 'Hanya Admin dan Bendahara yang dapat menolak donasi.');
        }

        $request->validate([
            'alasan_tolak' => ['required', 'string', 'max:255'],
        ], [
            'alasan_tolak.required' => 'Alasan penolakan donasi wajib diisi.',
        ]);

        $donasi = Donasi::findOrFail($id);

        if ($donasi->status === 'diterima') {
            return back()->with('error', 'Donasi yang sudah diterima tidak dapat ditolak.');
        }

        $dataLama = $donasi->only(['status', 'alasan_tolak', 'diproses_oleh', 'diproses_at']);

        $donasi->update([
            'status' => 'ditolak',
            'alasan_tolak' => $request->alasan_tolak,
            'diproses_oleh' => Auth::id(),
            'diproses_at' => now(),
        ]);

        AuditLog::catat('donasi_tolak', 'donasi', $donasi->id, $dataLama, [
            'kode' => $donasi->kode,
            'alasan_tolak' => $request->alasan_tolak,
        ]);

        return back()->with('info', "Donasi {$donasi->kode} telah ditolak.");
    }
}
