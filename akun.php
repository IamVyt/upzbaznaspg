<?php
// Pengaturan akun: ubah profil dan ganti password. Bisa dipakai semua peran yang sudah masuk (admin, bendahara, ketua, user)
require __DIR__ . '/php/fungsi.php';
wajib_login();
siapkan_profil($conn);

$userId = (int)($_SESSION['user_id'] ?? 0);
$user = q_one($conn, "SELECT id, nama_lengkap, username, password, role, email, no_hp, nip FROM users WHERE id = ?", 'i', [$userId]);
if (!$user) redirect('logout.php');   // akun sudah dihapus admin

$labelPeran = ['admin' => 'Admin', 'bendahara' => 'Bendahara', 'ketua' => 'Ketua', 'user' => 'User (donatur)'];

// Nilai awal form profil diambil dari database
$isi = [
    'nama'     => (string)$user['nama_lengkap'],
    'username' => (string)$user['username'],
    'email'    => (string)$user['email'],
    'no_hp'    => (string)$user['no_hp'],
    'nip'      => (string)$user['nip'],
];
$galatProfil = [];
$galatSandi  = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $aksi = (string)($_POST['aksi'] ?? '');

    if (!csrf_valid()) {
        if ($aksi === 'password') $galatSandi = 'Sesi formulir kedaluwarsa. Silakan ulangi.';
        else $galatProfil[] = 'Sesi formulir kedaluwarsa. Silakan ulangi.';

    } elseif ($aksi === 'profil') {
        foreach (array_keys($isi) as $k) {
            $v = $_POST[$k] ?? '';
            $isi[$k] = is_string($v) ? trim($v) : '';
        }
        if (mb_strlen($isi['nama']) < 2 || mb_strlen($isi['nama']) > 100) $galatProfil[] = 'Nama lengkap harus 2 sampai 100 karakter.';
        if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $isi['username'])) $galatProfil[] = 'Username 4 sampai 30 karakter, hanya huruf, angka, titik, dan garis bawah.';
        if ($isi['email'] !== '' && (mb_strlen($isi['email']) > 100 || !filter_var($isi['email'], FILTER_VALIDATE_EMAIL))) $galatProfil[] = 'Format email tidak valid.';
        if ($isi['no_hp'] !== '' && !preg_match('/^\+?[0-9][0-9\s\-]{6,18}[0-9]$/', $isi['no_hp'])) $galatProfil[] = 'Nomor WhatsApp atau telepon tidak valid. Contoh: 081234567890.';
        if ($isi['nip'] !== '' && !preg_match('/^[A-Za-z0-9.\-\/ ]{1,30}$/', $isi['nip'])) $galatProfil[] = 'NIP atau ID karyawan hanya boleh berisi huruf, angka, titik, garis miring, dan strip (maksimal 30 karakter).';
        if (!$galatProfil && q_one($conn, "SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id <> ?", 'si', [$isi['username'], $userId])) {
            $galatProfil[] = 'Username sudah dipakai. Silakan pilih yang lain.';
        }

        if (!$galatProfil) {
            // Hanya kolom yang benar-benar berubah yang dicatat ke riwayat aktivitas
            $sebelum = ['nama_lengkap' => (string)$user['nama_lengkap'], 'username' => (string)$user['username'],
                        'email' => (string)$user['email'], 'no_hp' => (string)$user['no_hp'], 'nip' => (string)$user['nip']];
            $sesudah = ['nama_lengkap' => $isi['nama'], 'username' => $isi['username'],
                        'email' => $isi['email'], 'no_hp' => $isi['no_hp'], 'nip' => $isi['nip']];
            $lama = []; $baru = [];
            foreach ($sesudah as $k => $v) {
                if ($sebelum[$k] !== $v) { $lama[$k] = $sebelum[$k]; $baru[$k] = $v; }
            }

            if (!$baru) {
                flash('info', 'Tidak ada perubahan pada profil Anda.');
                redirect('akun.php');
            }

            q($conn, "UPDATE users SET nama_lengkap = ?, username = ?, email = ?, no_hp = ?, nip = ? WHERE id = ?",
                'sssssi', [$isi['nama'], $isi['username'], $isi['email'] !== '' ? $isi['email'] : null,
                           $isi['no_hp'] !== '' ? $isi['no_hp'] : null, $isi['nip'] !== '' ? $isi['nip'] : null, $userId]);
            catat_audit($conn, 'ubah_profil', 'users', $userId, $lama, $baru);
            $_SESSION['nama']     = $isi['nama'];
            $_SESSION['username'] = $isi['username'];
            flash('success', 'Profil berhasil diperbarui.');
            redirect('akun.php');
        }

    } elseif ($aksi === 'password') {
        $lama  = (string)($_POST['lama'] ?? '');
        $baru  = (string)($_POST['baru'] ?? '');
        $ulang = (string)($_POST['ulang'] ?? '');

        if (!password_verify($lama, (string)$user['password'])) {
            $galatSandi = 'Password lama tidak sesuai.';
        } elseif (strlen($baru) < 8) {
            $galatSandi = 'Password baru minimal 8 karakter.';
        } elseif ($baru !== $ulang) {
            $galatSandi = 'Konfirmasi password baru tidak sama.';
        } elseif ($baru === $lama) {
            $galatSandi = 'Password baru harus berbeda dari password lama.';
        } else {
            q($conn, "UPDATE users SET password = ? WHERE id = ?", 'si', [password_hash($baru, PASSWORD_DEFAULT), $userId]);
            catat_audit($conn, 'ganti_password', 'users', $userId);
            flash('success', 'Password berhasil diganti.');
            redirect('akun.php');
        }
    }
}

