<?php
// Data penyaluran: ringkasan donasi yang sudah tersalurkan per jenis penyaluran (program),
// lengkap dengan riwayat siapa yang berdonasi dan donasi apa. Hanya donasi berstatus Sudah tersalurkan yang dihitung.
require __DIR__ . '/php/fungsi.php';
require __DIR__ . '/php/data.php';
wajib_staff();
siapkan_donasi($conn);

if (!function_exists('slug')) {
    function slug($teks) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$teks)), '-'); }
}

// Ringkasan per program (kunci: slug program)
$ringkas = [];
foreach (q_all($conn, "SELECT program, MAX(program_nama) AS program_nama,
        SUM(status = 'diterima') AS n,
        COALESCE(SUM(CASE WHEN status = 'diterima' THEN jumlah END), 0) AS total,
        COUNT(DISTINCT CASE WHEN status = 'diterima' THEN COALESCE(user_id, nama) END) AS donatur,
        SUM(status IN ('menunggu', 'dibayar')) AS proses,
        MAX(CASE WHEN status = 'diterima' THEN COALESCE(diproses_at, dibuat_at) END) AS terakhir
    FROM donasi GROUP BY program") as $r) {
    $ringkas[$r['program']] = $r;
}

// Semua program di situs ditampilkan, termasuk yang belum punya donasi
$baris = []; $dikenal = [];
foreach ($program as $p) {
    $s = slug($p[0]);
    $dikenal[$s] = true;
    $baris[] = ['slug' => $s, 'nama' => $p[0], 'ket' => $p[2]] + ($ringkas[$s] ?? []);
}
foreach ($ringkas as $s => $r) {
    if (!isset($dikenal[$s])) $baris[] = ['slug' => $s, 'nama' => $r['program_nama'], 'ket' => 'Program ini sudah tidak ada di daftar program situs.'] + $r;
}

// Riwayat donatur per program (50 terbaru tiap program)
$riwayat = [];
foreach (q_all($conn, "SELECT d.program, d.kode, d.nama, d.samarkan_nama, d.paket, d.metode, d.jumlah,
        COALESCE(d.diproses_at, d.dibuat_at) AS waktu, u.username
    FROM donasi d LEFT JOIN users u ON u.id = d.user_id
    WHERE d.status = 'diterima' ORDER BY waktu DESC, d.id DESC LIMIT 1000") as $r) {
    if (count($riwayat[$r['program']] ?? []) < 50) $riwayat[$r['program']][] = $r;
}

$totN = $totRp = $totProses = 0;
foreach ($ringkas as $r) { $totN += (int)$r['n']; $totRp += (float)$r['total']; $totProses += (int)$r['proses']; }
$totDonatur = (int)(q_one($conn, "SELECT COUNT(DISTINCT COALESCE(user_id, nama)) AS n FROM donasi WHERE status = 'diterima'")['n'] ?? 0);

$title = 'Data penyaluran';
$aktif = 'penyaluran';
include __DIR__ . '/php/app_header.php';
?>

<section class="top">
 <div class="head"><h1 class="tag">Data penyaluran</h1><p>Ringkasan donasi yang sudah tersalurkan untuk tiap jenis penyaluran. Klik Lihat riwayat untuk mengetahui siapa yang berdonasi dan donasi apa.</p></div>

 <div class="grid4">
  <div class="c g stat"><small>Total donasi tersalurkan</small><b><?= rupiah($totRp) ?></b><small>Sudah dikonfirmasi petugas</small></div>
  <div class="c l stat"><small>Jumlah donasi</small><b><?= $totN ?> donasi</b><small>Berstatus sudah tersalurkan</small></div>
  <div class="c l stat"><small>Jumlah donatur</small><b><?= $totDonatur ?> orang</b><small>Donatur berbeda</small></div>
  <div class="c d stat"><small>Masih diproses</small><b><?= $totProses ?> donasi</b><small>Menunggu bayar atau dicek</small></div>
 </div>

 <div class="card" style="margin-top:1.6rem">
  <h2 class="sub">Daftar data <small><?= count($baris) ?> jenis penyaluran</small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead><tr><th>No</th><th>Jenis penyaluran</th><th>Riwayat</th><th>Keterangan</th><th class="r">Total donasi</th><th class="r">Aksi</th></tr></thead>
    <tbody>
    <?php if (!$baris): ?><tr><td colspan="6"><div class="empty"><b>Belum ada data</b><p>Daftar program belum tersedia.</p></div></td></tr><?php endif; ?>
    <?php foreach ($baris as $i => $r): $n = (int)($r['n'] ?? 0); ?>
     <tr>
      <td><?= $i + 1 ?></td>
      <td><b><?= e($r['nama']) ?></b></td>
      <td class="nw"><?= $n > 0 ? '<b>' . $n . ' donasi</b><small>' . (int)$r['donatur'] . ' donatur' . ($r['terakhir'] ? ', terakhir ' . e(date('d/m/Y', strtotime($r['terakhir']))) : '') . '</small>' : 'Belum ada donasi' ?>
       <?php if ((int)($r['proses'] ?? 0) > 0): ?><small><?= (int)$r['proses'] ?> masih diproses</small><?php endif; ?></td>
      <td><span class="meta"><small title="<?= e($r['ket']) ?>"><?= e($r['ket']) ?></small></span></td>
      <td class="r nw"><b><?= rupiah($r['total'] ?? 0) ?></b></td>
      <td class="r nw"><button class="lnk" type="button" data-dialog="rw-<?= $i ?>"<?= $n === 0 ? ' disabled' : '' ?>>Lihat riwayat</button></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
 </div>
</section>

<?php foreach ($baris as $i => $r): $daftar = $riwayat[$r['slug']] ?? []; if (!$daftar) continue; ?>
<dialog class="dlg lebar" id="rw-<?= $i ?>" aria-labelledby="rw-j<?= $i ?>">
 <div class="dbox">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="rw-j<?= $i ?>"><span><?= e($r['nama']) ?></span></h3>
  <p><?= (int)$r['n'] ?> donasi dari <?= (int)$r['donatur'] ?> donatur, total <b><?= rupiah($r['total']) ?></b>.<?= (int)$r['n'] > count($daftar) ? ' Menampilkan ' . count($daftar) . ' donasi terbaru.' : '' ?></p>
  <div class="scroll"><table class="tbl">
   <thead><tr><th>Tanggal</th><th>Donatur</th><th>Donasi apa</th><th class="r">Nominal</th></tr></thead>
   <tbody>
   <?php foreach ($daftar as $d): ?>
    <tr>
     <td class="nw"><?= e(date('d/m/Y', strtotime($d['waktu']))) ?></td>
     <td><b><?= e($d['nama']) ?></b><?= $d['samarkan_nama'] ? ' <i class="cat">disamarkan</i>' : '' ?><small><?= $d['username'] ? '@' . e($d['username']) : 'Tanpa akun' ?>, <?= e($d['kode']) ?></small></td>
     <td><?= e($d['paket']) ?><small><?= e($metode[$d['metode']][0] ?? $d['metode']) ?></small></td>
     <td class="r nw"><b><?= rupiah($d['jumlah']) ?></b></td>
    </tr>
   <?php endforeach; ?>
   </tbody>
  </table></div>
  <div class="acts end"><button class="btn o" type="button" data-close>Tutup</button></div>
 </div>
</dialog>
<?php endforeach; ?>

<?php include __DIR__ . '/php/app_footer.php'; ?>
