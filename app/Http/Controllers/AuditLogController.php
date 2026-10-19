<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $aksi = $request->query('aksi');
        $q = $request->query('q');

        $query = AuditLog::with('user')->latest('waktu');

        if ($aksi) {
            $query->where('aksi', $aksi);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('username', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%")
                    ->orWhere('tabel', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();
        $daftarAksi = AuditLog::select('aksi')->distinct()->pluck('aksi');

        return view('audit.index', compact('logs', 'daftarAksi', 'aksi', 'q'));
    }

    public function clear(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Administrator yang dapat membersihkan riwayat aktivitas.');
        }

        $rentang = $request->input('rentang', '1minggu');
        $query = AuditLog::query();

        if ($rentang === '1minggu') {
            $query->where('waktu', '<', now()->subWeek());
        } elseif ($rentang === '1bulan') {
            $query->where('waktu', '<', now()->subMonth());
        } elseif ($rentang === 'semua') {
            // hapus semua
        }

        $count = $query->count();
        $query->delete();

        AuditLog::catat('audit_hapus', 'audit_log', null, null, [
            'rentang' => $rentang,
            'jumlah_dihapus' => $count,
        ]);

        return back()->with('success', "Sebanyak {$count} catatan riwayat aktivitas berhasil dibersihkan.");
    }
}
