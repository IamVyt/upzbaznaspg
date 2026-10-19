<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $site = config('upz.site');
        $programs = config('upz.program');
        $mitra = config('upz.mitra');
        $dampak = config('upz.dampak');
        $alur = config('upz.alur');
        $tim = config('upz.tim');

        // Ambil donatur terbaru yang statusnya diterima dan opt-in tampil_beranda (dengan fallback aman jika DB sedang proses)
        try {
            $donaturTerbaru = Donasi::where('status', 'diterima')
                ->where('tampil_beranda', true)
                ->latest('diproses_at')
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB belum terhubung untuk widget donatur beranda: ' . $e->getMessage());
            $donaturTerbaru = collect();
        }

        $testimoniStatis = config('upz.testimoni_statis');

        return view('home', compact(
            'site',
            'programs',
            'mitra',
            'dampak',
            'alur',
            'tim',
            'donaturTerbaru',
            'testimoniStatis'
        ));
    }
}
