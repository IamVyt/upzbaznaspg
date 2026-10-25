<?php

use Carbon\Carbon;

if (! function_exists('format_rupiah')) {
    function format_rupiah($nominal): string
    {
        return 'Rp '.number_format((float) $nominal, 0, ',', '.');
    }
}

if (! function_exists('rupiah')) {
    function rupiah($nominal): string
    {
        return format_rupiah($nominal);
    }
}

if (! function_exists('nama_singkat')) {
    function nama_singkat(string $nama): string
    {
        $kata = preg_split('/\s+/u', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
        if (! $kata) {
            return 'Hamba Allah';
        }
        $hasil = [array_shift($kata)];
        foreach ($kata as $k) {
            $hasil[] = mb_strtoupper(mb_substr($k, 0, 1)).'.';
        }

        return implode(' ', $hasil);
    }
}

if (! function_exists('nama_beranda')) {
    function nama_beranda(string $nama, $samarkan = false, $singkat = false): string
    {
        if ($samarkan) {
            return 'Hamba Allah';
        }

        return mb_strimwidth($singkat ? nama_singkat($nama) : trim($nama), 0, 40, '…');
    }
}

if (! function_exists('label_status_donasi')) {
    function label_status_donasi(string $status): array
    {
        return [
            'menunggu' => ['Menunggu pembayaran', 'wait'],
            'dibayar' => ['Menunggu pengecekan', 'check'],
            'diterima' => ['Sudah tersalurkan', 'in'],
            'ditolak' => ['Ditolak', 'void'],
            'dibatalkan' => ['Dibatalkan', 'off'],
        ][$status] ?? [$status, ''];
    }
}

if (! function_exists('slug')) {
    function slug($teks): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $teks)), '-');
    }
}

if (! function_exists('waktu_relatif')) {
    function waktu_relatif(int $selisih): string
    {
        $selisih = max(0, $selisih);
        if ($selisih < 60) {
            return 'baru saja';
        }
        if ($selisih < 3600) {
            return floor($selisih / 60).' menit lalu';
        }
        if ($selisih < 86400) {
            return floor($selisih / 3600).' jam lalu';
        }
        if ($selisih < 86400 * 30) {
            return floor($selisih / 86400).' hari lalu';
        }
        if ($selisih < 86400 * 365) {
            return floor($selisih / (86400 * 30)).' bulan lalu';
        }

        return floor($selisih / (86400 * 365)).' tahun lalu';
    }
}

if (! function_exists('tgl_indo')) {
    function tgl_indo($tanggal, bool $denganWaktu = false): string
    {
        if (! $tanggal) {
            return '-';
        }

        $c = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $tglStr = $c->format('d').' '.$bulan[(int) $c->format('m')].' '.$c->format('Y');

        if ($denganWaktu) {
            $tglStr .= ' '.$c->format('H:i').' WIB';
        }

        return $tglStr;
    }
}

if (! function_exists('audit_label_aksi')) {
    function audit_label_aksi(string $aksi): string
    {
        $label = [
            'login' => 'Masuk ke sistem',
            'register' => 'Mendaftar akun baru',
            'ganti_password' => 'Mengganti password',
            'reset_password' => 'Mereset password akun',
            'ubah_profil' => 'Mengubah profil akun',
            'tambah' => 'Mencatat transaksi kas',
            'edit' => 'Mengubah transaksi kas',
            'void' => 'Membatalkan transaksi kas (void)',
            'donasi_kirim' => 'Donatur mengirim donasi',
            'donasi_lapor_bayar' => 'Donatur melapor sudah membayar',
            'donasi_ganti_metode' => 'Donatur mengganti metode bayar',
            'donasi_batal' => 'Donatur membatalkan donasi',
            'donasi_terima' => 'Mengonfirmasi donasi masuk kas',
            'donasi_tolak' => 'Menolak donasi',
            'donasi_hapus' => 'Menghapus data donasi',
            'donasi_hapus_semua' => 'Menghapus banyak donasi sekaligus',
            'donasi_ubah_tampilan' => 'Donatur mengatur tampil di beranda',
            'donasi_beranda' => 'Menyembunyikan atau menampilkan di beranda',
            'rekening_tambah' => 'Menambah rekening atau e-wallet',
            'rekening_ubah' => 'Mengubah rekening atau e-wallet',
            'rekening_hapus' => 'Menghapus rekening atau e-wallet',
            'audit_hapus' => 'Menghapus riwayat aktivitas',
        ];

        return $label[$aksi] ?? ucfirst(str_replace('_', ' ', $aksi));
    }
}

