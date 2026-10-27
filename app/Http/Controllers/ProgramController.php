<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\KategoriDana;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = config('upz.program', []);
        $penyaluran = config('upz.penyaluran', []);

        $kat = [];
        foreach (KategoriDana::where('tipe', 'masuk')->where('aktif', true)->get() as $k) {
            $kat[$k->kode] = $k->nama;
        }

        $stat = [];
        $queryStat = Donasi::where('status', 'diterima')
            ->selectRaw('program, COUNT(*) as n, COALESCE(SUM(jumlah), 0) as total')
            ->groupBy('program')
            ->get();
        foreach ($queryStat as $r) {
            $stat[$r->program] = $r->toArray();
        }

        return view('program.index', compact('programs', 'penyaluran', 'kat', 'stat'));
    }
}
