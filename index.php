<?php
// Beranda tetap boleh tampil walau database mati (lihat php/koneksi.php), daftar donatur terbaru saja yang kosong
define('KONEKSI_BOLEH_GAGAL', true);
require __DIR__ . '/php/fungsi.php';
require __DIR__ . '/php/data.php';

// Donatur yang bersedia ditampilkan. Kalau jumlahnya kurang dari $minDonatur, section ini memakai testimoni statis dari php/data.php
$minDonatur = 3;   // ubah jadi 1 saat mencoba, supaya satu donatur saja sudah cukup untuk tampil
$donatur = $conn ? donatur_beranda($conn, 10) : [];
$pakaiDonatur = count($donatur) >= $minDonatur;
if ($pakaiDonatur) {
    $judulBagian = 'Donatur terbaru';
    $deskBagian  = 'Mereka yang baru saja menunaikan zakat, infak, dan sedekah melalui UPZ. Yang ditampilkan hanya nama, program, dan waktu, tanpa nominal.';
    $namaKartu   = 'Donatur';
    $kartu = array_map(fn($d) => [
        $d['doa'] !== '' ? '“' . $d['doa'] . '”' : 'Berdonasi untuk program ' . $d['program'] . '.',
        $d['nama'],
        $d['program'] . ' · ' . $d['waktu'],
    ], $donatur);
} else {
    $judulBagian = 'Testimoni';
    $deskBagian  = 'Cerita dari donatur dan penerima manfaat tentang pengalaman bersama UPZ.';
    $namaKartu   = 'Testimoni';
    $kartu       = $testimoni;
}
$title = $site['lengkap'];
include __DIR__ . '/php/header.php';
?>

<header class="hero" id="tentang">
 <div>
  <h1>Menebar kebaikan untuk sesama</h1>
  <p><?= e($site['lengkap']) ?> membantu insan perusahaan menunaikan zakat, infak, dan sedekah secara mudah, amanah, dan tepat sasaran untuk masyarakat yang membutuhkan.</p>
  <a class="btn" href="salurkan.php">Mulai berbagi</a>
 </div>
 <svg viewBox="0 0 500 440" fill="none" stroke="#191A23" stroke-width="2" role="img" aria-label="Ilustrasi hati dengan orbit">
  <g style="stroke:var(--ink)"><ellipse cx="250" cy="230" rx="200" ry="70" transform="rotate(-20 250 230)"/><ellipse cx="250" cy="230" rx="200" ry="70" transform="rotate(20 250 230)"/><ellipse cx="250" cy="230" rx="150" ry="150"/></g>
  <path d="M250 330C130 250 150 150 225 160C242 163 250 178 250 178C250 178 258 163 275 160C350 150 370 250 250 330Z" fill="#B9FF66" stroke="#191A23" stroke-width="3"/>
  <circle cx="110" cy="105" r="16" fill="#191A23"/><circle cx="395" cy="95" r="26" fill="#B9FF66" stroke="#191A23"/><circle cx="420" cy="320" r="10" fill="#B9FF66" stroke="#191A23"/>
  <use href="#st" x="60" y="290" width="60" height="60" fill="#191A23" stroke="none"/><use href="#st" x="330" y="360" width="34" height="34" fill="#191A23" stroke="none"/>
 </svg>
</header>

<?php $ulang = (int) ceil(12 / max(1, count($mitra))); // supaya strip selalu cukup panjang ?>
<div class="mq" role="group" aria-label="Mitra dan pendukung">
 <div class="mq-lintas">
  <?php for ($g = 0; $g < 2; $g++): ?>
  <div class="mq-grup"<?= $g ? ' aria-hidden="true"' : '' ?>>
   <?php for ($k = 0; $k < $ulang; $k++): foreach ($mitra as $nama): ?><span<?= ($g || $k) ? ' aria-hidden="true"' : '' ?>><?= e($nama) ?></span><?php endforeach; endfor; ?>
  </div>
  <?php endfor; ?>
 </div>
</div>

<section id="program">
 <div class="head"><h2 class="tag">Program</h2><p>Kami menyediakan beberapa program untuk membantu insan perusahaan menyalurkan zakat, infak, dan sedekah dengan lebih mudah. Program ini meliputi:</p></div>
 <div class="grid2">
  <?php foreach ($program as $i => [$nama, $warna]): ?>
  <div class="c <?= e($warna) ?>">
   <h3><span><?= e($nama) ?></span></h3>
   <button class="more" type="button" data-dialog="dlg-<?= $i ?>" aria-haspopup="dialog"><i>↗</i>Pelajari</button>
   <svg><use href="#<?= e(ikon_program($nama)) ?>"/></svg>
  </div>
  <?php endforeach; ?>
 </div>
</section>

