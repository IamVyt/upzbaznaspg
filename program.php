<?php
// Program dan kategori.
// Daftar program diambil dari php/data.php, sumber yang sama dengan bagian Program di beranda dan halaman Salurkan,
// jadi selalu sama dengan yang dilihat donatur. Halaman ini hanya untuk melihat, bukan untuk mengubah.
require __DIR__ . '/php/fungsi.php';
require __DIR__ . '/php/data.php';
wajib_peran(['admin', 'bendahara', 'ketua']);
siapkan_donasi($conn);

// Nama kategori kas yang aktif, untuk menunjukkan program ini tercatat ke kategori apa saat donasinya dikonfirmasi
$kat = [];
foreach (q_all($conn, "SELECT kode, nama FROM kategori_dana WHERE tipe = 'masuk' AND aktif = 1") as $k) $kat[$k['kode']] = $k['nama'];

// Donasi yang sudah tersalurkan per program
$stat = [];
foreach (q_all($conn, "SELECT program, COUNT(*) AS n, COALESCE(SUM(jumlah), 0) AS total FROM donasi WHERE status = 'diterima' GROUP BY program") as $r) {
    $stat[$r['program']] = $r;
}

$title = 'Program dan kategori';
$aktif = 'program';
include __DIR__ . '/php/app_header.php';
?>

<section class="top">
 <div class="head"><h1 class="tag">Program dan kategori</h1><p>Program penyaluran yang sama dengan bagian Program di beranda situs, lengkap dengan kategori kas dan donasi yang sudah tersalurkan.</p></div>

 <div class="card">
  <h2 class="sub">Daftar data <small><?= count($program) ?> program, sama dengan beranda situs. <a class="ul" href="index.php#program">Lihat di beranda</a></small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead><tr><th>No</th><th>Program</th><th>Keterangan</th><th>Kategori kas</th><th>Paket</th><th class="r">Donasi tersalurkan</th></tr></thead>
    <tbody>
    <?php if (!$program): ?><tr><td colspan="6"><div class="empty"><b>Belum ada data</b><p>Daftar program belum tersedia.</p></div></td></tr><?php endif; ?>
    <?php foreach ($program as $i => $p): $s = slug($p[0]); $kode = $penyaluran[$s]['kategori'] ?? ''; $st = $stat[$s] ?? null; ?>
     <tr>
      <td><?= $i + 1 ?></td>
      <td><b><?= e($p[0]) ?></b></td>
      <td><span class="meta"><small title="<?= e($p[2]) ?>"><?= e($p[2]) ?></small></span></td>
      <td class="nw"><?php if (isset($kat[$kode])): ?><i class="cat"><?= e($kat[$kode]) ?></i><?php else: ?><span class="badge void" title="Kategori tidak ditemukan atau nonaktif, sehingga donasi program ini belum bisa dikonfirmasi ke kas.">Belum ada kategori</span><?php endif; ?></td>
      <td class="nw"><?= count($penyaluran[$s]['paket'] ?? []) ?> paket</td>
      <td class="r nw"><b><?= rupiah($st['total'] ?? 0) ?></b><small><?= (int)($st['n'] ?? 0) ?> donasi</small></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
 </div>
</section>

<?php include __DIR__ . '/php/app_footer.php'; ?>
