<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\KategoriDana;
use App\Models\Transaksi;
use App\Support\BuktiDonasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonasiMasukController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'semua');
        $cari = trim((string) $request->query('cari', ''));

        // Hitung cepat untuk kartu statistik (semua data, tidak terpengaruh filter tab atau pencarian)
        $hitung = [
            'dibayar' => ['n' => 0, 'total' => 0],
            'menunggu' => ['n' => 0, 'total' => 0],
            'diterima' => ['n' => 0, 'total' => 0],
        ];
        $totals = Donasi::selectRaw('status, COUNT(*) as n, COALESCE(SUM(jumlah), 0) as total')
            ->groupBy('status')
            ->get();
        foreach ($totals as $b) {
            if (isset($hitung[$b->status])) {
                $hitung[$b->status] = ['n' => (int) $b->n, 'total' => (float) $b->total];
            }
        }
        $batal = ['n' => 0, 'total' => 0];
        foreach ($totals as $b) {
            if (in_array($b->status, ['ditolak', 'dibatalkan'], true)) {
                $batal['n'] += (int) $b->n;
                $batal['total'] += (float) $b->total;
            }
        }

        $tab = [
            'semua' => ['Semua', []],
            'dibayar' => ['Perlu dicek', ['dibayar']],
            'menunggu' => ['Menunggu pembayaran', ['menunggu']],
            'diterima' => ['Sudah tersalurkan', ['diterima']],
            'batal' => ['Ditolak atau dibatalkan', ['ditolak', 'dibatalkan']],
        ];
        if (! isset($tab[$status])) {
            $status = 'semua';
        }

        $query = Donasi::query()->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        if (! empty($tab[$status][1])) {
            $query->whereIn('status', $tab[$status][1]);
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('kode', 'like', "%{$cari}%")
                    ->orWhere('kontak', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%")
                    ->orWhere('nip', 'like', "%{$cari}%")
                    ->orWhere('catatan', 'like', "%{$cari}%");
            });
        }

        $baris = (clone $query)->count();
        $daftar = $query->paginate(20)->withQueryString();

        $metode = config('upz.metode_pembayaran', [
            'qris' => ['QRIS', 'i-qris'],
            'bank' => ['Transfer Bank', 'i-bank'],
            'ewallet' => ['E-Wallet', 'i-wallet'],
            'tunai' => ['Tunai di Kantor', 'i-cash'],
        ]);

        $bolehHapus = Auth::user()->isAdmin();

        return view('donasi_masuk.index', compact(
            'daftar', 'hitung', 'batal', 'tab', 'status', 'cari', 'baris', 'metode', 'bolehHapus'
        ));
    }

    public function proses(Request $request)
    {
        if (! Auth::user()->canManageFinance()) {
            return back()->with('danger', 'Anda tidak memiliki izin untuk memproses donasi.');
        }

        $aksi = $request->input('aksi');
        $id = (int) $request->input('id');
        $userId = Auth::id();

        if ($aksi === 'terima') {
            $donasi = Donasi::where('id', $id)->whereIn('status', ['menunggu', 'dibayar'])->first();
            if (! $donasi) {
                return back()->with('danger', 'Donasi tidak ditemukan atau sudah diproses petugas lain.');
            }

            $kategori = KategoriDana::where('kode', $donasi->kategori_kode)->where('tipe', 'masuk')->where('aktif', true)->first()
                ?? KategoriDana::where('tipe', 'masuk')->where('aktif', true)->first();

            if (! $kategori) {
                return back()->with('danger', 'Kategori dana '.$donasi->kategori_kode.' tidak ditemukan atau tidak aktif. Periksa menu Program dan kategori.');
            }

            DB::transaction(function () use ($donasi, $kategori, $userId) {
                $namaPihak = $donasi->samarkan_nama ? 'Hamba Allah' : $donasi->nama;
                $metodeConfig = config('upz.metode_pembayaran', []);
                $namaMetode = $metodeConfig[$donasi->metode][0] ?? strtoupper($donasi->metode);
                $via = $namaMetode.(($donasi->channel_nama && $donasi->channel_nama !== $namaMetode) ? ' ('.$donasi->channel_nama.')' : '');
                $ket = 'Donasi online '.$donasi->kode.', '.$donasi->program_nama.', '.$donasi->paket.', via '.$via;
                $catatan = trim((string) preg_replace('/\s+/', ' ', (string) $donasi->catatan));
                if ($catatan !== '' && ! $donasi->samarkan_nama) {
                    $ket .= '. Catatan: '.$catatan;
                }

                $trx = Transaksi::create([
                    'tipe' => 'masuk',
                    'kategori_id' => $kategori->id,
                    'nama_pihak' => $namaPihak,
                    'jumlah' => $donasi->jumlah,
                    'tanggal' => now(),
                    'keterangan' => $ket,
                    'dibuat_oleh' => $userId,
                ]);

                $dataLama = $donasi->only(['status', 'transaksi_id', 'diproses_oleh', 'diproses_at']);

                $donasi->update([
                    'status' => 'diterima',
                    'transaksi_id' => $trx->id,
                    'diproses_oleh' => $userId,
                    'diproses_at' => now(),
                ]);

                AuditLog::catat('donasi_terima', 'donasi', $donasi->id, $dataLama, [
                    'status' => 'diterima',
                    'kode' => $donasi->kode,
                    'jumlah' => $donasi->jumlah,
                    'transaksi_id' => $trx->id,
                ]);
            });

            return back()->with('success', 'Donasi '.$donasi->kode.' dikonfirmasi: status Sudah tersalurkan dan tercatat sebagai pemasukan kas.');
        } elseif ($aksi === 'tolak') {
            $alasan = trim((string) $request->input('alasan'));
            if ($alasan === '' || mb_strlen($alasan) > 255) {
                return back()->with('danger', 'Alasan penolakan wajib diisi (maksimal 255 karakter).');
            }

            $donasi = Donasi::where('id', $id)->whereIn('status', ['menunggu', 'dibayar'])->first();
            if (! $donasi) {
                return back()->with('danger', 'Donasi tidak ditemukan atau sudah diproses petugas lain.');
            }

            $dataLama = $donasi->only(['status']);

            $donasi->update([
                'status' => 'ditolak',
                'alasan_tolak' => $alasan,
                'diproses_oleh' => $userId,
                'diproses_at' => now(),
            ]);

            AuditLog::catat('donasi_tolak', 'donasi', $donasi->id, $dataLama, [
                'status' => 'ditolak',
                'kode' => $donasi->kode,
                'alasan' => $alasan,
            ]);

            return back()->with('success', 'Donasi '.$donasi->kode.' ditandai ditolak.');
        } elseif ($aksi === 'hapus') {
            if (! Auth::user()->isAdmin()) {
                return back()->with('danger', 'Hanya admin yang dapat menghapus data donasi.');
            }

            $donasi = Donasi::where('id', $id)->whereIn('status', ['menunggu', 'ditolak', 'dibatalkan'])->first();
            if (! $donasi) {
                return back()->with('danger', 'Donasi tidak ditemukan atau statusnya sudah berubah. Donasi yang sudah dibayar atau sudah tersalurkan tidak bisa dihapus dari sini.');
            }

            $snapshot = $donasi->toArray();
            BuktiDonasi::hapus($donasi->bukti);
            $donasi->delete();

            AuditLog::catat('donasi_hapus', 'donasi', $id, $snapshot, null);

            return back()->with('success', 'Donasi '.$snapshot['kode'].' atas nama '.$snapshot['nama'].' dihapus.');
        } elseif ($aksi === 'hapus_semua') {
            if (! Auth::user()->isAdmin()) {
                return back()->with('danger', 'Hanya admin yang dapat menghapus data donasi.');
            }

            $tabHapus = (string) $request->input('status');
            $sasaran = ['menunggu' => ['menunggu'], 'batal' => ['ditolak', 'dibatalkan']][$tabHapus] ?? null;

            if (! $sasaran) {
                return back()->with('danger', 'Hapus semua hanya tersedia di tab Menunggu pembayaran dan tab Ditolak atau dibatalkan.');
            }

            $daftarHapus = Donasi::whereIn('status', $sasaran)->get();
            if ($daftarHapus->isEmpty()) {
                return back()->with('info', 'Tidak ada data donasi yang cocok untuk dihapus.');
            }

            $count = $daftarHapus->count();
            $totalNominal = $daftarHapus->sum('jumlah');

            foreach ($daftarHapus as $d) {
                BuktiDonasi::hapus($d->bukti);
                $d->delete();
            }

            AuditLog::catat('donasi_hapus_semua', 'donasi', null, null, [
                'status' => $tabHapus,
                'jumlah_donasi' => $count,
                'total_nominal' => $totalNominal,
            ]);

            return back()->with('success', $count.' donasi dihapus dari tab tersebut.');
        }

        return back();
    }

    public function konfirmasi($id)
    {
        return $this->proses(new Request(['aksi' => 'terima', 'id' => $id]));
    }

    public function tolak(Request $request, $id)
    {
        return $this->proses(new Request(['aksi' => 'tolak', 'id' => $id, 'alasan' => $request->input('alasan_tolak', $request->input('alasan'))]));
    }
}