<section>
 <div class="cta">
  <div>
   <h2>Mari wujudkan kebaikan</h2>
   <p>Hubungi kami untuk mengetahui cara menunaikan zakat, infak, dan sedekah melalui <?= e($site['lengkap']) ?>.</p>
   <a class="btn" href="#kontak">Konsultasi gratis</a>
  </div>
  <svg viewBox="0 0 300 220" fill="none" stroke="var(--ink)" stroke-width="1.5" stroke-linejoin="round" role="img" aria-label="Ilustrasi percakapan dan donasi"><path d="M60 36h120a22 22 0 0 1 22 22v58a22 22 0 0 1-22 22h-62l-34 28v-28H60a22 22 0 0 1-22-22V58a22 22 0 0 1 22-22z" fill="#191A23"/><path d="M120 118C96 103 94 83 107 80c6-1 11 3 13 8 2-5 7-9 13-8 13 3 11 23-13 38z" fill="#B9FF66" stroke="#191A23"/><circle cx="226" cy="150" r="36" fill="#B9FF66" stroke="#191A23"/><circle cx="226" cy="150" r="27" stroke="#191A23"/><use href="#st" x="214" y="138" width="24" height="24" fill="#191A23" stroke="none"/><use href="#st" x="222" y="14" width="44" height="44" fill="#d9d9d9" stroke="none"/><circle cx="262" cy="84" r="6" fill="#191A23" stroke="none"/><path d="M22 160v8M18 164h8"/></svg>
 </div>
</section>

<section id="dampak">
 <div class="head"><h2 class="tag">Dampak</h2><p>Contoh penyaluran dana zakat, infak, dan sedekah yang dikelola secara transparan.</p></div>
 <div class="cs">
  <?php foreach ($dampak as $isi): ?>
  <div><?= e($isi) ?><br><a href="#program">Lihat program ↗</a></div>
  <?php endforeach; ?>
 </div>
</section>

<section id="tim">
 <div class="head"><h2 class="tag">Tim</h2><p>Pengurus UPZ yang mengelola dan menyalurkan amanah donatur.</p></div>
 <div class="team">
  <?php foreach ($tim as [$nama, $jabatan, $deskripsi]): ?>
  <div class="c">
   <div class="p"><svg><use href="#av"/></svg><div><b><?= e($nama) ?></b><small><?= e($jabatan) ?></small></div></div>
   <hr>
   <p><?= e($deskripsi) ?></p>
  </div>
  <?php endforeach; ?>
 </div>
</section>

<section id="alur">
 <div class="head"><h2 class="tag">Alur kerja</h2><p>Langkah demi langkah dari donasi masuk sampai bantuan sampai ke penerima.</p></div>
 <?php foreach ($alur as $i => [$judul, $isi]): ?>
 <details<?= $i === 0 ? ' class="on" open' : '' ?>>
  <summary><b><?= sprintf('%02d', $i + 1) ?></b><?= e($judul) ?></summary>
  <p><?= e($isi) ?></p>
 </details>
 <?php endforeach; ?>
</section>

<section id="donatur">
 <div class="head"><h2 class="tag"><?= e($judulBagian) ?></h2><p><?= e($deskBagian) ?></p></div>
 <div class="tm">
  <div class="sl" id="sl" role="region" aria-label="Daftar <?= e(strtolower($namaKartu)) ?>" tabindex="0">
   <div class="lintas" id="lintas">
   <?php foreach ($kartu as [$isi, $nama, $peran]): ?>
   <figure>
    <blockquote><?= e($isi) ?></blockquote>
    <figcaption><?= e($nama) ?><small><?= e($peran) ?></small></figcaption>
   </figure>
   <?php endforeach; ?>
   </div>
  </div>
  <div class="ctl">
   <button type="button" aria-label="Geser ke kiri" data-geser="-1">←</button>
   <div class="dots"><?php foreach ($kartu as $i => $t): ?><button type="button" aria-label="<?= e($namaKartu) ?> <?= $i + 1 ?>" data-ke="<?= $i ?>"<?= $i === 0 ? ' class="on"' : '' ?>></button><?php endforeach; ?></div>
   <button type="button" aria-label="Geser ke kanan" data-geser="1">→</button>
  </div>
 </div>
</section>

<section id="kontak">
 <div class="head"><h2 class="tag">Kontak</h2><p>Hubungi kami untuk bertanya atau menyalurkan donasi.</p></div>
 <div class="ct">
  <div>
   <div class="rad"><label><input type="radio" name="t" checked>Bertanya</label><label><input type="radio" name="t">Info donasi</label></div>
   <label for="n">Nama</label><input id="n" type="text" placeholder="Nama lengkap">
   <label for="e">Email</label><input id="e" type="email" placeholder="Email">
   <label for="m">Pesan</label><textarea id="m" rows="4" placeholder="Tulis pesan Anda"></textarea>
   <button class="btn" type="button">Kirim pesan</button>
  </div>
  <img class="mas-kontak" src="img/maskot.svg" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" loading="lazy" decoding="async">
 </div>
</section>

<?php foreach ($program as $i => [$nama, $warna, $ringkas, $poin]): ?>
<dialog class="dlg" id="dlg-<?= $i ?>" aria-labelledby="dt-<?= $i ?>">
 <div class="dbox">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="dt-<?= $i ?>"><span><?= e($nama) ?></span></h3>
  <p><?= e($ringkas) ?></p>
  <ul>
   <?php foreach ($poin as $p): ?><li><?= e($p) ?></li>
   <?php endforeach; ?>
  </ul>
  <a class="btn" href="salurkan.php?program=<?= e(slug($nama)) ?>">Salurkan sekarang</a>
 </div>
</dialog>
<?php endforeach; ?>

<?php include __DIR__ . '/php/footer.php'; ?>