if (! function_exists('audit_uraian_ringkas')) {
    function audit_uraian_ringkas($log): string
    {
        $aksi = $log->aksi;
        $lama = $log->data_lama ?? [];
        $baru = $log->data_baru ?? [];

        switch ($aksi) {
            case 'login':
                return 'Pengguna berhasil masuk ke dalam sistem';
            case 'register':
                return 'Pengguna baru berhasil melakukan registrasi akun';
            case 'ubah_profil':
                return 'Memperbarui informasi profil akun';
            case 'ganti_password':
                return 'Password akun berhasil diubah';
            case 'tambah':
                $nominal = isset($baru['jumlah']) ? format_rupiah($baru['jumlah']) : '';
                $tipe = ($baru['tipe'] ?? '') === 'masuk' ? 'pemasukan' : 'pengeluaran';

                return "Mencatat kas $tipe sebesar $nominal".(! empty($baru['keterangan']) ? ' ('.$baru['keterangan'].')' : '');
            case 'void':
                $alasan = $baru['alasan_batal'] ?? ($lama['alasan_batal'] ?? '-');

                return "Membatalkan transaksi dengan alasan: $alasan";
            case 'donasi_kirim':
                $kode = $baru['kode'] ?? '';
                $prog = $baru['program_nama'] ?? '';
                $jml = isset($baru['jumlah']) ? format_rupiah($baru['jumlah']) : '';

                return "Donasi online diajukan ($kode) untuk $prog sebesar $jml";
            case 'donasi_lapor_bayar':
                return 'Donatur mengunggah bukti pembayaran dan melapor sudah transfer';
            case 'donasi_terima':
                $kode = $baru['kode'] ?? ($lama['kode'] ?? '');
                $jml = isset($baru['jumlah']) ? format_rupiah($baru['jumlah']) : '';

                return "Donasi $kode diterima & dicatat otomatis ke pembukuan kas ($jml)";
            case 'donasi_tolak':
                $alasan = $baru['alasan_tolak'] ?? '-';

                return "Donasi ditolak petugas. Alasan: $alasan";
            case 'donasi_hapus':
                return 'Data donasi dihapus dari sistem';
            case 'donasi_hapus_semua':
                $cnt = $baru['jumlah_dihapus'] ?? 0;

                return "Menghapus $cnt data donasi sekaligus";
            case 'rekening_ubah':
                return 'Memperbarui konfigurasi metode pembayaran';
            default:
                return 'Aktivitas '.audit_label_aksi($aksi);
        }
    }
}

if (! function_exists('audit_waktu_lalu')) {
    function audit_waktu_lalu($waktu): string
    {
        $time = $waktu instanceof Carbon ? $waktu->timestamp : strtotime((string) $waktu);
        $s = max(0, time() - $time);
        if ($s < 60) {
            return 'baru saja';
        }
        if ($s < 3600) {
            return floor($s / 60).' menit lalu';
        }
        if ($s < 86400) {
            return floor($s / 3600).' jam lalu';
        }
        if ($s < 172800) {
            return 'kemarin';
        }
        if ($s < 2592000) {
            return floor($s / 86400).' hari lalu';
        }

        return '';
    }
}

if (! function_exists('audit_golongan')) {
    function audit_golongan($aksi): string
    {
        $a = strtolower((string) $aksi);
        foreach ([
            'hapus' => 'hapus', 'void' => 'hapus', 'batal' => 'hapus', 'tolak' => 'hapus',
            'tambah' => 'tambah', 'register' => 'tambah', 'terima' => 'tambah',
            'edit' => 'ubah', 'ubah' => 'ubah', 'ganti' => 'ubah', 'reset' => 'ubah',
            'login' => 'masuk', 'logout' => 'masuk',
        ] as $kunci => $golongan) {
            if (str_contains($a, $kunci)) {
                return $golongan;
            }
        }

        return 'lain';
    }
}

if (! function_exists('audit_label_ip')) {
    function audit_label_ip(?string $ip): array
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return ['-', ''];
        }
        if ($ip === '::1' || str_starts_with($ip, '127.')) {
            return ['Komputer ini sendiri', 'Alamat uji lokal ('.$ip.')'];
        }
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return [$ip, ''];
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['Jaringan kantor atau lokal', $ip];
        }

        return [$ip, 'Dari internet'];
    }
}

if (! function_exists('ikon_program')) {
    function ikon_program($nama): string
    {
        $n = strtolower((string) $nama);
        foreach ([
            'zakat' => 'ic-zakat',
            'infak' => 'ic-infak',
            'sedekah' => 'ic-infak',
            'pendidikan' => 'ic-ilmu',
            'kesehatan' => 'ic-sehat',
            'ekonomi' => 'ic-usaha',
            'kemanusiaan' => 'ic-peduli',
        ] as $kunci => $id) {
            if (str_contains($n, $kunci)) {
                return $id;
            }
        }

        return 'ic';
    }
}

if (! function_exists('can_input')) {
    function can_input(): bool
    {
        return auth()->check() && auth()->user()->canManageFinance();
    }
}

if (! function_exists('is_staff')) {
    function is_staff(): bool
    {
        return auth()->check() && auth()->user()->isStaff();
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}
