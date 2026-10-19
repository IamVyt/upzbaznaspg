<?php

if (!function_exists('format_rupiah')) {
    function format_rupiah($nominal): string
    {
        return 'Rp ' . number_format((float)$nominal, 0, ',', '.');
    }
}

if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal, bool $denganWaktu = false): string
    {
        if (!$tanggal) return '-';

        $c = $tanggal instanceof \Carbon\Carbon ? $tanggal : \Carbon\Carbon::parse($tanggal);
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $tglStr = $c->format('d') . ' ' . $bulan[(int)$c->format('m')] . ' ' . $c->format('Y');

        if ($denganWaktu) {
            $tglStr .= ' ' . $c->format('H:i') . ' WIB';
        }

        return $tglStr;
    }
}

if (!function_exists('audit_label_aksi')) {
    function audit_label_aksi(string $aksi): string
    {
        $label = [
            'login'                => 'Masuk ke sistem',
            'register'             => 'Mendaftar akun baru',
            'ganti_password'       => 'Mengganti password',
            'ubah_profil'          => 'Mengubah profil akun',
            'tambah'               => 'Mencatat transaksi kas',
            'edit'                 => 'Mengubah transaksi kas',
            'void'                 => 'Membatalkan transaksi kas (void)',
            'donasi_kirim'         => 'Donatur mengirim donasi',
            'donasi_lapor_bayar'   => 'Donatur melapor sudah membayar',
            'donasi_ganti_metode'  => 'Donatur mengganti metode bayar',
            'donasi_batal'         => 'Donatur membatalkan donasi',
            'donasi_terima'        => 'Mengonfirmasi donasi masuk kas',
            'donasi_tolak'         => 'Menolak donasi',
            'donasi_hapus'         => 'Menghapus data donasi',
            'donasi_hapus_semua'   => 'Menghapus banyak donasi sekaligus',
            'donasi_ubah_tampilan' => 'Donatur mengatur tampil di beranda',
            'donasi_beranda'       => 'Menyembunyikan atau menampilkan di beranda',
            'rekening_tambah'      => 'Menambah rekening atau e-wallet',
            'rekening_ubah'        => 'Mengubah rekening atau e-wallet',
            'rekening_hapus'       => 'Menghapus rekening atau e-wallet',
            'audit_hapus'          => 'Menghapus riwayat aktivitas',
        ];

        return $label[$aksi] ?? ucfirst(str_replace('_', ' ', $aksi));
    }
}

if (!function_exists('audit_uraian_ringkas')) {
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
                return "Mencatat kas $tipe sebesar $nominal" . (!empty($baru['keterangan']) ? ' (' . $baru['keterangan'] . ')' : '');
            case 'void':
                $alasan = $baru['alasan_batal'] ?? ($lama['alasan_batal'] ?? '-');
                return "Membatalkan transaksi dengan alasan: $alasan";
            case 'donasi_kirim':
                $kode = $baru['kode'] ?? '';
                $prog = $baru['program_nama'] ?? '';
                $jml = isset($baru['jumlah']) ? format_rupiah($baru['jumlah']) : '';
                return "Donasi online diajukan ($kode) untuk $prog sebesar $jml";
            case 'donasi_lapor_bayar':
                return "Donatur mengunggah bukti pembayaran dan melapor sudah transfer";
            case 'donasi_terima':
                $kode = $baru['kode'] ?? ($lama['kode'] ?? '');
                $jml = isset($baru['jumlah']) ? format_rupiah($baru['jumlah']) : '';
                return "Donasi $kode diterima & dicatat otomatis ke pembukuan kas ($jml)";
            case 'donasi_tolak':
                $alasan = $baru['alasan_tolak'] ?? '-';
                return "Donasi ditolak petugas. Alasan: $alasan";
            case 'donasi_hapus':
                return "Data donasi dihapus dari sistem";
            case 'donasi_hapus_semua':
                $cnt = $baru['jumlah_dihapus'] ?? 0;
                return "Menghapus $cnt data donasi sekaligus";
            case 'rekening_ubah':
                return "Memperbarui konfigurasi metode pembayaran";
            default:
                return 'Aktivitas ' . audit_label_aksi($aksi);
        }
    }
}

if (!function_exists('ikon_program')) {
    function ikon_program($nama): string
    {
        $n = strtolower((string)$nama);
        foreach ([
            'zakat' => 'ic-zakat',
            'infak' => 'ic-infak',
            'sedekah' => 'ic-infak',
            'pendidikan' => 'ic-ilmu',
            'kesehatan' => 'ic-sehat',
            'ekonomi' => 'ic-usaha',
            'kemanusiaan' => 'ic-peduli'
        ] as $kunci => $id) {
            if (str_contains($n, $kunci)) {
                return $id;
            }
        }
        return 'ic';
    }
}

