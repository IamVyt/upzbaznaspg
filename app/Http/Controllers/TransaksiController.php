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
        $transaksi = Transaksi::with(['kategori', 'pembuat'])
            ->latest('tanggal')
            ->get();

        $fileName = 'Laporan_Kas_UPZ_BAZNAS_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transaksi) {
            $file = fopen('php://output', 'w');
            // Menulis BOM UTF-8 agar Excel membuka dengan encoding yang benar
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'ID Transaksi',
                'Tanggal',
                'Jenis',
                'Kategori',
                'Pihak Terkait',
                'Jumlah (Rp)',
                'Keterangan',
                'Status',
                'Alasan Batal',
                'Petugas Pencatat',
            ]);

            foreach ($transaksi as $t) {
                fputcsv($file, [
                    $t->id,
                    $t->tanggal ? $t->tanggal->format('d/m/Y H:i') : '-',
                    strtoupper($t->tipe),
                    $t->kategori?->nama ?? '-',
                    $t->nama_pihak ?? '-',
                    $t->jumlah,
                    $t->keterangan ?? '-',
                    $t->isBatal() ? 'Dibatalkan (Void)' : 'Sah / Aktif',
                    $t->alasan_batal ?? '-',
                    $t->pembuat?->name ?? 'Sistem',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
