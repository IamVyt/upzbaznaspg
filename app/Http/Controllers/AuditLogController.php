<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string)$request->query('cari', ''));
        $aksi = trim((string)$request->query('aksi', ''));

        $rentangLog = [
            'minggu' => ['1 minggu terakhir', now()->subDays(7)],
            'bulan'  => ['1 bulan terakhir', now()->subMonth()],
            'semua'  => ['semua riwayat', null],
        ];

        $hitung = [
            'semua'  => AuditLog::count(),
            'minggu' => AuditLog::where('waktu', '>=', now()->subDays(7))->count(),
            'bulan'  => AuditLog::where('waktu', '>=', now()->subMonth())->count(),
        ];

        $pesanHapus = [
            'minggu' => 'Hapus ' . (int)$hitung['minggu'] . ' catatan aktivitas dalam 7 hari terakhir? Tindakan ini tidak bisa dibatalkan.',
            'bulan'  => 'Hapus ' . (int)$hitung['bulan'] . ' catatan aktivitas dalam 1 bulan terakhir? Tindakan ini tidak bisa dibatalkan.',
            'semua'  => 'Hapus SEMUA ' . (int)$hitung['semua'] . ' catatan aktivitas, bukan hanya yang tampil di hasil pencarian? Tindakan ini tidak bisa dibatalkan.',
        ];

        $ringkasan = [
            'semua'    => $hitung['semua'],
            'hari_ini' => AuditLog::whereDate('waktu', today())->count(),
            'aktif'    => AuditLog::where('waktu', '>=', now()->subDays(7))->pluck('username')->unique()->filter()->count(),
        ];

        $terbanyak = AuditLog::selectRaw('aksi, count(*) as n')
            ->groupBy('aksi')
            ->orderByRaw('count(*) desc')
            ->first();

        $daftarAksi = [];
        foreach (AuditLog::distinct()->pluck('aksi') as $a) {
            $daftarAksi[$a] = audit_label_aksi($a);
        }
        asort($daftarAksi);

        $query = AuditLog::with('user')->orderBy('id', 'desc');

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('username', 'like', "%{$cari}%")
                  ->orWhere('tabel', 'like', "%{$cari}%")
                  ->orWhere('ip', 'like', "%{$cari}%");
            });
        }

        if ($aksi !== '') {
            $query->where('aksi', $aksi);
        }

        $total = (clone $query)->count();
        $baris = $query->paginate(15)->withQueryString();

        $qs = array_filter(['cari' => $cari, 'aksi' => $aksi]);

        return view('audit.index', compact(
            'baris', 'total', 'cari', 'aksi', 'daftarAksi', 'ringkasan', 'terbanyak',
            'hitung', 'pesanHapus', 'rentangLog', 'qs'
        ));
    }

    public function clear(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('danger', 'Hanya administrator yang dapat membersihkan riwayat aktivitas.');
        }

        $rentang = (string)$request->input('rentang', 'semua');
        $query = AuditLog::query();

        $label = 'semua riwayat';
        if ($rentang === 'minggu') {
            $query->where('waktu', '>=', now()->subDays(7));
            $label = '1 minggu terakhir';
        } elseif ($rentang === 'bulan') {
            $query->where('waktu', '>=', now()->subMonth());
            $label = '1 bulan terakhir';
        }

        $hapus = $query->delete();

        if ($hapus > 0) {
            AuditLog::catat('audit_hapus', 'audit_log', null, null, ['rentang' => $label, 'dihapus' => $hapus]);
            return back()->with('success', $hapus . ' catatan aktivitas dihapus (' . $label . ').');
        }

        return back()->with('info', 'Tidak ada catatan pada rentang tersebut.');
    }
}
