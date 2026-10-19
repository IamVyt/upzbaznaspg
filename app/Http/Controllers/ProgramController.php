<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\KategoriDana;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = config('upz.program');
        $penyaluran = config('upz.penyaluran');

        $kategoriDana = KategoriDana::orderBy('tipe')->orderBy('nama')->get();

        $programStats = [];
        foreach ($programs as $prog) {
            $slug = $prog['slug'];
            $totalDana = Donasi::where('status', 'diterima')->where('program', $slug)->sum('jumlah');
            $countDonatur = Donasi::where('status', 'diterima')->where('program', $slug)->count();

            $programStats[] = [
                'program' => $prog,
                'paket' => $penyaluran[$slug]['paket'] ?? [],
                'kategori' => $penyaluran[$slug]['kategori'] ?? '-',
                'total_dana' => $totalDana,
                'count_donatur' => $countDonatur,
            ];
        }

        return view('program.index', compact('programStats', 'kategoriDana'));
    }
}
