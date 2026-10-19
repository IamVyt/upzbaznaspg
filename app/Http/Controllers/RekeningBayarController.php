<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RekeningBayar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekeningBayarController extends Controller
{
    public function index()
    {
        $bankList = RekeningBayar::bank()->orderBy('urutan')->get();
        $ewalletList = RekeningBayar::ewallet()->orderBy('urutan')->get();

        return view('pembayaran.index', compact('bankList', 'ewalletList'));
    }

    public function update(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Administrator yang dapat mengubah metode pembayaran.');
        }

        $items = $request->input('rekening', []);

        foreach ($items as $id => $data) {
            $rek = RekeningBayar::find($id);
            if ($rek) {
                $lama = $rek->toArray();
                $rek->update([
                    'nomor' => $data['nomor'] ?? '',
                    'atas_nama' => $data['atas_nama'] ?? '',
                    'aktif' => isset($data['aktif']),
                ]);
            }
        }

        AuditLog::catat('rekening_ubah', 'rekening_bayar', null, null, ['status' => 'diperbarui']);

        return back()->with('success', 'Pengaturan rekening pembayaran dan e-wallet berhasil disimpan.');
    }
}
