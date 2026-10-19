<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use Illuminate\Http\Request;

class DataPenyaluranController extends Controller
{
    public function index(Request $request)
    {
        // Ringkasan per program (kunci: slug program)
        $ringkas = [];
        $queryRingkas = Donasi::selectRaw("
            program,
            MAX(program_nama) AS program_nama,
            SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) AS n,
            COALESCE(SUM(CASE WHEN status = 'diterima' THEN jumlah ELSE 0 END), 0) AS total,
            COUNT(DISTINCT CASE WHEN status = 'diterima' THEN COALESCE(user_id, nama) END) AS donatur,
            SUM(CASE WHEN status IN ('menunggu', 'dibayar') THEN 1 ELSE 0 END) AS proses,
            MAX(CASE WHEN status = 'diterima' THEN COALESCE(diproses_at, dibuat_at) END) AS terakhir
        ")->groupBy('program')->get();

        foreach ($queryRingkas as $r) {
            $ringkas[$r->program] = $r->toArray();
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
            ->orderByRaw('COALESCE(diproses_at, dibuat_at) DESC')
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
            ->distinct()
            ->selectRaw("COALESCE(CONCAT('u', user_id), CONCAT('n', nama)) as d_id")
            ->count('d_id');

        return view('data_penyaluran.index', compact(
            'baris', 'riwayat', 'totN', 'totRp', 'totProses', 'totDonatur'
        ));
    }
}
