<?php
// Menerjemahkan catatan riwayat aktivitas menjadi bahasa yang mudah dipahami admin:
// nama aktivitas, objek, kalimat penjelasan dari data JSON, dan asal perangkat (alamat IP).
// Data aslinya di tabel audit_log tidak diubah. Ini hanya cara menampilkannya.

const AUDIT_AKSI = [
    'login'               => 'Masuk ke sistem',
    'register'            => 'Mendaftar akun baru',
    'ganti_password'      => 'Mengganti password',
    'ubah_profil'         => 'Mengubah profil akun',
    'tambah'              => 'Mencatat transaksi kas',
    'edit'                => 'Mengubah transaksi kas',
    'void'                => 'Membatalkan transaksi kas (void)',
    'donasi_kirim'        => 'Donatur mengirim donasi',
    'donasi_lapor_bayar'  => 'Donatur melapor sudah membayar',
    'donasi_ganti_metode' => 'Donatur mengganti metode bayar',
    'donasi_batal'        => 'Donatur membatalkan donasi',
    'donasi_terima'       => 'Mengonfirmasi donasi masuk kas',
    'donasi_tolak'        => 'Menolak donasi',
    'donasi_hapus'        => 'Menghapus data donasi',
    'donasi_hapus_semua'  => 'Menghapus banyak donasi sekaligus',
    'donasi_ubah_tampilan' => 'Donatur mengatur tampil di beranda',
    'donasi_beranda'      => 'Menyembunyikan atau menampilkan di beranda',
    'kas_yatim_void'      => 'Membatalkan kas dari donasi terhapus',
    'qris_unggah'         => 'Mengunggah kode QRIS',
    'qris_hapus'          => 'Menghapus kode QRIS',
    'rekening_tambah'     => 'Menambah rekening atau e-wallet',
    'rekening_ubah'       => 'Mengubah rekening atau e-wallet',
    'rekening_hapus'      => 'Menghapus rekening atau e-wallet',
    'audit_hapus'         => 'Menghapus riwayat aktivitas',
];

const AUDIT_OBJEK = [
    'donasi' => 'Donasi', 'transaksi' => 'Transaksi kas', 'users' => 'Pengguna', 'muzakki' => 'Muzakki',
    'mustahik' => 'Mustahik', 'audit_log' => 'Riwayat aktivitas', 'rekening_bayar' => 'Rekening atau e-wallet',
    'pengaturan' => 'Pengaturan pembayaran', 'kategori_dana' => 'Kategori kas',
];

// Sebutan objek dalam kalimat untuk aktivitas tambah, ubah, dan hapus data master
const AUDIT_SEBUTAN = ['users' => 'pengguna', 'muzakki' => 'data muzakki', 'mustahik' => 'data mustahik', 'kategori_dana' => 'kategori kas'];

const AUDIT_FIELD = [
    'nama_lengkap' => 'Nama lengkap', 'username' => 'Username', 'role' => 'Peran', 'nama' => 'Nama', 'nik_nip' => 'NIP atau NIK',
    'nik' => 'NIK', 'kontak' => 'Kontak', 'alamat' => 'Alamat', 'asnaf' => 'Golongan asnaf', 'email' => 'Email',
    'no_hp' => 'No. HP', 'nip' => 'NIP', 'jumlah' => 'Jumlah', 'tanggal' => 'Tanggal', 'tipe' => 'Jenis',
    'kategori_id' => 'Kategori', 'status' => 'Status', 'metode' => 'Metode', 'channel' => 'Bank atau e-wallet',
    'nomor' => 'Nomor', 'aktif' => 'Status', 'kode' => 'Kode', 'program' => 'Program', 'paket' => 'Paket', 'alasan' => 'Alasan',
];

// Kolom teknis yang tidak berguna bagi admin dan tidak ditampilkan
const AUDIT_ABAIKAN = ['id', 'password', 'aktif', 'dibuat_at', 'diubah_at'];

function audit_label_aksi(string $aksi): string {
    if (isset(AUDIT_AKSI[$aksi])) return AUDIT_AKSI[$aksi];
    if (preg_match('/^(tambah|ubah|hapus)_(\w+)$/', $aksi, $m)) {
        $kata = ['tambah' => 'Menambah', 'ubah' => 'Mengubah', 'hapus' => 'Menghapus'][$m[1]];
        return $kata . ' ' . (AUDIT_SEBUTAN[$m[2]] ?? str_replace('_', ' ', $m[2]));
    }
    return ucfirst(str_replace('_', ' ', $aksi));
}

