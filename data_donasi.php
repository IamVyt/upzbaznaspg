<?php
// Data donasi: donasi online yang sudah berhasil (status Sudah tersalurkan) beserta data donaturnya.
// Donasi yang masih menunggu, ditolak, atau dibatalkan tidak tampil di sini. Pengecekannya ada di menu Donasi masuk.
require __DIR__ . '/php/fungsi.php';
require __DIR__ . '/php/data.php';
wajib_staff();
siapkan_donasi($conn);

// Hapus data donasi hanya untuk admin. Donasinya dihapus dari tabel donasi, jadi ikut hilang juga dari menu Donasi masuk
// dan dari riwayat Donasi saya milik donatur. Catatan di tabel kas (transaksi) sengaja tidak disentuh karena itu pembukuan.
// Setiap penghapusan dicatat di Riwayat aktivitas.
$bolehHapus = ($_SESSION['role'] ?? '') === 'admin';
// Catatan kas pemasukan dari donasi online yang data donasinya sudah tidak ada (sudah dihapus).
// Catatan seperti ini masih dihitung di saldo sampai dibatalkan (void)
$yatim = "tipe = 'masuk' AND dibatalkan_at IS NULL AND keterangan LIKE 'Donasi online %' AND id NOT IN (SELECT transaksi_id FROM donasi WHERE transaksi_id IS NOT NULL)";
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $aksi = is_string($_POST['aksi'] ?? null) ? $_POST['aksi'] : '';
    $balik = [];   // pencarian dan halaman yang sedang dibuka, supaya setelah hapus tidak kembali ke awal
    foreach (['cari', 'program', 'hal'] as $k) {
        if (is_string($_POST[$k] ?? null) && $_POST[$k] !== '') $balik[$k] = $_POST[$k];
    }

    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir kedaluwarsa. Silakan ulangi.');
    } elseif (!$bolehHapus) {
        flash('danger', 'Hanya admin yang dapat menghapus data donasi.');
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $d  = q_one($conn, "SELECT * FROM donasi WHERE id = ? AND status = 'diterima'", 'i', [$id]);
        if (!$d) {
            flash('danger', 'Data donasi tidak ditemukan atau sudah dihapus.');
        } else {
            // Tombol "Hapus dan batalkan kas" juga membatalkan (void) catatan kas milik donasi ini.
            // Donasi dan kas diubah dalam satu transaksi database, jadi saldo selalu sinkron dengan data.
            $batalKas = !empty($_POST['batal_kas']) && $d['transaksi_id'];
            $galat = jalankan_transaksi($conn, function () use ($conn, $id, $d, $batalKas) {
                q($conn, "DELETE FROM donasi WHERE id = ? AND status = 'diterima'", 'i', [$id]);
                if ($batalKas) {
                    q($conn, "UPDATE transaksi SET dibatalkan_at = NOW(), alasan_batal = ? WHERE id = ? AND dibatalkan_at IS NULL",
                        'si', ['Donasi ' . $d['kode'] . ' dihapus oleh admin', (int)$d['transaksi_id']]);
                }
            });
            if ($galat) {
                flash('danger', $galat);
            } else {
                hapus_bukti($d['bukti']);
                catat_audit($conn, 'donasi_hapus', 'donasi', $id, [
                    'kode' => $d['kode'], 'nama' => $d['nama'], 'program' => $d['program_nama'], 'paket' => $d['paket'],
                    'jumlah' => $d['jumlah'], 'metode' => $d['metode'], 'user_id' => $d['user_id'], 'transaksi_id' => $d['transaksi_id'],
                    'kas_dibatalkan' => $batalKas ? 'ya' : 'tidak',
                ]);
                flash('success', 'Donasi ' . $d['kode'] . ' atas nama ' . $d['nama'] . ' dihapus, termasuk dari Donasi masuk. '
                    . ($batalKas ? 'Catatan kas-nya juga dibatalkan (void) dan saldo disesuaikan.' : 'Catatan di Dashboard kas tidak diubah.'));
            }
        }
    } elseif ($aksi === 'hapus_semua') {
        $daftarHapus = q_all($conn, "SELECT id, bukti, jumlah FROM donasi WHERE status = 'diterima'");
        if (!$daftarHapus) {
            flash('info', 'Tidak ada data donasi yang bisa dihapus.');
        } else {
            // Hapus tepat yang tadi dicatat (berdasarkan id), supaya donasi yang baru saja dikonfirmasi tidak ikut terhapus tanpa tercatat
            $ids = implode(',', array_map('intval', array_column($daftarHapus, 'id')));
            $batalKas = !empty($_POST['batal_kas']);
            $trxIds = $batalKas
                ? array_map('intval', array_column(q_all($conn, "SELECT transaksi_id FROM donasi WHERE id IN ($ids) AND transaksi_id IS NOT NULL"), 'transaksi_id'))
                : [];
            // Donasi dan kas diubah dalam satu transaksi database, jadi saldo selalu sinkron dengan data
            $galat = jalankan_transaksi($conn, function () use ($conn, $ids, $trxIds) {
                q($conn, "DELETE FROM donasi WHERE id IN ($ids)");
                if ($trxIds) {
                    q($conn, "UPDATE transaksi SET dibatalkan_at = NOW(), alasan_batal = 'Semua data donasi dihapus oleh admin' WHERE id IN (" . implode(',', $trxIds) . ") AND dibatalkan_at IS NULL");
                }
            });
            if ($galat) {
                flash('danger', $galat);
                redirect('data_donasi.php');
            }
            foreach ($daftarHapus as $d) hapus_bukti($d['bukti']);
            catat_audit($conn, 'donasi_hapus_semua', 'donasi', null, null, [
                'jumlah_donasi' => count($daftarHapus), 'total_nominal' => array_sum(array_column($daftarHapus, 'jumlah')),
                'kas_dibatalkan' => count($trxIds),
            ]);
            flash('success', count($daftarHapus) . ' data donasi dihapus, termasuk dari Donasi masuk. '
                . ($batalKas ? count($trxIds) . ' catatan kas-nya juga dibatalkan (void) dan saldo disesuaikan.' : 'Catatan di Dashboard kas tidak diubah.'));
            $balik = [];
        }
    }
    // Membersihkan catatan kas dari donasi online yang datanya sudah dihapus lebih dulu (dibatalkan dengan void, bukan dihapus)
    if ($aksi === 'batalkan_yatim' && $bolehHapus && csrf_valid()) {
        $jmlYatim = (int)q_one($conn, "SELECT COUNT(*) AS n FROM transaksi WHERE $yatim")['n'];
        $galat = $jmlYatim ? jalankan_transaksi($conn, fn() => q($conn,
            "UPDATE transaksi SET dibatalkan_at = NOW(), alasan_batal = 'Data donasi sumbernya sudah dihapus' WHERE $yatim")) : null;
        if ($galat) {
            flash('danger', $galat);
        } elseif (!$jmlYatim) {
            flash('info', 'Tidak ada catatan kas yang perlu dibatalkan.');
        } else {
            catat_audit($conn, 'kas_yatim_void', 'transaksi', null, null, ['jumlah_catatan' => $jmlYatim]);
            flash('success', $jmlYatim . ' catatan kas dari donasi online yang datanya sudah dihapus dibatalkan (void). Saldo kas sudah disesuaikan.');
        }
    }
    redirect('data_donasi.php' . ($balik ? '?' . http_build_query($balik) : ''));
}

