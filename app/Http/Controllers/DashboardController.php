<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\KategoriDana;
use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Transaksi;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $katAll = KategoriDana::where('aktif', true)->orderBy('tipe')->orderBy('nama')->get();
        $katMap = ['masuk' => [], 'keluar' => []];
        foreach ($katAll as $k) {
            $katMap[$k->tipe][] = ['id' => (int)$k->id, 'nama' => $k->nama];
        }

        $muzakki = Muzakki::where('aktif', true)->orderBy('nama')->get();
        $mustahik = Mustahik::where('aktif', true)->orderBy('nama')->get();

        $cari = trim((string)$request->query('cari', ''));
        $mulai = (string)$request->query('tgl_mulai', '');
        $selesai = (string)$request->query('tgl_selesai', '');
        $tipe = (string)$request->query('tipe', 'semua');
        $kategori = (int)$request->query('kategori', 0);

        $f = [
            'cari' => $cari,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'tipe' => in_array($tipe, ['masuk', 'keluar'], true) ? $tipe : 'semua',
            'kategori' => $kategori,
        ];

        $adaFilter = $cari !== '' || $mulai !== '' || $selesai !== '' || $f['tipe'] !== 'semua' || $kategori > 0;

        // Saldo kas aktif akumulasi (semua waktu)
        $totalMasukSemua = Transaksi::aktif()->masuk()->sum('jumlah');
        $totalKeluarSemua = Transaksi::aktif()->keluar()->sum('jumlah');
        $saldo = $totalMasukSemua - $totalKeluarSemua;

        $isAdmin = Auth::user()->isAdmin();
        $jmlVoid = $isAdmin ? Transaksi::whereNotNull('dibatalkan_at')->count() : 0;

        // Total sesuai filter tanggal & kategori untuk KPI
        $kpiQuery = Transaksi::aktif();
        if ($mulai !== '') {
            $kpiQuery->whereDate('tanggal', '>=', $mulai);
        }
        if ($selesai !== '') {
            $kpiQuery->whereDate('tanggal', '<=', $selesai);
        }
        if ($kategori > 0) {
            $kpiQuery->where('kategori_id', $kategori);
        }

        $total = ['masuk' => 0, 'keluar' => 0];
        $jml = ['masuk' => 0, 'keluar' => 0];

        $totals = (clone $kpiQuery)->selectRaw("tipe, COALESCE(SUM(jumlah), 0) as total, COUNT(*) as n")
            ->groupBy('tipe')
            ->get();
        foreach ($totals as $r) {
            $total[$r->tipe] = (float)$r->total;
            $jml[$r->tipe] = (int)$r->n;
        }

        // Query tabel riwayat transaksi
        $query = Transaksi::with(['kategori', 'pembuat'])->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

        if ($f['tipe'] !== 'semua') {
            $query->where('tipe', $f['tipe']);
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

        $baris = (clone $query)->count();
        $riwayat = $query->paginate(8)->withQueryString();

        $qs = array_filter([
            'cari' => $f['cari'],
            'tgl_mulai' => $f['mulai'],
            'tgl_selesai' => $f['selesai'],
            'tipe' => $f['tipe'] === 'semua' ? '' : $f['tipe'],
            'kategori' => $f['kategori'] > 0 ? $f['kategori'] : '',
        ], fn($v) => $v !== '');

        // Donasi online yang menunggu verifikasi
        $menunggu = Donasi::whereIn('status', ['menunggu', 'dibayar'])->count();

        // Grafik arus kas 6 bulan terakhir
        $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $bulan = [];
        $label = [];
        $d = new DateTime('first day of this month');
        $d->modify('-5 months');
        for ($i = 0; $i < 6; $i++) {
            $key = $d->format('Y-m');
            $bulan[$key] = ['masuk' => 0, 'keluar' => 0];
            $label[] = $namaBulan[(int)$d->format('n') - 1] . ' ' . $d->format('y');
            $d->modify('+1 month');
        }

        $startMonth = array_key_first($bulan) . '-01 00:00:00';
        $graphData = Transaksi::aktif()
            ->where('tanggal', '>=', $startMonth)
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bln, tipe, SUM(jumlah) as total")
            ->groupBy('bln', 'tipe')
            ->get();

        foreach ($graphData as $r) {
            if (isset($bulan[$r->bln])) {
                $bulan[$r->bln][$r->tipe] = (float)$r->total;
            }
        }

        $chartMasuk = array_column(array_values($bulan), 'masuk');
        $chartKeluar = array_column(array_values($bulan), 'keluar');

        return view('dashboard.index', compact(
            'katAll', 'katMap', 'muzakki', 'mustahik', 'f', 'adaFilter', 'saldo', 'isAdmin',
            'jmlVoid', 'total', 'jml', 'baris', 'riwayat', 'qs', 'menunggu', 'label', 'chartMasuk', 'chartKeluar'
        ));
    }

    public function proses(Request $request)
    {
        if (!Auth::user()->canManageFinance()) {
            return back()->with('danger', 'Anda tidak memiliki izin untuk mengubah transaksi.');
        }

        $aksi   = $request->input('aksi', 'tambah');
        $id     = (int)$request->input('id', 0);
        $userId = Auth::id();

        if ($aksi === 'void') {
            $transaksi = Transaksi::where('id', $id)->whereNull('dibatalkan_at')->first();
            $alasan = trim((string)$request->input('alasan', ''));
            if (!$transaksi) {
                return back()->with('danger', 'Transaksi tidak ditemukan atau sudah dibatalkan.');
            }
            if ($alasan === '' || mb_strlen($alasan) > 255) {
                return back()->with('danger', 'Alasan pembatalan wajib diisi (maksimal 255 karakter).');
            }

            $dataLama = ['nama' => $transaksi->nama_pihak, 'jumlah' => $transaksi->jumlah];
            $transaksi->update([
                'dibatalkan_at' => now(),
                'alasan_batal' => $alasan,
            ]);

            AuditLog::catat('void', 'transaksi', $id, $dataLama, ['alasan' => $alasan]);
            return back()->with('success', 'Transaksi dibatalkan. Data tetap tersimpan dengan penanda void.');
        }

        if (in_array($aksi, ['hapus_void', 'hapus_semua_void'], true)) {
            if (!Auth::user()->isAdmin()) {
                return back()->with('danger', 'Hanya admin yang dapat menghapus permanen transaksi void.');
            }

            $targets = $aksi === 'hapus_void'
                ? Transaksi::where('id', $id)->whereNotNull('dibatalkan_at')->get()
                : Transaksi::whereNotNull('dibatalkan_at')->get();

            if ($targets->isEmpty()) {
                return back()->with('info', 'Tidak ada transaksi void yang bisa dihapus.');
            }

            $count = $targets->count();
            $totalNominal = $targets->sum('jumlah');
            $targetIds = $targets->pluck('id')->all();

            DB::transaction(function () use ($targetIds) {
                Donasi::whereIn('transaksi_id', $targetIds)->update(['transaksi_id' => null]);
                Transaksi::whereIn('id', $targetIds)->delete();
            });

            $satu = $aksi === 'hapus_void';
            AuditLog::catat(
                $aksi,
                'transaksi',
                $satu ? (int)$targets[0]->id : null,
                $satu ? ['nama' => $targets[0]->nama_pihak, 'tipe' => $targets[0]->tipe, 'jumlah' => $targets[0]->jumlah, 'alasan_batal' => $targets[0]->alasan_batal] : null,
                ['jumlah_transaksi' => $count, 'total_nominal' => $totalNominal]
            );

            return back()->with('success', $count . ' transaksi void dihapus permanen. Saldo kas tidak berubah.');
        }

        $tipe       = (string)$request->input('tipe');
        $kategoriId = (int)$request->input('kategori_id');
        $pihakId    = (int)$request->input('pihak_id') ?: null;
        $nama       = trim((string)$request->input('nama'));
        $jumlah     = (float)$request->input('jumlah');
        $tanggal    = (string)$request->input('tanggal');
        $keterangan = trim((string)$request->input('keterangan')) ?: null;

        $lama = ($aksi === 'ubah' && $id > 0) ? Transaksi::where('id', $id)->whereNull('dibatalkan_at')->first() : null;
        if ($aksi === 'ubah' && !$lama) {
            return back()->with('danger', 'Transaksi tidak ditemukan atau sudah dibatalkan.');
        }
        if ($lama) {
            $tipe = $lama->tipe;
        }
        if ($nama === '' && $tipe === 'masuk') {
            $nama = 'Hamba Allah';
        }

        $kat = KategoriDana::where('id', $kategoriId)->where('tipe', $tipe)->where('aktif', true)->first();

        if (!in_array($aksi, ['tambah', 'ubah'], true) || !in_array($tipe, ['masuk', 'keluar'], true)) {
            return back()->with('danger', 'Permintaan tidak valid.');
        }
        if ($nama === '' || mb_strlen($nama) > 150) {
            return back()->with('danger', 'Nama pihak wajib diisi dan maksimal 150 karakter.');
        }
        if (!$kat) {
            return back()->with('danger', 'Kategori transaksi wajib dipilih.');
        }
        if ($jumlah <= 0) {
            return back()->with('danger', 'Jumlah harus lebih dari 0.');
        }
        if (!$tanggal || $tanggal > date('Y-m-d')) {
            return back()->with('danger', 'Tanggal tidak valid atau melebihi hari ini.');
        }

        $dataBaru = [
            'tipe' => $tipe,
            'kategori_id' => $kategoriId,
            'pihak_id' => $pihakId,
            'nama_pihak' => $nama,
            'jumlah' => $jumlah,
            'tanggal' => Carbon::parse($tanggal . ' ' . date('H:i:s')),
            'keterangan' => $keterangan,
        ];

        if ($aksi === 'ubah') {
            $dataLama = ['nama' => $lama->nama_pihak, 'jumlah' => $lama->jumlah, 'kategori_id' => $lama->kategori_id];
            $lama->update($dataBaru);
            AuditLog::catat('edit', 'transaksi', $lama->id, $dataLama, $dataBaru);
            return back()->with('success', 'Perubahan data transaksi berhasil disimpan.');
        } else {
            $dataBaru['dibuat_oleh'] = $userId;
            $trx = Transaksi::create($dataBaru);
            AuditLog::catat('tambah', 'transaksi', $trx->id, null, $dataBaru);
            return back()->with('success', ($tipe === 'masuk' ? 'Pemasukan' : 'Pengeluaran') . ' kas berhasil dicatat.');
        }
    }
}