// Objek yang dikenai aktivitas. Nomor internal baris disembunyikan bila tidak ada, dan donasi memakai kodenya
function audit_objek(array $r): string {
    $tabel = (string)($r['tabel'] ?? '');
    if ($tabel === '') return '-';
    $nama = AUDIT_OBJEK[$tabel] ?? $tabel;
    $data = array_merge(json_decode((string)($r['data_lama'] ?? ''), true) ?: [], json_decode((string)($r['data_baru'] ?? ''), true) ?: []);
    if ($tabel === 'donasi' && !empty($data['kode'])) return $nama . ' ' . $data['kode'];
    return $nama . ((int)($r['record_id'] ?? 0) > 0 ? ' #' . (int)$r['record_id'] : '');
}

// Alamat IP diubah menjadi keterangan yang bisa dipahami. Mengembalikan [teks utama, teks kecil]
function audit_label_ip(?string $ip): array {
    $ip = trim((string)$ip);
    if ($ip === '') return ['-', ''];
    if ($ip === '::1' || strpos($ip, '127.') === 0) return ['Komputer ini sendiri', 'Alamat uji lokal (' . $ip . ')'];
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return [$ip, ''];
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) return ['Jaringan kantor atau lokal', $ip];
    return [$ip, 'Dari internet'];
}

function audit_nama_kategori($conn, int $id): string {
    static $peta = null;
    if ($peta === null) {
        $peta = [];
        foreach (q_all($conn, "SELECT id, nama FROM kategori_dana") as $k) $peta[(int)$k['id']] = $k['nama'];
    }
    return $peta[$id] ?? ('Kategori #' . $id . ' (sudah dihapus)');
}

function audit_nama_channel($conn, string $kode): string {
    static $peta = null;
    if ($peta === null) {
        $peta = [];
        try {
            foreach (q_all($conn, "SELECT kode, nama FROM rekening_bayar") as $k) $peta[$k['kode']] = $k['nama'];
        } catch (Throwable $galat) {
        }
    }
    return $peta[$kode] ?? strtoupper($kode);
}

// Nilai tiap kolom ditulis dengan bahasa yang lazim (rupiah, nama peran, nama status, dan seterusnya)
function audit_nilai($conn, string $kunci, $nilai): string {
    global $metode;
    if ($nilai === null || $nilai === '') return '(kosong)';
    switch ($kunci) {
        case 'jumlah': case 'total_nominal':
            return rupiah($nilai);
        case 'tipe':
            return $nilai === 'masuk' ? 'Pemasukan' : ($nilai === 'keluar' ? 'Pengeluaran' : (string)$nilai);
        case 'role':
            return ['admin' => 'Admin', 'bendahara' => 'Bendahara', 'ketua' => 'Ketua', 'user' => 'User (donatur)'][$nilai] ?? (string)$nilai;
        case 'status':
            return label_status_donasi((string)$nilai)[0];
        case 'aktif':
            return (int)$nilai ? 'Aktif' : 'Nonaktif';
        case 'kategori_id':
            return audit_nama_kategori($conn, (int)$nilai);
        case 'metode':
            return $metode[$nilai][0] ?? (string)$nilai;
        case 'channel':
            return audit_nama_channel($conn, (string)$nilai);
        case 'tanggal':
            $waktu = strtotime((string)$nilai);
            return $waktu ? date('d/m/Y', $waktu) : (string)$nilai;
        case 'asnaf':
            return ucfirst(str_replace('_', ' ', (string)$nilai));
    }
    return is_scalar($nilai) ? (string)$nilai : json_encode($nilai, JSON_UNESCAPED_UNICODE);
}

function audit_metode($conn, array $a): string {
    if (empty($a['metode'])) return '-';
    $teks = audit_nilai($conn, 'metode', $a['metode']);
    if (!empty($a['channel'])) $teks .= ' (' . audit_nilai($conn, 'channel', $a['channel']) . ')';
    return $teks;
}

// Daftar "Nama kolom: nilai" dari satu set data
function audit_daftar($conn, array $data, array $abaikan = AUDIT_ABAIKAN): array {
    $baris = [];
    foreach ($data as $k => $nilai) {
        if (in_array($k, $abaikan, true)) continue;
        $baris[] = (AUDIT_FIELD[$k] ?? ucfirst(str_replace('_', ' ', $k))) . ': ' . audit_nilai($conn, $k, $nilai);
    }
    return $baris;
}