// Pilihan filter program: sama dengan program di beranda situs
$opsiProgram = [];
foreach ($program as $p) $opsiProgram[slug($p[0])] = $p[0];

$cari = is_string($_GET['cari'] ?? null) ? trim($_GET['cari']) : '';
$prog = is_string($_GET['program'] ?? null) ? $_GET['program'] : '';
if (!isset($opsiProgram[$prog])) $prog = '';

// Ringkasan semua donasi berhasil (tidak ikut terfilter)
$ring = q_one($conn, "SELECT COUNT(*) AS n, COALESCE(SUM(jumlah), 0) AS total,
        COUNT(DISTINCT COALESCE(CONCAT('u', user_id), CONCAT('n', nama))) AS donatur
    FROM donasi WHERE status = 'diterima'") ?: ['n' => 0, 'total' => 0, 'donatur' => 0];
$rata = (int)$ring['n'] > 0 ? (float)$ring['total'] / (int)$ring['n'] : 0;
$yatimInfo = $bolehHapus ? (q_one($conn, "SELECT COUNT(*) AS n, COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE $yatim") ?: ['n' => 0, 'total' => 0]) : ['n' => 0, 'total' => 0];

// Daftar donasi berhasil dengan pencarian dan filter program
$dari = " FROM donasi d LEFT JOIN users u ON u.id = d.user_id LEFT JOIN transaksi t ON t.id = d.transaksi_id WHERE d.status = 'diterima'";
$sql = ''; $types = ''; $params = [];
if ($prog !== '') {
    $sql .= " AND d.program = ?";
    $types .= 's';
    $params[] = $prog;
}
if ($cari !== '') {
    $like = '%' . addcslashes($cari, '%_\\') . '%';
    $sql .= " AND (d.nama LIKE ? OR d.kode LIKE ? OR d.kontak LIKE ? OR d.email LIKE ? OR d.nip LIKE ? OR u.username LIKE ?)";
    $types .= 'ssssss';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

$perHal   = 10;
$jml      = (int)q_one($conn, "SELECT COUNT(*) AS c" . $dari . $sql, $types, $params)['c'];
$totalHal = max(1, (int)ceil($jml / $perHal));
$hal      = min(max(1, (int)($_GET['hal'] ?? 1)), $totalHal);
$daftar   = q_all($conn, "SELECT d.id, d.kode, d.nama, d.samarkan_nama, d.nip, d.kontak, d.email, d.program_nama, d.paket,
        d.metode, d.channel_nama, d.jumlah, d.bukti, COALESCE(d.diproses_at, d.dibuat_at) AS waktu, u.username,
        t.id AS trx_id, t.dibatalkan_at AS trx_batal"
    . $dari . $sql . " ORDER BY waktu DESC, d.id DESC LIMIT ? OFFSET ?",
    $types . 'ii', array_merge($params, [$perHal, ($hal - 1) * $perHal]));

$qs = array_filter(['cari' => $cari, 'program' => $prog], fn($v) => $v !== '');

$title = 'Data donasi';
$aktif = 'data_donasi';
include __DIR__ . '/php/app_header.php';
?>

<section class="top">
 <div class="head"><h1 class="tag">Data donasi</h1><p>Data donatur yang donasinya sudah berhasil tersalurkan. Donasi yang belum dikonfirmasi petugas ada di menu Donasi masuk.</p></div>

 <div class="grid4">
  <div class="c g stat"><small>Total donasi berhasil</small><b><?= rupiah($ring['total']) ?></b><small>Sudah dikonfirmasi petugas</small></div>
  <div class="c l stat"><small>Jumlah donasi</small><b><?= (int)$ring['n'] ?> donasi</b><small>Berstatus sudah tersalurkan</small></div>
  <div class="c l stat"><small>Jumlah donatur</small><b><?= (int)$ring['donatur'] ?> orang</b><small>Donatur berbeda</small></div>
  <div class="c d stat"><small>Rata-rata donasi</small><b><?= rupiah($rata) ?></b><small>Per donasi berhasil</small></div>
 </div>

 <div class="bar">
  <form class="acts" method="get">
   <input class="cari" type="text" name="cari" value="<?= e($cari) ?>" placeholder="Cari nama, kode, kontak, atau akun" aria-label="Cari donasi">
   <select class="saring" name="program" aria-label="Filter program">
    <option value="">Semua program</option>
    <?php foreach ($opsiProgram as $s => $nama): ?><option value="<?= e($s) ?>"<?= $prog === $s ? ' selected' : '' ?>><?= e($nama) ?></option><?php endforeach; ?>
   </select>
   <button class="btn sm" type="submit">Cari</button>
   <?php if ($cari !== '' || $prog !== ''): ?><a class="btn o sm" href="data_donasi.php">Reset</a><?php endif; ?>
  </form>
  <?php if ($bolehHapus): ?>
  <form method="post" class="acts">
   <?= csrf_field() ?>
   <input type="hidden" name="aksi" value="hapus_semua">
   <button class="btn o sm bahaya" type="submit"<?= (int)$ring['n'] === 0 ? ' disabled' : '' ?> data-konfirmasi="<?= e('Hapus SEMUA ' . (int)$ring['n'] . ' donasi berhasil (total ' . rupiah($ring['total']) . '), bukan hanya yang tampil di hasil pencarian? Semuanya juga hilang dari Donasi masuk dan dari riwayat Donasi saya milik donatur. Catatan di Dashboard kas TIDAK diubah, jadi saldo tetap dan transaksinya masih tampil. Tindakan ini tidak bisa dibatalkan.') ?>">Hapus semua</button>
   <button class="btn o sm bahaya" type="submit" name="batal_kas" value="1"<?= (int)$ring['n'] === 0 ? ' disabled' : '' ?> data-konfirmasi="<?= e('Hapus SEMUA ' . (int)$ring['n'] . ' donasi berhasil (total ' . rupiah($ring['total']) . ') DAN batalkan catatan kas-nya? Saldo kas berkurang sebesar total donasi yang kasnya masih aktif. Catatan kas tetap tersimpan dengan tanda void di Dashboard kas, tetapi data donasinya hilang dan tidak bisa dikembalikan.') ?>">Hapus semua dan batalkan kas</button>
  </form>
  <?php endif; ?>
 </div>

 <div class="card">
  <h2 class="sub">Daftar data <small><?= $jml ?> data</small></h2>
  <?php if ($bolehHapus && (int)$yatimInfo['n'] > 0): ?>
  <div class="peringatan">
   Ada <b><?= (int)$yatimInfo['n'] ?> catatan kas</b> dari donasi online (total <b><?= rupiah($yatimInfo['total']) ?></b>) yang data donasinya sudah dihapus, tetapi masih dihitung di saldo dan tampil di Dashboard kas.
   <form method="post" style="margin-top:.6rem">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="batalkan_yatim">
    <button class="btn o sm bahaya" type="submit" data-konfirmasi="<?= e('Batalkan (void) ' . (int)$yatimInfo['n'] . ' catatan kas senilai ' . rupiah($yatimInfo['total']) . '? Saldo kas berkurang sebesar itu. Catatan tetap tersimpan dengan tanda void di Dashboard kas.') ?>">Batalkan catatan kas ini (void)</button>
   </form>
  </div>
  <?php endif; ?>
  <div class="scroll">
   <table class="tbl">
    <thead><tr><th>No</th><th>Tanggal</th><th>Donatur</th><th>Kontak</th><th>Program dan paket</th><th>Metode</th><th class="r">Nominal</th><?php if ($bolehHapus): ?><th class="r">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$daftar): ?>
     <tr><td colspan="<?= $bolehHapus ? 8 : 7 ?>"><div class="empty"><b>Belum ada data</b><p>Belum ada donasi berhasil yang cocok dengan pencarian ini.</p></div></td></tr>
    <?php endif; ?>
    <?php foreach ($daftar as $i => $r): $namaMetode = $metode[$r['metode']][0] ?? $r['metode']; ?>
     <tr>
      <td><?= ($hal - 1) * $perHal + $i + 1 ?></td>
      <td class="nw"><?= e(date('d/m/Y', strtotime($r['waktu']))) ?><small><?= e(date('H:i', strtotime($r['waktu']))) ?></small></td>
      <td><b><?= e($r['nama']) ?></b><?= $r['samarkan_nama'] ? ' <i class="cat">disamarkan</i>' : '' ?><small><?= $r['username'] ? '@' . e($r['username']) : 'Tanpa akun' ?></small></td>
      <td><?= e($r['kontak']) ?><?php if ($r['email']): ?><small><?= e($r['email']) ?></small><?php endif; ?><?php if ($r['nip']): ?><small>NIP <?= e($r['nip']) ?></small><?php endif; ?></td>
      <td><b><?= e($r['program_nama']) ?></b><small><?= e($r['paket']) ?></small></td>
      <td class="nw"><?= e($namaMetode) ?><?php if ($r['channel_nama'] && $r['channel_nama'] !== $namaMetode): ?><small><?= e($r['channel_nama']) ?></small><?php endif; ?></td>
      <td class="r nw"><b><?= rupiah($r['jumlah']) ?></b><small><?= e($r['kode']) ?></small><small><?= $r['trx_id'] ? ($r['trx_batal'] ? 'Kas: sudah dibatalkan' : 'Kas: tercatat') : 'Kas: tidak ada' ?></small><?php if ($r['bukti']): ?><small><a class="ul" href="bukti.php?kode=<?= urlencode($r['kode']) ?>" target="_blank" rel="noopener">Lihat bukti</a></small><?php endif; ?></td>
      <?php if ($bolehHapus): ?>
      <td class="r nw">
       <form class="aksi-hapus" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <input type="hidden" name="cari" value="<?= e($cari) ?>">
        <input type="hidden" name="program" value="<?= e($prog) ?>">
        <input type="hidden" name="hal" value="<?= (int)$hal ?>">
        <button class="lnk del" type="submit" data-konfirmasi="<?= e('Hapus donasi ' . $r['kode'] . ' dari ' . $r['nama'] . ' sebesar ' . rupiah($r['jumlah']) . '? Data ini juga hilang dari Donasi masuk dan dari riwayat Donasi saya milik donatur. Catatan di Dashboard kas TIDAK diubah, saldo tetap. Tindakan ini tidak bisa dibatalkan.') ?>">Hapus data</button>
        <?php if ($r['trx_id'] && !$r['trx_batal']): ?>
        <button class="lnk del" type="submit" name="batal_kas" value="1" data-konfirmasi="<?= e('Hapus donasi ' . $r['kode'] . ' dari ' . $r['nama'] . ' DAN batalkan catatan kas-nya sebesar ' . rupiah($r['jumlah']) . '? Saldo kas berkurang sebesar itu. Catatan kas tetap tersimpan dengan tanda void di Dashboard kas, tetapi data donasinya hilang dan tidak bisa dikembalikan.') ?>">Hapus dan batalkan kas</button>
        <?php endif; ?>
       </form>
      </td>
      <?php endif; ?>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if ($totalHal > 1): ?>
  <div class="pg">
   <small>Halaman <?= $hal ?> dari <?= $totalHal ?></small>
   <div>
    <?php if ($hal > 1): ?><a href="?<?= e(http_build_query($qs + ['hal' => $hal - 1])) ?>">←</a><?php endif; ?>
    <?php for ($i = max(1, $hal - 2); $i <= min($totalHal, $hal + 2); $i++): ?>
    <a class="<?= $i === $hal ? 'on' : '' ?>" href="?<?= e(http_build_query($qs + ['hal' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($hal < $totalHal): ?><a href="?<?= e(http_build_query($qs + ['hal' => $hal + 1])) ?>">→</a><?php endif; ?>
   </div>
  </div>
  <?php endif; ?>
 </div>
</section>

<?php include __DIR__ . '/php/app_footer.php'; ?>
