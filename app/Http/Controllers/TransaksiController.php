<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::user()->canManageFinance()) {
            return back()->with('error', 'Anda tidak memiliki hak akses mencatat kas.');
        }

        $validated = $request->validate([
            'tipe' => ['required', 'in:masuk,keluar'],
            'kategori_id' => ['required', 'exists:kategori_dana,id'],
            'nama_pihak' => ['nullable', 'string', 'max:150'],
            'jumlah' => ['required', 'numeric', 'min:100'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'kategori_id.required' => 'Kategori kas wajib dipilih.',
            'jumlah.required' => 'Nominal wajib diisi.',
            'jumlah.min' => 'Nominal minimal Rp 100.',
            'tanggal.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        $transaksi = Transaksi::create([
            'tipe' => $validated['tipe'],
            'kategori_id' => $validated['kategori_id'],
            'nama_pihak' => $validated['nama_pihak'] ?: ($validated['tipe'] === 'masuk' ? 'Muzakki' : 'Mustahik'),
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'],
            'keterangan' => $validated['keterangan'],
            'dibuat_oleh' => Auth::id(),
        ]);

        AuditLog::catat('tambah', 'transaksi', $transaksi->id, null, [
            'tipe' => $transaksi->tipe,
            'kategori_id' => $transaksi->kategori_id,
            'jumlah' => $transaksi->jumlah,
            'keterangan' => $transaksi->keterangan,
        ]);

        return back()->with('success', 'Transaksi kas berhasil dicatat ke sistem.');
    }

    public function void(Request $request, $id)
    {
        if (!Auth::user()->canManageFinance()) {
            return back()->with('error', 'Hanya Admin dan Bendahara yang dapat membatalkan transaksi.');
        }

        $request->validate([
            'alasan_batal' => ['required', 'string', 'max:255'],
        ], [
            'alasan_batal.required' => 'Alasan pembatalan transaksi kas wajib diisi.',
        ]);

        $transaksi = Transaksi::findOrFail($id);

        if ($transaksi->isBatal()) {
            return back()->with('info', 'Transaksi ini sudah pernah dibatalkan.');
        }

        $dataLama = $transaksi->only(['dibatalkan_at', 'alasan_batal']);

        $transaksi->update([
            'dibatalkan_at' => now(),
            'alasan_batal' => $request->alasan_batal,
        ]);

        AuditLog::catat('void', 'transaksi', $transaksi->id, $dataLama, [
            'dibatalkan_at' => now(),
            'alasan_batal' => $request->alasan_batal,
        ]);

        return back()->with('info', "Transaksi #{$transaksi->id} berhasil dibatalkan (void).");
    }

    public function export(Request $request)
    {
        $cari = trim((string)$request->query('cari', ''));
        $mulai = (string)$request->query('tgl_mulai', '');
        $selesai = (string)$request->query('tgl_selesai', '');
        $tipe = (string)$request->query('tipe', 'semua');
        $kategori = (int)$request->query('kategori', 0);

        $query = Transaksi::with(['kategori', 'pembuat'])
            ->whereNull('dibatalkan_at')
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc');

        if (in_array($tipe, ['masuk', 'keluar'], true)) {
            $query->where('tipe', $tipe);
        }
        if ($kategori > 0) {
            $query->where('kategori_id', $kategori);
        }
        if ($mulai !== '') {
            $query->whereDate('tanggal', '>=', $mulai);
        }
        if ($selesai !== '') {
            $query->whereDate('tanggal', '<=', $selesai);
        }
        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('nama_pihak', 'like', "%{$cari}%")
                  ->orWhere('keterangan', 'like', "%{$cari}%");
            });
        }

        $daftar = $query->get();

        if ($mulai !== '' && $selesai !== '') {
            $periode = date('d/m/Y', strtotime($mulai)) . ' s.d. ' . date('d/m/Y', strtotime($selesai));
        } elseif ($mulai !== '') {
            $periode = 'Mulai ' . date('d/m/Y', strtotime($mulai));
        } elseif ($selesai !== '') {
            $periode = 'Sampai ' . date('d/m/Y', strtotime($selesai));
        } else {
            $periode = 'Semua transaksi';
        }

        $totalMasukSemua = Transaksi::aktif()->masuk()->sum('jumlah');
        $totalKeluarSemua = Transaksi::aktif()->keluar()->sum('jumlah');
        $saldoTotal = $totalMasukSemua - $totalKeluarSemua;

        $fileName = 'Laporan_Keuangan_UPZ_' . date('Ymd_His') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Cache-Control' => 'max-age=0',
        ];

        $html = view('dashboard.export_excel', [
            'daftar' => $daftar,
            'periode' => $periode,
            'saldoTotal' => $saldoTotal,
            'petugas' => Auth::user(),
        ])->render();

        return response($html, 200, $headers);
    }
}
