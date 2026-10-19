<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataDonasiController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $program = $request->query('program');

        $programs = config('upz.program');

        $query = Donasi::where('status', 'diterima')->latest();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('kode', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('kontak', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            });
        }

        if ($program) {
            $query->where('program', $program);
        }

        $donasiList = $query->paginate(20)->withQueryString();
        $totalNominal = (clone $query)->sum('jumlah');
        $totalDonasi = (clone $query)->count();

        return view('data_donasi.index', compact(
            'donasiList',
            'programs',
            'q',
            'program',
            'totalNominal',
            'totalDonasi'
        ));
    }

    public function destroy($id)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Admin yang memiliki hak menghapus data donasi.');
        }

        $donasi = Donasi::findOrFail($id);
        $snapshot = $donasi->toArray();

        $donasi->delete();

        AuditLog::catat('donasi_hapus', 'donasi', $id, $snapshot, null);

        return back()->with('success', "Data donasi {$donasi->kode} berhasil dihapus dari sistem.");
    }

    public function destroyAll(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Admin yang memiliki hak menghapus data donasi.');
        }

        $filterProgram = $request->input('program');
        $query = Donasi::where('status', 'diterima');

        if ($filterProgram) {
            $query->where('program', $filterProgram);
        }

        $count = $query->count();
        $query->delete();

        AuditLog::catat('donasi_hapus_semua', 'donasi', null, null, [
            'jumlah_dihapus' => $count,
            'filter_program' => $filterProgram ?: 'semua',
        ]);

        return back()->with('success', "Sebanyak {$count} data donasi berhasil dihapus.");
    }
}
