<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RekeningBayar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RekeningBayarController extends Controller
{
    private function getQrisPath(): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = public_path("img/qris.$ext");
            if (file_exists($path)) {
                return "img/qris.$ext";
            }
        }
        return null;
    }

    public function index()
    {
        $daftar = RekeningBayar::orderBy('jenis')->orderBy('urutan')->orderBy('nama')->get();
        $boleh  = Auth::user()->isAdmin();
        $qris   = $this->getQrisPath();

        $siap = [
            'transfer' => RekeningBayar::where('jenis', 'bank')->where('aktif', true)->where('nomor', '<>', '')->exists(),
            'ewallet'  => RekeningBayar::where('jenis', 'ewallet')->where('aktif', true)->where('nomor', '<>', '')->exists(),
            'qris'     => $qris !== null,
            'tunai'    => true,
        ];

        return view('pembayaran.index', compact('daftar', 'boleh', 'qris', 'siap'));
    }

    public function proses(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('danger', 'Hanya admin yang boleh mengubah metode pembayaran.');
        }

        $aksi = (string)$request->input('aksi');

        if ($aksi === 'qris_unggah') {
            $request->validate([
                'qris' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            ], [
                'qris.required' => 'Pilih berkas gambar QRIS terlebih dahulu.',
                'qris.image'    => 'Berkas harus berupa gambar valid (JPG, PNG, atau WebP).',
                'qris.max'      => 'Ukuran berkas QRIS maksimal 2 MB.',
            ]);

            // Hapus gambar QRIS yang lama jika ada
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $p = public_path("img/qris.$ext");
                if (file_exists($p)) {
                    @unlink($p);
                }
            }

            $file = $request->file('qris');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $file->move(public_path('img'), "qris.$ext");

            AuditLog::catat('qris_unggah', 'pengaturan', null, null, ['qris' => 'diperbarui']);
            return back()->with('success', 'Gambar QRIS diperbarui. Metode QRIS sekarang bisa dipilih donatur.');
        }

        if ($aksi === 'qris_hapus') {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $p = public_path("img/qris.$ext");
                if (file_exists($p)) {
                    @unlink($p);
                }
            }

            AuditLog::catat('qris_hapus', 'pengaturan', null, null, ['qris' => 'dihapus']);
            return back()->with('success', 'Gambar QRIS dihapus. Metode QRIS tidak lagi bisa dipilih donatur.');
        }

        if ($aksi === 'simpan') {
            $id     = (int)$request->input('id', 0);
            $jenis  = (string)$request->input('jenis');
            $nama   = trim((string)$request->input('nama'));
            $nomor  = trim((string)$request->input('nomor'));
            $an     = trim((string)$request->input('atas_nama'));
            $aktif  = $request->boolean('aktif');

            $galat = [];
            if ($id === 0 && !in_array($jenis, ['bank', 'ewallet'], true)) {
                $galat[] = 'Pilih jenis bank atau e-wallet.';
            }
            if (mb_strlen($nama) < 2 || mb_strlen($nama) > 60) {
                $galat[] = 'Nama harus 2 sampai 60 karakter.';
            }
            if ($nomor !== '' && !preg_match('/^[0-9][0-9 .\-]{4,38}[0-9]$/', $nomor)) {
                $galat[] = 'Nomor hanya boleh berisi angka, spasi, titik, dan strip (6 sampai 40 karakter).';
            }
            if ($aktif && $nomor === '') {
                $galat[] = 'Isi nomor terlebih dahulu sebelum mengaktifkan.';
            }
            if (mb_strlen($an) > 100) {
                $galat[] = 'Atas nama maksimal 100 karakter.';
            }

            if (!empty($galat)) {
                return back()->with('danger', implode(' ', $galat));
            }

            if ($id > 0) {
                $rek = RekeningBayar::find($id);
                if (!$rek) {
                    return back()->with('danger', 'Data rekening tidak ditemukan.');
                }
                $lama = $rek->only(['nama', 'nomor', 'aktif']);
                $rek->update([
                    'nama' => $nama,
                    'nomor' => $nomor,
                    'atas_nama' => $an,
                    'aktif' => $aktif,
                ]);
                AuditLog::catat('rekening_ubah', 'rekening_bayar', $id, $lama, ['nama' => $nama, 'nomor' => $nomor, 'aktif' => $aktif]);
                return back()->with('success', $nama . ' diperbarui. Perubahan berlaku untuk donasi baru.');
            } else {
                $kodeBaru = Str::limit(Str::slug($nama), 20, '');
                if ($kodeBaru === '' || RekeningBayar::where('jenis', $jenis)->where('kode', $kodeBaru)->exists()) {
                    return back()->with('danger', 'Nama tersebut sudah ada atau tidak valid. Gunakan nama lain.');
                }
                $urutan = ((int)RekeningBayar::where('jenis', $jenis)->max('urutan')) + 1;
                $rek = RekeningBayar::create([
                    'jenis' => $jenis,
                    'kode' => $kodeBaru,
                    'nama' => $nama,
                    'nomor' => $nomor,
                    'atas_nama' => $an,
                    'aktif' => $aktif,
                    'urutan' => $urutan,
                ]);
                AuditLog::catat('rekening_tambah', 'rekening_bayar', $rek->id, null, ['nama' => $nama, 'nomor' => $nomor, 'aktif' => $aktif]);
                return back()->with('success', $nama . ' ditambahkan.');
            }
        }

        if ($aksi === 'hapus') {
            $id = (int)$request->input('id');
            $rek = RekeningBayar::find($id);
            if ($rek) {
                $lama = $rek->only(['nama', 'nomor']);
                $rek->delete();
                AuditLog::catat('rekening_hapus', 'rekening_bayar', $id, $lama, null);
                return back()->with('success', $lama['nama'] . ' dihapus. Donasi yang sudah dibuat tetap menyimpan data pembayarannya.');
            }
            return back()->with('danger', 'Data tidak ditemukan.');
        }

        return back()->with('danger', 'Permintaan tidak valid.');
    }

    public function update(Request $request)
    {
        return $this->proses($request);
    }
}
