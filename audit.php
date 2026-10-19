<?php
require __DIR__ . '/php/fungsi.php';
require __DIR__ . '/php/data.php';
require __DIR__ . '/php/audit_uraian.php';
wajib_peran(['admin']);

// Hapus riwayat: 1 minggu terakhir, 1 bulan terakhir, atau semua. Hanya admin (lihat wajib_peran di atas).
// Penghapusan itu sendiri dicatat sebagai satu baris baru (audit_hapus) supaya tetap ada jejak siapa yang menghapus
$rentangLog = [
    'minggu' => ['1 minggu terakhir', 'WHERE waktu >= DATE_SUB(NOW(), INTERVAL 7 DAY)'],
    'bulan'  => ['1 bulan terakhir', 'WHERE waktu >= DATE_SUB(NOW(), INTERVAL 1 MONTH)'],
    'semua'  => ['semua riwayat', ''],
];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pilih = is_string($_POST['rentang'] ?? null) ? $_POST['rentang'] : '';
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir kedaluwarsa. Silakan ulangi.');
    } elseif (!isset($rentangLog[$pilih])) {
        flash('danger', 'Rentang penghapusan tidak dikenal.');
    } else {
        $stmt  = q($conn, "DELETE FROM audit_log " . $rentangLog[$pilih][1]);
        $hapus = (int)mysqli_stmt_affected_rows($stmt);
        if ($hapus > 0) {
            catat_audit($conn, 'audit_hapus', 'audit_log', null, null, ['rentang' => $rentangLog[$pilih][0], 'dihapus' => $hapus]);
            flash('success', $hapus . ' catatan aktivitas dihapus (' . $rentangLog[$pilih][0] . ').');
        } else {
            flash('info', 'Tidak ada catatan pada rentang tersebut.');
        }
    }
    redirect('audit.php');
}

$cari = trim($_GET['cari'] ?? '');
$aksi = trim($_GET['aksi'] ?? '');
$per  = 15;

$where = '1 = 1'; $types = ''; $params = [];
if ($cari !== '') {
    $like = '%' . addcslashes($cari, '%_\\') . '%';
    $where .= ' AND (username LIKE ? OR tabel LIKE ? OR ip LIKE ?)'; $types .= 'sss'; array_push($params, $like, $like, $like);
}
if ($aksi !== '') { $where .= ' AND aksi = ?'; $types .= 's'; $params[] = $aksi; }

// Pilihan jenis aktivitas ditampilkan dengan nama yang mudah dipahami, diurutkan menurut abjad
$daftarAksi = [];
foreach (array_column(q_all($conn, "SELECT DISTINCT aksi FROM audit_log"), 'aksi') as $a) $daftarAksi[$a] = audit_label_aksi($a);
asort($daftarAksi);