// Hanya kolom yang berubah, ditulis "Nama kolom: nilai lama menjadi nilai baru"
function audit_selisih($conn, array $lama, array $baru, array $abaikan = AUDIT_ABAIKAN): array {
    $baris = [];
    foreach ($baru as $k => $nilai) {
        if (in_array($k, $abaikan, true)) continue;
        $label = AUDIT_FIELD[$k] ?? ucfirst(str_replace('_', ' ', $k));
        if (array_key_exists($k, $lama)) {
            if ((string)$lama[$k] === (string)$nilai) continue;
            $baris[] = $label . ': ' . audit_nilai($conn, $k, $lama[$k]) . ' menjadi ' . audit_nilai($conn, $k, $nilai);
        } else {
            $baris[] = $label . ': ' . audit_nilai($conn, $k, $nilai);
        }
    }
    return $baris;
}

// Penjelasan satu catatan dalam beberapa kalimat pendek. Baris pertama adalah inti kejadiannya
function audit_uraian($conn, array $r): array {
    $aksi = (string)$r['aksi'];
    $lama = json_decode((string)($r['data_lama'] ?? ''), true) ?: [];
    $baru = json_decode((string)($r['data_baru'] ?? ''), true) ?: [];
    $v = function (array $a, string $k) use ($conn) { return isset($a[$k]) && $a[$k] !== '' ? audit_nilai($conn, $k, $a[$k]) : '-'; };
    $t = function (array $a, string $k) { return isset($a[$k]) && $a[$k] !== '' ? (string)$a[$k] : '-'; };

    switch ($aksi) {
        case 'login':
            return ['Berhasil masuk ke sistem.'];
        case 'register':
            return ['Akun donatur baru berhasil dibuat.'];
        case 'ganti_password':
            return ['Password akun diganti. Isi password tidak pernah dicatat di riwayat.'];
        case 'ubah_profil':
            return array_merge(['Data profil diubah.'], audit_selisih($conn, $lama, $baru));

        case 'tambah':
            if (($r['tabel'] ?? '') === 'transaksi') {
                return [
                    $v($baru, 'tipe') . ' ' . $v($baru, 'jumlah') . ' atas nama ' . $t($baru, 'nama') . ' dicatat ke kas.',
                    'Kategori: ' . $v($baru, 'kategori_id') . '. Tanggal transaksi: ' . $v($baru, 'tanggal') . '.',
                ];
            }
            break;
        case 'edit':
            if (($r['tabel'] ?? '') === 'transaksi') {
                $ubah = audit_selisih($conn, $lama, array_intersect_key($baru, $lama));
                return array_merge(['Data transaksi kas diubah.'], $ubah ?: ['Perubahan hanya pada tanggal atau keterangan.']);
            }
            break;
        case 'void':
            $baris = ['Transaksi atas nama ' . $t($lama, 'nama') . ' sebesar ' . $v($lama, 'jumlah') . ' dibatalkan (void).'];
            if (!empty($baru['alasan'])) $baris[] = 'Alasan: ' . $baru['alasan'];
            $baris[] = 'Catatan tetap tersimpan di riwayat kas dengan tanda coret dan tidak dihitung ke saldo.';
            return $baris;

        case 'donasi_kirim':
            return [
                'Donasi ' . $t($baru, 'kode') . ' dibuat untuk program ' . $t($baru, 'program') . ', paket ' . $t($baru, 'paket') . ', sebesar ' . $v($baru, 'jumlah') . '.',
                'Metode pembayaran: ' . audit_metode($conn, $baru) . '.',
            ];
        case 'donasi_lapor_bayar':
            return ['Donatur melaporkan sudah membayar. Bukti pembayaran: ' . $t($baru, 'bukti') . '.', 'Status berubah menjadi Menunggu pengecekan.'];
        case 'donasi_ganti_metode':
            return ['Metode pembayaran diganti dari ' . audit_metode($conn, $lama) . ' menjadi ' . audit_metode($conn, $baru) . '.'];
        case 'donasi_batal':
            return ['Donatur membatalkan donasinya sebelum membayar.'];
        case 'donasi_terima':
            $baris = ['Donasi ' . $t($baru, 'kode') . ' sebesar ' . $v($baru, 'jumlah') . ' dikonfirmasi diterima.'];
            if (!empty($lama['status'])) $baris[] = 'Status sebelumnya: ' . $v($lama, 'status') . '.';
            if (!empty($baru['transaksi_id'])) $baris[] = 'Dicatat sebagai pemasukan kas, transaksi nomor ' . (int)$baru['transaksi_id'] . '.';
            return $baris;
        case 'donasi_tolak':
            return ['Donasi ' . $t($baru, 'kode') . ' ditolak.', 'Alasan: ' . $t($baru, 'alasan')];
        case 'donasi_hapus':
            $baris = ['Donasi ' . $t($lama, 'kode') . ' dari ' . $t($lama, 'nama') . ' dihapus (' . $t($lama, 'program') . ', ' . $t($lama, 'paket') . ', ' . $v($lama, 'jumlah') . ', ' . $v($lama, 'metode') . ').'];
            if (!empty($lama['status'])) $baris[] = 'Status saat dihapus: ' . $v($lama, 'status') . '.';
            if (($lama['kas_dibatalkan'] ?? '') === 'ya') $baris[] = 'Catatan kas ikut dibatalkan (void) dan saldo disesuaikan.';
            elseif (!empty($lama['transaksi_id'])) $baris[] = 'Catatan kas tidak diubah, saldo tetap.';
            else $baris[] = 'Donasi ini tidak punya catatan kas, jadi kas tidak terpengaruh.';
            return $baris;
        case 'donasi_hapus_semua':
            $n = (int)($baru['jumlah_donasi'] ?? 0);
            $tab = ['menunggu' => 'tab Menunggu pembayaran', 'batal' => 'tab Ditolak atau dibatalkan'][$baru['tab'] ?? ''] ?? null;
            return [
                'Menghapus ' . $n . ' donasi ' . ($tab ? 'dari ' . $tab : 'yang sudah tersalurkan (menu Data donasi)') . ', total ' . $v($baru, 'total_nominal') . '.',
                $tab ? 'Donasi ini belum masuk kas, jadi kas tidak terpengaruh.' : 'Catatan kas tidak diubah.',
            ];

        case 'donasi_ubah_tampilan':
            $pilihanNama = ['lengkap' => 'nama lengkap', 'singkat' => 'nama disingkat', 'anonim' => 'Hamba Allah'];
            $baris = [(!empty($baru['beranda']) ? 'Donatur setuju donasinya tampil di beranda' : 'Donatur tidak menampilkan donasinya di beranda') . '.'];
            if (!empty($baru['beranda'])) $baris[] = 'Nama yang tampil: ' . ($pilihanNama[$baru['nama'] ?? ''] ?? '-') . '. Doa: ' . (!empty($baru['doa']) ? 'ikut tampil' : 'tidak ditampilkan') . '.';
            return $baris;
        case 'donasi_beranda':
            return [!empty($baru['disembunyikan']) ? 'Donasi ' . $t($baru, 'kode') . ' disembunyikan dari beranda oleh pengurus.' : 'Donasi ' . $t($baru, 'kode') . ' ditampilkan lagi di beranda.'];

        case 'qris_unggah':
            return ['Gambar kode QRIS resmi diperbarui. Metode QRIS kini bisa dipilih donatur.'];
        case 'qris_hapus':
            return ['Gambar kode QRIS dihapus. Metode QRIS tidak lagi bisa dipilih donatur.'];
        case 'rekening_tambah':
            return ['Ditambahkan: ' . $t($baru, 'nama') . ', nomor ' . $t($baru, 'nomor') . ', ' . $v($baru, 'aktif') . '.'];
        case 'rekening_ubah':
            return array_merge(['Data ' . $t($lama, 'nama') . ' diubah.'], audit_selisih($conn, $lama, $baru, []));
        case 'rekening_hapus':
            return ['Dihapus: ' . $t($lama, 'nama') . ', nomor ' . $t($lama, 'nomor') . '.', 'Donasi yang sudah dibuat tetap menyimpan data pembayarannya.'];

        case 'audit_hapus':
            return ['Menghapus ' . (int)($baru['dihapus'] ?? 0) . ' catatan riwayat aktivitas (' . $t($baru, 'rentang') . ').', 'Penghapusan ini dicatat di sini agar ada jejaknya.'];
    }

    // Tambah, ubah, dan hapus data master (pengguna, muzakki, mustahik)
    if (preg_match('/^(tambah|ubah|hapus)_(\w+)$/', $aksi, $m)) {
        $objek = AUDIT_SEBUTAN[$m[2]] ?? str_replace('_', ' ', $m[2]);
        if ($m[1] === 'hapus') {
            $awal = in_array($m[2], ['muzakki', 'mustahik'], true) ? 'Menghapus ' . $objek . ' dari daftar (riwayat transaksinya tetap tersimpan).' : 'Menghapus ' . $objek . '.';
            return array_merge([$awal], audit_daftar($conn, $lama));
        }
        return array_merge([($m[1] === 'tambah' ? 'Menambah ' : 'Mengubah ') . $objek . '.'], audit_daftar($conn, $baru));
    }

    // Jenis aktivitas yang belum dikenal: tampilkan datanya apa adanya tapi tetap rapi
    if ($lama && $baru) return array_merge(['Perubahan data:'], audit_selisih($conn, $lama, $baru));
    $baris = audit_daftar($conn, $baru ?: $lama);
    return $baris ?: ['Tidak ada keterangan tambahan.'];
}
