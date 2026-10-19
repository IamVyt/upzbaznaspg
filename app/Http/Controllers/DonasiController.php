<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\RekeningBayar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DonasiController extends Controller
{
    public function create(Request $request)
    {
        $programs = config('upz.program');
        $penyaluran = config('upz.penyaluran');
        $programPilihan = $request->query('program', 'infak-dan-sedekah');

        $selectedProgram = null;
        foreach ($programs as $p) {
            if ($p['slug'] === $programPilihan) {
                $selectedProgram = $p;
                break;
            }
        }
        if (!$selectedProgram) {
            $selectedProgram = $programs[0];
            $programPilihan = $selectedProgram['slug'];
        }

        $paketList = $penyaluran[$programPilihan]['paket'] ?? [];
        $rekeningBank = RekeningBayar::bank()->aktif()->get();
        $rekeningEwallet = RekeningBayar::ewallet()->aktif()->get();

        $user = Auth::user();

        return view('donasi.salurkan', compact(
            'programs',
            'penyaluran',
            'programPilihan',
            'selectedProgram',
            'paketList',
            'rekeningBank',
            'rekeningEwallet',
            'user'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program' => ['required', 'string'],
            'paket' => ['required', 'string'],
            'jumlah' => ['required', 'numeric', 'min:10000', 'max:1000000000'],
            'metode' => ['required', 'in:transfer,ewallet,qris,tunai'],
            'channel' => ['nullable', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:150'],
            'kontak' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'nip' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'samarkan_nama' => ['nullable', 'boolean'],
            'tampil_beranda' => ['nullable', 'boolean'],
            'tampil_doa' => ['nullable', 'boolean'],
        ], [
            'program.required' => 'Program donasi wajib dipilih.',
            'paket.required' => 'Paket penyaluran wajib dipilih.',
            'jumlah.required' => 'Nominal donasi wajib diisi.',
            'jumlah.min' => 'Nominal donasi minimal Rp 10.000.',
            'jumlah.max' => 'Nominal donasi maksimal Rp 1.000.000.000.',
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'nama.required' => 'Nama lengkap wajib diisi.',
            'kontak.required' => 'Nomor WhatsApp / telepon wajib diisi.',
        ]);

        $programs = config('upz.program');
        $penyaluran = config('upz.penyaluran');

        $programNama = 'Donasi';
        foreach ($programs as $p) {
            if ($p['slug'] === $validated['program']) {
                $programNama = $p['nama'];
                break;
            }
        }

        $kategoriKode = $penyaluran[$validated['program']]['kategori'] ?? 'INFAK';

        // Resolusi channel bank/ewallet
        $channelNama = null;
        $channelInfo = null;
        if (!empty($validated['channel'])) {
            $rek = RekeningBayar::where('kode', $validated['channel'])->first();
            if ($rek) {
                $channelNama = $rek->nama;
                $channelInfo = $rek->nomor . ' a.n ' . $rek->atas_nama;
            } else {
                $channelNama = strtoupper($validated['channel']);
            }
        } elseif ($validated['metode'] === 'qris') {
            $channelNama = 'QRIS UPZ BAZNAS';
            $channelInfo = 'Scan QRIS Nasional';
        } elseif ($validated['metode'] === 'tunai') {
            $channelNama = 'Tunai di Kantor';
            $channelInfo = config('upz.site.alamat');
        }

        // Generate unik kode donasi: UPZ-YYYYMMDD-XXXX
        $tglStr = now()->format('Ymd');
        $randomStr = strtoupper(Str::random(4));
        $kode = "UPZ-{$tglStr}-{$randomStr}";

        $donasi = Donasi::create([
            'kode' => $kode,
            'user_id' => Auth::id(),
            'program' => $validated['program'],
            'program_nama' => $programNama,
            'paket' => $validated['paket'],
            'kategori_kode' => $kategoriKode,
            'nama' => $validated['nama'],
            'samarkan_nama' => $request->boolean('samarkan_nama'),
            'nip' => $validated['nip'],
            'kontak' => $validated['kontak'],
            'email' => $validated['email'],
            'jumlah' => $validated['jumlah'],
            'metode' => $validated['metode'],
            'channel' => $validated['channel'] ?? null,
            'channel_nama' => $channelNama,
            'channel_info' => $channelInfo,
            'catatan' => $validated['catatan'],
            'status' => 'menunggu',
            'tampil_beranda' => $request->boolean('tampil_beranda'),
            'tampil_doa' => $request->boolean('tampil_doa'),
        ]);

        AuditLog::catat('donasi_kirim', 'donasi', $donasi->id, null, [
            'kode' => $donasi->kode,
            'program_nama' => $donasi->program_nama,
            'jumlah' => $donasi->jumlah,
            'metode' => $donasi->metode,
        ]);

        return redirect()->route('donasi.sukses', $donasi->kode)
            ->with('success', 'Formulir donasi berhasil diajukan! Silakan selesaikan pembayaran.');
    }

    public function sukses($kode)
    {
        $donasi = Donasi::where('kode', $kode)->firstOrFail();
        return view('donasi.sukses', compact('donasi'));
    }

    public function bayar($kode)
    {
        $donasi = Donasi::where('kode', $kode)->firstOrFail();
        $rekeningBank = RekeningBayar::bank()->aktif()->get();
        $rekeningEwallet = RekeningBayar::ewallet()->aktif()->get();

        return view('donasi.bayar', compact('donasi', 'rekeningBank', 'rekeningEwallet'));
    }

    public function uploadBukti(Request $request, $kode)
    {
        $donasi = Donasi::where('kode', $kode)->firstOrFail();

        $request->validate([
            'bukti' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ], [
            'bukti.required' => 'Berkas bukti pembayaran wajib diunggah.',
            'bukti.image' => 'Berkas harus berupa gambar (JPG, PNG, atau WebP).',
            'bukti.max' => 'Ukuran berkas maksimal 4 MB.',
        ]);

        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $fileName = 'bukti_' . $donasi->kode . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('bukti', $fileName, 'public');

            $dataLama = $donasi->only(['status', 'dibayar_at', 'bukti']);

            $donasi->update([
                'status' => 'dibayar',
                'dibayar_at' => now(),
                'bukti' => $path,
            ]);

            AuditLog::catat('donasi_lapor_bayar', 'donasi', $donasi->id, $dataLama, [
                'status' => 'dibayar',
                'dibayar_at' => now(),
                'bukti' => $path,
            ]);
        }

        return redirect()->route('donasi.bayar', $donasi->kode)
            ->with('success', 'Bukti pembayaran berhasil diunggah! Petugas kami akan segera memverifikasi donasi Anda.');
    }

    public function batal(Request $request, $kode)
    {
        $donasi = Donasi::where('kode', $kode)->firstOrFail();

        // Hanya boleh dibatalkan jika masih berstatus menunggu atau dibayar
        if (!in_array($donasi->status, ['menunggu', 'dibayar'], true)) {
            return back()->with('error', 'Donasi ini tidak dapat dibatalkan.');
        }

        // Cek otorisasi
        if (Auth::id() && $donasi->user_id && $donasi->user_id !== Auth::id() && !Auth::user()->isStaff()) {
            abort(403);
        }

        $donasi->update([
            'status' => 'dibatalkan',
            'dibatalkan_at' => now(),
        ]);

        AuditLog::catat('donasi_batal', 'donasi', $donasi->id, null, ['kode' => $donasi->kode]);

        return redirect()->route('home')->with('info', 'Donasi ' . $donasi->kode . ' telah dibatalkan.');
    }

    public function cetak($kode)
    {
        $donasi = Donasi::where('kode', $kode)->firstOrFail();
        return view('donasi.cetak', compact('donasi'));
    }

    public function riwayat()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $donasiSaya = Donasi::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('donasi.saya', compact('donasiSaya'));
    }

    public function showBukti($kode)
    {
        $donasi = Donasi::where('kode', $kode)->firstOrFail();

        // Cek izin akses: pemilik atau staf
        if (!Auth::check()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (!Auth::user()->isStaff() && Auth::id() !== $donasi->user_id) {
            abort(403, 'Anda tidak memiliki hak untuk melihat bukti ini.');
        }

        if (!$donasi->bukti || !Storage::disk('public')->exists($donasi->bukti)) {
            abort(404, 'Berkas bukti pembayaran tidak ditemukan.');
        }

        return response()->file(Storage::disk('public')->path($donasi->bukti));
    }
}