$total  = (int)q_one($conn, "SELECT COUNT(*) AS c FROM audit_log WHERE $where", $types, $params)['c'];
$jmlHal = max(1, (int)ceil($total / $per));
$hal    = min(max(1, (int)($_GET['hal'] ?? 1)), $jmlHal);
$baris  = q_all($conn, "SELECT * FROM audit_log WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$per, ($hal - 1) * $per]));
$qs = array_filter(['cari' => $cari, 'aksi' => $aksi]);

// Jumlah catatan per rentang untuk menu Hapus riwayat (tidak terpengaruh pencarian)
$hitung = q_one($conn, "SELECT COUNT(*) AS semua,
        COALESCE(SUM(waktu >= DATE_SUB(NOW(), INTERVAL 7 DAY)), 0) AS minggu,
        COALESCE(SUM(waktu >= DATE_SUB(NOW(), INTERVAL 1 MONTH)), 0) AS bulan
    FROM audit_log");
$pesanHapus = [
    'minggu' => 'Hapus ' . (int)$hitung['minggu'] . ' catatan aktivitas dalam 7 hari terakhir? Tindakan ini tidak bisa dibatalkan.',
    'bulan'  => 'Hapus ' . (int)$hitung['bulan'] . ' catatan aktivitas dalam 1 bulan terakhir? Tindakan ini tidak bisa dibatalkan.',
    'semua'  => 'Hapus SEMUA ' . (int)$hitung['semua'] . ' catatan aktivitas, bukan hanya yang tampil di hasil pencarian? Tindakan ini tidak bisa dibatalkan.',
];

// Ringkasan di bagian atas halaman (tidak terpengaruh pencarian)
$ringkasan = q_one($conn, "SELECT COUNT(*) AS semua,
        COALESCE(SUM(DATE(waktu) = CURDATE()), 0) AS hari_ini,
        COUNT(DISTINCT CASE WHEN waktu >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN username END) AS aktif
    FROM audit_log");
$terbanyak = q_one($conn, "SELECT aksi, COUNT(*) AS n FROM audit_log GROUP BY aksi ORDER BY n DESC, aksi ASC LIMIT 1");

// Waktu relatif, misalnya "5 menit lalu"
if (!function_exists('audit_waktu_lalu')) {
    function audit_waktu_lalu($waktu) {
        $s = max(0, time() - strtotime($waktu));
        if ($s < 60) return 'baru saja';
        if ($s < 3600) return floor($s / 60) . ' menit lalu';
        if ($s < 86400) return floor($s / 3600) . ' jam lalu';
        if ($s < 172800) return 'kemarin';
        if ($s < 2592000) return floor($s / 86400) . ' hari lalu';
        return '';
    }
}
// Golongan aktivitas untuk warna titik: hapus, tambah, ubah, masuk, atau lain
if (!function_exists('audit_golongan')) {
    function audit_golongan($aksi) {
        $a = strtolower((string)$aksi);
        foreach (['hapus' => 'hapus', 'void' => 'hapus', 'batal' => 'hapus', 'tolak' => 'hapus', 'tambah' => 'tambah', 'register' => 'tambah',
                  'terima' => 'tambah', 'edit' => 'ubah', 'ubah' => 'ubah', 'ganti' => 'ubah', 'login' => 'masuk', 'logout' => 'masuk'] as $kunci => $golongan) {
            if (strpos($a, $kunci) !== false) return $golongan;
        }
        return 'lain';
    }
}

$title = 'Riwayat aktivitas';
$aktif = 'audit';
include __DIR__ . '/php/app_header.php';
?>
<section class="top">
 <div class="head"><h1 class="tag">Riwayat aktivitas</h1><p>Catatan siapa melakukan apa di sistem: login, perubahan transaksi, donasi, dan perubahan data oleh seluruh pengguna.</p></div>

 <div class="grid4">
  <div class="c g stat"><small>Total aktivitas</small><b><?= (int)$ringkasan['semua'] ?></b><small>Seluruh catatan tersimpan</small></div>
  <div class="c l stat"><small>Hari ini</small><b><?= (int)$ringkasan['hari_ini'] ?></b><small>Aktivitas sejak pukul 00.00</small></div>
  <div class="c l stat"><small>Pengguna aktif</small><b><?= (int)$ringkasan['aktif'] ?></b><small>Dalam 7 hari terakhir</small></div>
  <div class="c d stat"><small>Paling sering</small><b style="font-size:1.1rem"><?= $terbanyak ? e(audit_label_aksi($terbanyak['aksi'])) : '-' ?></b><small><?= $terbanyak ? (int)$terbanyak['n'] . ' kali tercatat' : 'Belum ada data' ?></small></div>
 </div>

 <form class="card fbox audit" method="get">
  <div><label for="cari">Cari pengguna, objek, atau alamat IP</label><input id="cari" type="text" name="cari" value="<?= e($cari) ?>"></div>
  <div><label for="aksi">Jenis aktivitas</label>
   <select id="aksi" name="aksi"><option value="">Semua aktivitas</option>
    <?php foreach ($daftarAksi as $kode => $nama): ?><option value="<?= e($kode) ?>" <?= $aksi === $kode ? 'selected' : '' ?>><?= e($nama) ?></option><?php endforeach; ?></select></div>
  <div class="acts"><button class="btn sm" type="submit">Terapkan</button><a class="btn o sm" href="audit.php">Reset</a></div>
 </form>

 <div class="card">
  <div class="subbar">
   <h2 class="sub">Log aktivitas <small><?= $total ?> catatan</small></h2>
   <div class="dd">
    <button class="btn o sm dd-btn bahaya" type="button" aria-expanded="false" aria-controls="dd-hapus">Hapus riwayat</button>
    <div class="dd-menu" id="dd-hapus" hidden>
     <?php foreach ($rentangLog as $kode => [$nama, $kondisi]): $n = (int)$hitung[$kode]; ?>
     <form method="post" data-konfirmasi="<?= e($pesanHapus[$kode]) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="rentang" value="<?= e($kode) ?>">
      <button class="dd-item<?= $kode === 'semua' ? ' bahaya' : '' ?>" type="submit"<?= $n === 0 ? ' disabled' : '' ?>>Hapus <?= e($nama) ?><small><?= $n ?> catatan</small></button>
     </form>
     <?php endforeach; ?>
    </div>
   </div>
  </div>

  <div class="scroll">
   <table class="tbl log">
    <thead><tr><th>Kapan</th><th>Siapa</th><th>Apa yang terjadi</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (!$baris): ?><tr><td colspan="4"><div class="empty"><b>Belum ada catatan</b><p>Aktivitas akan muncul di sini setelah ada yang login atau mengubah data.</p></div></td></tr><?php endif; ?>
    <?php foreach ($baris as $r):
        $uraian = audit_uraian($conn, $r);
        [$ipUtama, $ipKecil] = audit_label_ip($r['ip']);
        $kls = audit_golongan($r['aksi']);
        $rel = audit_waktu_lalu($r['waktu']);
        $punyaData = $r['data_lama'] || $r['data_baru'];
    ?>
     <tr>
      <td class="nw"><?= date('d/m/Y', strtotime($r['waktu'])) ?><small><?= date('H:i', strtotime($r['waktu'])) ?><?= $rel !== '' ? ', ' . e($rel) : '' ?></small></td>
      <td><b><?= e($r['username'] ?? 'Tamu') ?></b></td>
      <td class="detail">
       <p class="kalimat" title="<?= e(implode(' ', array_slice($uraian, 1))) ?>"><i class="titik k-<?= $kls ?>"></i><?= e($uraian[0] ?? audit_label_aksi($r['aksi'])) ?></p>









      </td>
      <td class="nw ip" title="<?= e($ipUtama) ?>"><?= e($r['ip'] ?: '-') ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if ($jmlHal > 1): ?>
  <div class="pg"><small>Halaman <?= $hal ?> dari <?= $jmlHal ?></small>
   <div><?php for ($i = max(1, $hal - 2); $i <= min($jmlHal, $hal + 2); $i++): ?><a class="<?= $i === $hal ? 'on' : '' ?>" href="?<?= e(http_build_query($qs + ['hal' => $i])) ?>"><?= $i ?></a><?php endfor; ?></div>
  </div>
  <?php endif; ?>
 </div>
</section>
<?php include __DIR__ . '/php/app_footer.php'; ?>