$title = 'Pengaturan akun';
$aktif = 'akun';
include __DIR__ . '/php/app_header.php';
?>

<section class="top">
 <div class="head"><h1 class="tag">Pengaturan akun</h1><p>Perbarui profil dan password Anda. Data profil juga dipakai untuk mengisi form donasi secara otomatis.</p></div>

 <div class="akun-grid">
  <form class="card" id="profil" method="post" action="akun.php#profil" autocomplete="off">
   <h2 class="sub">Profil <small>Peran: <?= e($labelPeran[$user['role']] ?? $user['role']) ?></small></h2>
   <?php if ($galatProfil): ?><div class="alert danger" role="alert"><?= e(implode(' ', $galatProfil)) ?></div><?php endif; ?>
   <?= csrf_field() ?>
   <input type="hidden" name="aksi" value="profil">

   <div class="kolom2">
    <div>
     <label for="p-nama">Nama lengkap</label>
     <input id="p-nama" type="text" name="nama" value="<?= e($isi['nama']) ?>" maxlength="100" required>
    </div>
    <div>
     <label for="p-username">Username</label>
     <input id="p-username" type="text" name="username" value="<?= e($isi['username']) ?>" maxlength="30" required>
    </div>
   </div>
   <small class="bantu">Username dipakai untuk login. Isi 4 sampai 30 karakter, hanya huruf, angka, titik, dan garis bawah.</small>

   <div class="kolom2">
    <div>
     <label for="p-email">Email (opsional)</label>
     <input id="p-email" type="email" name="email" value="<?= e($isi['email']) ?>" maxlength="100">
    </div>
    <div>
     <label for="p-hp">WhatsApp atau telepon (opsional)</label>
     <input id="p-hp" type="tel" name="no_hp" value="<?= e($isi['no_hp']) ?>" inputmode="tel" maxlength="20" placeholder="081234567890">
    </div>
   </div>

   <label for="p-nip">NIP atau ID karyawan (opsional)</label>
   <input id="p-nip" type="text" name="nip" value="<?= e($isi['nip']) ?>" maxlength="30">

   <button class="btn" type="submit">Simpan profil</button>
  </form>

  <form class="card" id="password" method="post" action="akun.php#password" autocomplete="off">
   <h2 class="sub">Ganti password <small>Minimal 8 karakter</small></h2>
   <?php if ($galatSandi !== ''): ?><div class="alert danger" role="alert"><?= e($galatSandi) ?></div><?php endif; ?>
   <?= csrf_field() ?>
   <input type="hidden" name="aksi" value="password">
   <?php foreach (['lama' => ['Password lama', 'current-password'], 'baru' => ['Password baru', 'new-password'], 'ulang' => ['Ulangi password baru', 'new-password']] as $n => [$lbl, $ac]): ?>
   <label for="<?= $n ?>"><?= $lbl ?></label>
   <div class="pw"><input id="<?= $n ?>" type="password" name="<?= $n ?>" required autocomplete="<?= $ac ?>"><button type="button" data-pw="<?= $n ?>" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button></div>
   <?php endforeach; ?>
   <button class="btn" type="submit">Simpan password</button>
  </form>
 </div>
</section>

<?php include __DIR__ . '/php/app_footer.php'; ?>
