<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use Illuminate\Http\Request;

class DataPenyaluranController extends Controller
{
    public function index(Request $request)
    {
        $programs = config('upz.program');

        // Rekapitulasi per program
        $rekapProgram = [];
        foreach ($programs as $prog) {
            $slug = $prog['slug'];
            $totalDonasi = Donasi::where('status', 'diterima')->where('program', $slug)->sum('jumlah');
            $jumlahDonatur = Donasi::where('status', 'diterima')->where('program', $slug)->count();

            $rekapProgram[] = [
                'nama' => $prog['nama'],
                'slug' => $slug,
                'warna' => $prog['warna'],
                'ringkas' => $prog['ringkas'],
                'total_donasi' => $totalDonasi,
                'jumlah_donatur' => $jumlahDonatur,
            ];
        }

        // Riwayat donasi tersalurkan terbaru
        $riwayatPenyaluran = Donasi::where('status', 'diterima')
            ->latest('diproses_at')
            ->paginate(15);

        return view('data_penyaluran.index', compact('rekapProgram', 'riwayatPenyaluran'));
    }
}
