<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DataDonasiController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string)$request->query('cari', ''));
        $prog = trim((string)$request->query('program', ''));

        $opsiProgram = [];
        foreach (config('upz.program', []) as $p) {
            $opsiProgram[$p['slug']] = $p['nama'];
        }
        if (!isset($opsiProgram[$prog])) {
            $prog = '';
        }

        $bolehHapus = Auth::user()->isAdmin();

        // Ringkasan semua donasi berhasil (tidak ikut terfilter)
        $ring = [
            'n' => Donasi::where('status', 'diterima')->count(),
            'total' => Donasi::where('status', 'diterima')->sum('jumlah'),
            'donatur' => Donasi::where('status', 'diterima')
                ->get(['user_id', 'nama'])
                ->map(fn($d) => $d->user_id ? 'u' . $d->user_id : 'n' . $d->nama)
                ->unique()
                ->count(),
        ];
        $rata = $ring['n'] > 0 ? $ring['total'] / $ring['n'] : 0;

        // Catatan kas yatim (transaksi online yang donasinya sudah dihapus)
        $yatimQuery = Transaksi::where('tipe', 'masuk')
            ->whereNull('dibatalkan_at')
            ->where('keterangan', 'like', 'Donasi online %')
            ->whereNotIn('id', Donasi::whereNotNull('transaksi_id')->pluck('transaksi_id'));

        $yatimInfo = [
            'n' => (clone $yatimQuery)->count(),
            'total' => (clone $yatimQuery)->sum('jumlah'),
        ];

        // Query daftar donasi berhasil
        $query = Donasi::with(['user', 'transaksi'])
            ->where('status', 'diterima')
            ->orderByRaw('COALESCE(diproses_at, created_at) DESC')
            ->orderBy('id', 'desc');

        if ($prog !== '') {
            $query->where('program', $prog);
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('kode', 'like', "%{$cari}%")
                  ->orWhere('kontak', 'like', "%{$cari}%")
                  ->orWhere('email', 'like', "%{$cari}%")
                  ->orWhere('nip', 'like', "%{$cari}%")
                  ->orWhereHas('user', function ($uq) use ($cari) {
                      $uq->where('username', 'like', "%{$cari}%");
                  });
            });
        }

        $jml = (clone $query)->count();
        $daftar = $query->paginate(10)->withQueryString();

        $metode = config('upz.metode_pembayaran', [
            'qris'    => ['QRIS', 'i-qris'],
            'bank'    => ['Transfer Bank', 'i-bank'],
            'ewallet' => ['E-Wallet', 'i-wallet'],
            'tunai'   => ['Tunai di Kantor', 'i-cash'],
        ]);

        return view('data_donasi.index', compact(
            'daftar', 'ring', 'rata', 'yatimInfo', 'cari', 'prog', 'opsiProgram', 'jml', 'metode', 'bolehHapus'
        ));
    }

    public function proses(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('danger', 'Hanya admin yang memiliki izin untuk memproses tindakan ini.');
        }

        $aksi = (string)$request->input('aksi');

        if ($aksi === 'hapus') {
            $id = (int)$request->input('id');
            $d = Donasi::where('id', $id)->where('status', 'diterima')->first();
            if (!$d) {
                return back()->with('danger', 'Data donasi tidak ditemukan atau sudah dihapus.');
            }

            $batalKas = $request->boolean('batal_kas') && $d->transaksi_id;

            DB::transaction(function () use ($d, $batalKas) {
                if ($batalKas && $d->transaksi) {
                    $d->transaksi->update([
                        'dibatalkan_at' => now(),
                        'alasan_batal' => 'Donasi ' . $d->kode . ' dihapus oleh admin',
                    ]);
                }
                if ($d->bukti && Storage::disk('public')->exists($d->bukti)) {
                    Storage::disk('public')->delete($d->bukti);
                }
                $d->delete();
            });

            AuditLog::catat('donasi_hapus', 'donasi', $id, [
                'kode' => $d->kode, 'nama' => $d->nama, 'program' => $d->program_nama, 'paket' => $d->paket,
                'jumlah' => $d->jumlah, 'metode' => $d->metode, 'user_id' => $d->user_id, 'transaksi_id' => $d->transaksi_id,
                'kas_dibatalkan' => $batalKas ? 'ya' : 'tidak',
            ]);

            return back()->with('success', 'Donasi ' . $d->kode . ' atas nama ' . $d->nama . ' dihapus. '
                . ($batalKas ? 'Catatan kas-nya juga dibatalkan (void) dan saldo disesuaikan.' : 'Catatan di Dashboard kas tidak diubah.'));
        }

        if ($aksi === 'beranda') {
            $id = (int)$request->input('id');
            $d = Donasi::where('id', $id)->where('status', 'diterima')->first();
            if (!$d || !$d->tampil_beranda) {
                return back()->with('danger', 'Donasi tidak ditemukan atau donatur tidak mengizinkan tampil di beranda.');
            }

            $blokir = $d->beranda_blokir ? false : true;
            $d->update(['beranda_blokir' => $blokir]);

            AuditLog::catat('donasi_beranda', 'donasi', $id, ['kode' => $d->kode, 'disembunyikan' => $d->beranda_blokir], ['kode' => $d->kode, 'disembunyikan' => $blokir]);
            return back()->with('success', 'Donasi ' . $d->kode . ($blokir ? ' disembunyikan dari beranda.' : ' ditampilkan lagi di beranda.'));
        }

        if ($aksi === 'hapus_semua') {
            $daftarHapus = Donasi::where('status', 'diterima')->get();
            if ($daftarHapus->isEmpty()) {
                return back()->with('info', 'Tidak ada data donasi yang bisa dihapus.');
            }

            $batalKas = $request->boolean('batal_kas');
            $trxIds = $batalKas
                ? $daftarHapus->pluck('transaksi_id')->filter()->unique()->all()
                : [];

            DB::transaction(function () use ($daftarHapus, $trxIds) {
                if (!empty($trxIds)) {
                    Transaksi::whereIn('id', $trxIds)
                        ->whereNull('dibatalkan_at')
                        ->update([
                            'dibatalkan_at' => now(),
                            'alasan_batal' => 'Semua data donasi dihapus oleh admin',
                        ]);
                }
                foreach ($daftarHapus as $d) {
                    if ($d->bukti && Storage::disk('public')->exists($d->bukti)) {
                        Storage::disk('public')->delete($d->bukti);
                    }
                    $d->delete();
                }
            });

            AuditLog::catat('donasi_hapus_semua', 'donasi', null, null, [
                'jumlah_donasi' => $daftarHapus->count(),
                'total_nominal' => $daftarHapus->sum('jumlah'),
                'kas_dibatalkan' => count($trxIds),
            ]);

            return back()->with('success', $daftarHapus->count() . ' data donasi dihapus, termasuk dari Donasi masuk. '
                . ($batalKas ? count($trxIds) . ' catatan kas-nya juga dibatalkan (void) dan saldo disesuaikan.' : 'Catatan di Dashboard kas tidak diubah.'));
        }

        if ($aksi === 'batalkan_yatim') {
            $yatimIds = Transaksi::where('tipe', 'masuk')
                ->whereNull('dibatalkan_at')
                ->where('keterangan', 'like', 'Donasi online %')
                ->whereNotIn('id', Donasi::whereNotNull('transaksi_id')->pluck('transaksi_id'))
                ->pluck('id')
                ->all();

            if (!empty($yatimIds)) {
                Transaksi::whereIn('id', $yatimIds)->update([
                    'dibatalkan_at' => now(),
                    'alasan_batal' => 'Dibatalkan massal oleh admin karena data donasi asalnya sudah dihapus',
                ]);
                AuditLog::catat('yatim_batal', 'transaksi', null, null, ['jumlah' => count($yatimIds)]);
                return back()->with('success', count($yatimIds) . ' catatan kas dibatalkan (void).');
            }

            return back()->with('info', 'Tidak ada catatan kas yatim yang perlu dibatalkan.');
        }

        return back();
    }

    public function destroy($id)
    {
        return $this->proses(new Request(['aksi' => 'hapus', 'id' => $id]));
    }

    public function destroyAll(Request $request)
    {
        return $this->proses(new Request(['aksi' => 'hapus_semua', 'batal_kas' => $request->boolean('batal_kas')]));
    }
}
