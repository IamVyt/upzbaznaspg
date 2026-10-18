<?php
// Dropdown akun di navigasi atas setelah login: nama pengguna, Pengaturan akun, dan Keluar.
// Dipakai bersama oleh php/header.php (beranda) dan php/app_header.php (halaman sistem). Dibuka lewat js/main.js
$namaLengkap = (string)($_SESSION['nama'] ?? 'Akun');
$namaDepan   = strtok($namaLengkap, ' ') ?: 'Akun';
$inisial     = mb_strtoupper(mb_substr($namaDepan, 0, 1));
?>
<li class="akun">
 <button class="btn o akun-btn" type="button" aria-expanded="false" aria-controls="menu-akun">
  <span class="av" aria-hidden="true"><?= e($inisial) ?></span><?= e($namaDepan) ?>
 </button>
 <div class="akun-menu" id="menu-akun" hidden>
  <div class="akun-info"><b><?= e($namaLengkap) ?></b><small>@<?= e($_SESSION['username'] ?? '') ?></small></div>
  <a href="akun.php"><svg><use href="#i-user"/></svg>Pengaturan akun</a>
  <a class="keluar" href="logout.php"><svg><use href="#i-out"/></svg>Keluar</a>
 </div>
</li>
