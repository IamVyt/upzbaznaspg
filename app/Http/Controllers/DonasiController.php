<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\RekeningBayar;
use App\Support\BuktiDonasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonasiController extends Controller
{
    /**
     * Langkah 1: Pilih program dan paket donasi (salurkan.php)
     */
    public function salurkan(Request $request)
    {
        $programs = config('upz.program', []);
        $penyaluran = config('upz.penyaluran', []);

        // Hanya program yang punya paket di $penyaluran yang bisa dipilih
        $tersedia = [];
        foreach ($programs as $p) {
            $s = slug($p['nama']);
            if (isset($penyaluran[$s])) {
                $tersedia[$s] = $p;
            }
        }

        $slug = $request->query('program', '');
        $slug = is_string($slug) ? $slug : '';
        $prog = $tersedia[$slug] ?? null;

        return view('donasi.salurkan', compact('tersedia', 'slug', 'prog', 'penyaluran'));
    }

    /**
     * Langkah 2: Formulir pengisian data donatur dan pembayaran (bayar.php)
     */
    public function bayarForm(Request $request)
    {
        $slug = (string) $request->query('program', '');
        $idx = (int) $request->query('paket', 0);

        if (! Auth::check()) {
            session(['tujuan' => $request->fullUrl()]);

            return redirect()->route('login')->with('info', 'Silakan masuk atau buat akun baru terlebih dahulu untuk melanjutkan penyaluran donasi.');
        }

        $programs = config('upz.program', []);
        $penyaluran = config('upz.penyaluran', []);

        $prog = null;
        foreach ($programs as $p) {
            if (slug($p['nama']) === $slug) {
                $prog = $p;
                break;
            }
        }

        if (! $prog || ! isset($penyaluran[$slug]['paket'][$idx])) {
            return redirect()->route('donasi.create');
        }

        $infoPaket = $penyaluran[$slug];
        $pk = $infoPaket['paket'][$idx];
        $namaPaket = $pk['nama'];
        $nominalTetap = (float) ($pk['nominal'] ?? 0);
        $bebas = ($nominalTetap <= 0);
        $deskPaket = $pk['ket'] ?? '';
        $opsiPaket = ! empty($pk['hitung']) ? 'hitung' : '';

        $minimal = 10000;
        $maksimal = 1000000000;

        $user = Auth::user();
        $isi = [
            'nama' => old('nama', $user->name ?? ''),
            'kontak' => old('kontak', $user->no_hp ?? ''),
            'nip' => old('nip', $user->nip ?? ''),
            'email' => old('email', $user->email ?? ''),
            'jumlah' => old('jumlah', $bebas ? '' : $nominalTetap),
            'metode' => old('metode', 'transfer'),
            'catatan' => old('catatan', ''),
            'tampil_nama' => old('tampil_nama', 'lengkap'),
            'tampil_beranda' => old('tampil_beranda', '0'),
            'tampil_doa' => old('tampil_doa', '0'),
        ];

        // Daftar channel rekening & ewallet yang aktif
        $bank = RekeningBayar::where('jenis', 'bank')->where('aktif', true)->where('nomor', '<>', '')->orderBy('urutan')->orderBy('nama')->get()->keyBy('kode')->toArray();
        $ewallet = RekeningBayar::where('jenis', 'ewallet')->where('aktif', true)->where('nomor', '<>', '')->orderBy('urutan')->orderBy('nama')->get()->keyBy('kode')->toArray();

        $qris = null;
        foreach (['png', 'jpg', 'webp'] as $ext) {
            if (file_exists(public_path("img/qris.$ext"))) {
                $qris = "img/qris.$ext";
                break;
            }
        }

        $metode = config('upz.metode_pembayaran', [
            'transfer' => ['Transfer bank', 'Pilih bank tujuan transfer'],
            'ewallet' => ['E-wallet', 'GoPay, OVO, DANA, ShopeePay, dan lainnya'],
            'qris' => ['QRIS', 'Pindai dengan aplikasi bank atau e-wallet'],
            'tunai' => ['Tunai di kantor UPZ', 'Serahkan langsung kepada pengurus'],
        ]);

        $ada = [
            'transfer' => ! empty($bank),
            'ewallet' => ! empty($ewallet),
            'qris' => ($qris !== null),
            'tunai' => true,
        ];

        // Pastikan metode awal tersedia
        if (empty($ada[$isi['metode']])) {
            foreach ($ada as $km => $tersedia) {
                if ($tersedia) {
                    $isi['metode'] = $km;
                    break;
                }
            }
        }

        $pilihMetode = $isi['metode'];
        $pilihChannel = old('channel_'.$pilihMetode, array_key_first($pilihMetode === 'transfer' ? $bank : $ewallet) ?? '');

        return view('donasi.bayar', compact(
            'slug',
            'idx',
            'prog',
            'namaPaket',
            'nominalTetap',
            'bebas',
            'deskPaket',
            'opsiPaket',
            'minimal',
            'maksimal',
            'isi',
            'bank',
            'ewallet',
            'qris',
            'metode',
            'ada',
            'pilihMetode',
            'pilihChannel'
        ));
    }

    /**
     * Proses kirim formulir donasi (bayar.php POST)
     */
    public function prosesBayar(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $slug = (string) $request->input('program', '');
        $idx = (int) $request->input('paket', 0);

        $programs = config('upz.program', []);
        $penyaluran = config('upz.penyaluran', []);

        $prog = null;
        foreach ($programs as $p) {
            if (slug($p['nama']) === $slug) {
                $prog = $p;
                break;
            }
        }

        if (! $prog || ! isset($penyaluran[$slug]['paket'][$idx])) {
            return redirect()->route('donasi.create')->with('danger', 'Program atau paket tidak valid.');
        }

        $info = $penyaluran[$slug];
        $pk = $info['paket'][$idx];
        $namaPaket = $pk['nama'];
        $nominalTetap = (float) ($pk['nominal'] ?? 0);
        $bebas = ($nominalTetap <= 0);

        $minimal = 10000;
        $maksimal = 1000000000;

        $rules = [
            'nama' => ['required', 'string', 'min:2', 'max:150'],
            'kontak' => ['required', 'string', 'min:6', 'max:20'],
            'nip' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'metode' => ['required', 'in:transfer,ewallet,qris,tunai'],
            'tampil_nama' => ['required', 'in:lengkap,singkat,anonim'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'tampil_beranda' => ['nullable', 'in:1'],
            'tampil_doa' => ['nullable', 'in:1'],
        ];

        if ($bebas) {
            $rules['jumlah'] = ['required', 'numeric', 'min:'.$minimal, 'max:'.$maksimal];
        }

        $validated = $request->validate($rules, [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'kontak.required' => 'Nomor WhatsApp atau HP wajib diisi.',
            'jumlah.min' => 'Nominal donasi minimal Rp '.number_format($minimal, 0, ',', '.').'.',
            'jumlah.max' => 'Nominal donasi maksimal Rp '.number_format($maksimal, 0, ',', '.').'.',
        ]);

        $jumlah = $bebas ? (float) $validated['jumlah'] : $nominalTetap;
        $metodeKode = $validated['metode'];

        // Resolusi channel bank/ewallet
        $kanal = null;
        $kanalNama = null;
        $kanalInfo = null;

        if (in_array($metodeKode, ['transfer', 'ewallet'], true)) {
            $jenis = ($metodeKode === 'transfer') ? 'bank' : 'ewallet';
            $pilihanKanal = (string) $request->input('channel_'.$metodeKode, '');
            $rec = RekeningBayar::where('jenis', $jenis)->where('aktif', true)->where('kode', $pilihanKanal)->first();

            if (! $rec) {
                return back()->withInput()->with('danger', 'Silakan pilih '.($jenis === 'bank' ? 'bank tujuan' : 'e-wallet').' untuk pembayaran Anda.');
            }
            $kanal = $rec->kode;
            $kanalNama = $rec->nama;
            $kanalInfo = $rec->nomor.($rec->atas_nama !== '' ? ' a.n. '.$rec->atas_nama : '');
        } elseif ($metodeKode === 'qris') {
            $kanalNama = 'QRIS UPZ BAZNAS';
        } else {
            $kanalNama = 'Tunai di kantor UPZ';
            $kanalInfo = config('upz.site.alamat');
        }

        // Generate unik kode: UPZ-YYMMDD-XXXXXX
        $kode = null;
        for ($coba = 0; $coba < 5; $coba++) {
            $calon = 'UPZ-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
            if (! Donasi::where('kode', $calon)->exists()) {
                $kode = $calon;
                break;
            }
        }

        if (! $kode) {
            return back()->withInput()->with('danger', 'Donasi belum tersimpan. Silakan coba lagi.');
        }

        $tampilBeranda = ! empty($validated['tampil_beranda']) ? 1 : 0;
        $tampilDoa = (! empty($validated['tampil_doa']) && ! empty($validated['catatan'])) ? 1 : 0;

        $donasi = Donasi::create([
            'kode' => $kode,
            'user_id' => Auth::id(),
            'program' => $slug,
            'program_nama' => $prog['nama'],
            'paket' => $namaPaket,
            'kategori_kode' => $info['kategori'] ?? 'INFAK',
            'nama' => $validated['nama'],
            'samarkan_nama' => ($validated['tampil_nama'] === 'anonim'),
            'nama_singkat' => ($validated['tampil_nama'] === 'singkat'),
            'nip' => ! empty($validated['nip']) ? $validated['nip'] : null,
            'kontak' => $validated['kontak'],
            'email' => ! empty($validated['email']) ? $validated['email'] : null,
            'jumlah' => $jumlah,
            'metode' => $metodeKode,
            'channel' => $kanal,
            'channel_nama' => $kanalNama,
            'channel_info' => $kanalInfo,
            'catatan' => ! empty($validated['catatan']) ? $validated['catatan'] : null,
            'status' => 'menunggu',
            'tampil_beranda' => $tampilBeranda,
            'tampil_doa' => $tampilDoa,
        ]);

        AuditLog::catat('donasi_kirim', 'donasi', $donasi->id, null, [
            'kode' => $kode,
            'program' => $prog['nama'],
            'paket' => $namaPaket,
            'jumlah' => $jumlah,
            'metode' => $metodeKode,
            'channel' => $kanal,
        ]);

        return redirect()->route('donasi.sukses', ['kode' => $donasi->kode, 'baru' => 1]);
    }

    /**
     * Alias lama /donasi/bayar/{kode} -> halaman detail donasi.
     */
    public function bayarRedirect($kode)
    {
        return redirect()->route('donasi.sukses', $kode);
    }

    /**
     * Langkah 3: Detail, petunjuk pembayaran, dan konfirmasi (donasi_sukses.php)
     */
    public function sukses(Request $request, $kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();
        $userId = Auth::id() ?: 0;
        $milik = ($userId > 0 && (int) $d->user_id === $userId);
        $st = $d->status;

        $baru = $request->query('baru') === '1';

        $judul = 'Donasi Anda tercatat';
        $kalimat = 'Terima kasih atas kebaikan Anda. Silakan ikuti petunjuk pembayaran di bawah untuk menyelesaikan donasi.';

        if (! $baru) {
            if ($st === 'diterima') {
                $judul = 'Donasi sudah tersalurkan';
                $kalimat = 'Alhamdulillah, donasi Anda telah diverifikasi dan masuk dalam pembukuan kas UPZ BAZNAS Petrokimia Gresik.';
            } elseif ($st === 'dibayar') {
                $judul = 'Pembayaran sedang dicek';
                $kalimat = 'Terima kasih. Laporan pembayaran Anda sudah kami terima dan sedang diverifikasi oleh pengurus.';
            } elseif ($st === 'ditolak') {
                $judul = 'Donasi ditolak';
                $kalimat = 'Donasi ini tidak dapat diproses oleh pengurus.';
            } elseif ($st === 'dibatalkan') {
                $judul = 'Donasi dibatalkan';
                $kalimat = 'Donasi ini telah dibatalkan.';
            }
        }

        $tahap = match ($st) {
            'menunggu' => ['done', 'on', ''],
            'dibayar' => ['done', 'done', 'on'],
            'diterima' => ['done', 'done', 'done'],
            default => null,
        };

        [$labelStatus, $kelasStatus] = label_status_donasi($st);

        $metodeList = [
            'transfer' => ['Transfer bank', 'Pilih bank tujuan transfer', [
                'Transfer sesuai nominal ke rekening bank pilihan Anda yang tertera di atas.',
                'Tuliskan nomor referensi donasi pada berita transfer.',
                'Setelah transfer, tekan Saya sudah membayar dan unggah bukti transfer.',
            ]],
            'ewallet' => ['E-wallet', 'GoPay, OVO, DANA, ShopeePay, dan lainnya', [
                'Buka aplikasi e-wallet pilihan Anda, lalu kirim sesuai nominal ke nomor yang tertera di atas.',
                'Cantumkan nomor referensi donasi pada catatan pembayaran bila tersedia.',
                'Setelah membayar, tekan Saya sudah membayar dan unggah tangkapan layar bukti pembayaran.',
            ]],
            'qris' => ['QRIS', 'Pindai dengan aplikasi bank atau e-wallet', [
                'Buka aplikasi bank atau e-wallet Anda, lalu pindai kode QRIS di atas.',
                'Masukkan nominal sesuai donasi dan selesaikan pembayaran.',
                'Setelah membayar, tekan Saya sudah membayar dan unggah bukti pembayaran.',
            ]],
            'tunai' => ['Tunai di kantor UPZ', 'Serahkan langsung kepada pengurus', [
                'Lokasi: '.config('upz.site.alamat'),
                'Jam layanan: Hari kerja 08.00 - 16.00 WIB',
                'Sebutkan nomor referensi donasi kepada pengurus saat menyerahkan dana, lalu tekan Saya sudah membayar.',
            ]],
        ];

        $m = $metodeList[$d->metode] ?? [$d->metode, '', []];

        $qris = null;
        foreach (['png', 'jpg', 'webp'] as $ext) {
            if (file_exists(public_path("img/qris.$ext"))) {
                $qris = "img/qris.$ext";
                break;
            }
        }

        $nomor = '';
        $atasNama = '';
        if ($d->channel_info) {
            $parts = explode(' a.n. ', (string) $d->channel_info, 2);
            $nomor = $parts[0];
            $atasNama = $parts[1] ?? '';
        }

        $bank = RekeningBayar::where('jenis', 'bank')->where('aktif', true)->where('nomor', '<>', '')->orderBy('urutan')->orderBy('nama')->get()->keyBy('kode')->toArray();
        $ewallet = RekeningBayar::where('jenis', 'ewallet')->where('aktif', true)->where('nomor', '<>', '')->orderBy('urutan')->orderBy('nama')->get()->keyBy('kode')->toArray();

        $ada = [
            'transfer' => ! empty($bank),
            'ewallet' => ! empty($ewallet),
            'qris' => ($qris !== null),
            'tunai' => true,
        ];

        $pilihMetode = $d->metode;
        $pilihChannel = $d->channel;

        $adaKiri = ($st === 'menunggu') || ($d->bukti && ($milik || Auth::user()?->isStaff()));

        return view('donasi.sukses', compact(
            'd',
            'milik',
            'st',
            'judul',
            'kalimat',
            'tahap',
            'labelStatus',
            'kelasStatus',
            'm',
            'nomor',
            'atasNama',
            'qris',
            'metodeList',
            'bank',
            'ewallet',
            'ada',
            'pilihMetode',
            'pilihChannel',
            'adaKiri'
        ));
    }

    /**
     * Donatur mengunggah bukti pembayaran (lapor_bayar)
     */
    public function uploadBukti(Request $request, $kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();
        if (Auth::id() !== $d->user_id && ! Auth::user()?->isStaff()) {
            abort(403);
        }

        if (! in_array($d->status, ['menunggu', 'dibayar'], true)) {
            return back()->with('danger', 'Donasi ini sudah diproses, pembayaran tidak bisa dilaporkan lagi.');
        }

        $request->validate([
            'bukti' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'bukti.image' => 'Berkas harus berupa gambar JPG, PNG, atau WebP.',
            'bukti.max' => 'Ukuran berkas gambar maksimal 2 MB.',
        ]);

        $path = $d->bukti;
        if ($request->hasFile('bukti')) {
            $path = BuktiDonasi::simpan($request->file('bukti'));
            BuktiDonasi::hapus($d->bukti);
        }

        $statusLama = $d->status;
        $d->update([
            'status' => 'dibayar',
            'dibayar_at' => $d->dibayar_at ?: now(),
            'bukti' => $path,
        ]);

        if ($statusLama === 'menunggu') {
            AuditLog::catat('donasi_lapor_bayar', 'donasi', $d->id, ['status' => 'menunggu'], [
                'status' => 'dibayar',
                'bukti' => $path ? 'ada' : 'tidak ada',
            ]);
            $pesan = 'Terima kasih. Pembayaran Anda sudah dilaporkan dan akan dicek pengurus. Anda bisa mencetak tanda terima sementara.';
        } else {
            $pesan = 'Bukti pembayaran diperbarui.';
        }

        return redirect()->route('donasi.sukses', $d->kode)->with('success', $pesan);
    }

    /**
     * Donatur mengganti metode pembayaran sebelum diverifikasi
     */
    public function gantiMetode(Request $request, $kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();
        if (Auth::id() !== $d->user_id && ! Auth::user()?->isStaff()) {
            abort(403);
        }

        if ($d->status !== 'menunggu') {
            return back()->with('danger', 'Metode tidak bisa diganti karena pembayaran sudah dilaporkan atau donasi sudah diproses.');
        }

        $m = (string) $request->input('metode', '');
        $c = (string) $request->input('channel_'.$m, '');

        $kanal = null;
        $kanalNama = null;
        $kanalInfo = null;

        if (in_array($m, ['transfer', 'ewallet'], true)) {
            $jenis = ($m === 'transfer') ? 'bank' : 'ewallet';
            $rec = RekeningBayar::where('jenis', $jenis)->where('aktif', true)->where('kode', $c)->first();
            if (! $rec) {
                return back()->with('danger', 'Silakan pilih '.($jenis === 'bank' ? 'bank tujuan' : 'e-wallet').' untuk pembayaran Anda.');
            }
            $kanal = $rec->kode;
            $kanalNama = $rec->nama;
            $kanalInfo = $rec->nomor.($rec->atas_nama !== '' ? ' a.n. '.$rec->atas_nama : '');
        } elseif ($m === 'qris') {
            $kanalNama = 'QRIS UPZ BAZNAS';
        } elseif ($m === 'tunai') {
            $kanalNama = 'Tunai di kantor UPZ';
            $kanalInfo = config('upz.site.alamat');
        } else {
            return back()->with('danger', 'Metode pembayaran tidak valid.');
        }

        $lama = ['metode' => $d->metode, 'channel' => $d->channel];
        $d->update([
            'metode' => $m,
            'channel' => $kanal,
            'channel_nama' => $kanalNama,
            'channel_info' => $kanalInfo,
        ]);

        AuditLog::catat('donasi_ganti_metode', 'donasi', $d->id, $lama, [
            'metode' => $m,
            'channel' => $kanal,
        ]);

        return redirect()->route('donasi.sukses', $d->kode)
            ->with('success', 'Metode pembayaran diganti ke '.$kanalNama.'. Silakan selesaikan pembayaran sesuai petunjuk di bawah.');
    }

    /**
     * Donatur mengatur opsi tampil nama & doa di beranda
     */
    public function ubahTampilan(Request $request, $kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();
        if (Auth::id() !== $d->user_id && ! Auth::user()?->isStaff()) {
            abort(403);
        }

        if (! in_array($d->status, ['menunggu', 'dibayar', 'diterima'], true)) {
            return back()->with('danger', 'Pengaturan tampilan hanya tersedia untuk donasi yang masih berjalan atau sudah tersalurkan.');
        }

        $tn = $request->input('tampil_nama', 'lengkap');
        $tn = in_array($tn, ['lengkap', 'singkat', 'anonim'], true) ? $tn : 'lengkap';
        $beranda = $request->has('tampil_beranda') ? 1 : 0;
        $doa = ($beranda && $request->has('tampil_doa') && trim((string) $d->catatan) !== '') ? 1 : 0;

        $namaLama = $d->samarkan_nama ? 'anonim' : ($d->nama_singkat ? 'singkat' : 'lengkap');
        $d->update([
            'samarkan_nama' => ($tn === 'anonim'),
            'nama_singkat' => ($tn === 'singkat'),
            'tampil_beranda' => $beranda,
            'tampil_doa' => $doa,
        ]);

        AuditLog::catat('donasi_ubah_tampilan', 'donasi', $d->id, [
            'nama' => $namaLama,
            'beranda' => (int) $d->tampil_beranda,
            'doa' => (int) $d->tampil_doa,
        ], [
            'nama' => $tn,
            'beranda' => $beranda,
            'doa' => $doa,
        ]);

        $pesan = $beranda
            ? ($d->status === 'diterima'
                ? 'Pengaturan disimpan. Donasi Anda tampil di beranda sesuai pilihan nama.'
                : 'Pengaturan disimpan. Donasi Anda akan tampil di beranda setelah dikonfirmasi pengurus.')
            : 'Pengaturan disimpan. Donasi Anda tidak ditampilkan di beranda.';

        return redirect()->route('donasi.sukses', $d->kode)->with('success', $pesan);
    }

    /**
     * Batalkan donasi (hanya jika masih menunggu)
     */
    public function batal(Request $request, $kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();
        if (Auth::id() !== $d->user_id && ! Auth::user()?->isStaff()) {
            abort(403);
        }

        if ($d->status !== 'menunggu') {
            return back()->with('danger', 'Donasi yang sudah dibayar tidak bisa dibatalkan sendiri. Hubungi pengurus UPZ.');
        }

        $d->update([
            'status' => 'dibatalkan',
            'dibatalkan_at' => now(),
        ]);

        AuditLog::catat('donasi_batal', 'donasi', $d->id, ['status' => 'menunggu'], ['status' => 'dibatalkan']);

        return redirect()->route('donasi.sukses', $d->kode)
            ->with('success', 'Donasi dibatalkan. Anda bisa membuat donasi baru kapan saja.');
    }

    /**
     * Cetak bukti penyaluran resmi atau tanda terima sementara (cetak_donasi.php)
     */
    public function cetak($kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();
        if (Auth::id() !== $d->user_id && ! Auth::user()?->isStaff()) {
            abort(403);
        }

        return view('donasi.cetak', compact('d'));
    }

    /**
     * Riwayat donasi donatur yang sedang masuk (donasi_saya.php)
     */
    public function riwayat(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        $tersalurkan = (float) Donasi::where('user_id', $userId)->where('status', 'diterima')->sum('jumlah');
        $berjalan = (float) Donasi::where('user_id', $userId)->whereIn('status', ['menunggu', 'dibayar'])->sum('jumlah');
        $totalCount = Donasi::where('user_id', $userId)->count();

        $riwayat = Donasi::where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate(8);

        return view('donasi.saya', compact('tersalurkan', 'berjalan', 'totalCount', 'riwayat'));
    }

    /**
     * Akses berkas bukti pembayaran
     */
    public function showBukti($kode)
    {
        $d = Donasi::where('kode', $kode)->firstOrFail();

        if (! Auth::check() || (! Auth::user()->isStaff() && Auth::id() !== $d->user_id)) {
            abort(403);
        }

        if (! BuktiDonasi::ada($d->bukti)) {
            abort(404, 'Berkas bukti pembayaran tidak ditemukan.');
        }

        return BuktiDonasi::response($d->bukti);
    }
}
