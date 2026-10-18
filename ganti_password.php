<?php
// Ganti password sudah digabung ke halaman Pengaturan akun. File ini dibiarkan agar tautan lama tetap berfungsi
require __DIR__ . '/php/fungsi.php';
wajib_login();
redirect('akun.php#password');
