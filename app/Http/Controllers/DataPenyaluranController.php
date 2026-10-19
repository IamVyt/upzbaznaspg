<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use Illuminate\Http\Request;

class DataPenyaluranController extends Controller
{
    public function index(Request $request)
    {
        // Ambil semua donasi untuk dihitung per program secara agnostik database (kompatibel MySQL, Postgres, SQLite)
        $semuaDonasi = Donasi::all();

        $ringkas = [];
        foreach ($semuaDonasi->groupBy('program') as $progSlug => $items) {
            $diterima = $items->where('status', 'diterima');
            $ringkas[$progSlug] = [
                'program'      => $progSlug,
                'program_nama' => $items->first()->program_nama ?? $progSlug,
                'n'            => $diterima->count(),
                'total'        => (float)$diterima->sum('jumlah'),
                'donatur'      => $diterima->map(fn($d) => $d->user_id ? 'u' . $d->user_id : 'n' . $d->nama)->unique()->count(),
                'proses'       => $items->whereIn('status', ['menunggu', 'dibayar'])->count(),
                'terakhir'     => $diterima->max(fn($d) => $d->diproses_at ?? $d->created_at),
            ];
        }

        $programs = config('upz.program', []);
        $baris = [];
        $dikenal = [];

        foreach ($programs as $p) {
            $s = $p['slug'];
            $dikenal[$s] = true;
            $baris[] = array_merge([
                'slug' => $s,
                'nama' => $p['nama'],
                'ket'  => $p['ringkas'] ?? '',
            ], $ringkas[$s] ?? []);
        }

        foreach ($ringkas as $s => $r) {
            if (!isset($dikenal[$s])) {
                $baris[] = array_merge([
                    'slug' => $s,
                    'nama' => $r['program_nama'] ?? $s,
                    'ket'  => 'Program ini sudah tidak ada di daftar program situs.',
                ], $r);
            }
        }

        // Riwayat donatur per program (50 terbaru tiap program)
        $riwayatRaw = Donasi::with('user')
            ->where('status', 'diterima')
            ->orderByRaw('COALESCE(diproses_at, created_at) DESC')
            ->orderBy('id', 'desc')
            ->take(1000)
            ->get();

        $riwayat = [];
        foreach ($riwayatRaw as $d) {
            $slug = $d->program;
            if (!isset($riwayat[$slug])) {
                $riwayat[$slug] = [];
            }
            if (count($riwayat[$slug]) < 50) {
                $riwayat[$slug][] = $d;
            }
        }

        $totN = 0;
        $totRp = 0;
        $totProses = 0;
        foreach ($ringkas as $r) {
            $totN += (int)($r['n'] ?? 0);
            $totRp += (float)($r['total'] ?? 0);
            $totProses += (int)($r['proses'] ?? 0);
        }
        $totDonatur = Donasi::where('status', 'diterima')
            ->get(['user_id', 'nama'])
            ->map(fn($d) => $d->user_id ? 'u' . $d->user_id : 'n' . $d->nama)
            ->unique()
            ->count();

        return view('data_penyaluran.index', compact(
            'baris', 'riwayat', 'totN', 'totRp', 'totProses', 'totDonatur'
        ));
    }
}
