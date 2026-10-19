@extends(auth()->user()->isStaff() ? 'layouts.app' : 'layouts.portal')

@section('title', 'Pengaturan akun')

@section('content')
<section class="top">
 <div class="head"><h1 class="tag">Pengaturan akun</h1><p>Perbarui profil dan password Anda. Data profil juga dipakai untuk mengisi form donasi secara otomatis.</p></div>

 <div class="akun-grid">
  <form class="card" id="profil" method="post" action="{{ route('profile.update') }}" autocomplete="off">
   @csrf
   @method('PUT')
   <h2 class="sub">Profil <small>Peran: {{ ucfirst($user->role) }}</small></h2>
   @if ($errors->any() && !$errors->has('current_password') && !$errors->has('password'))
   <div class="alert danger" role="alert">{{ implode(' ', $errors->all()) }}</div>
   @endif

   <div class="kolom2">
    <div>
     <label for="p-nama">Nama lengkap</label>
     <input id="p-nama" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required>
    </div>
    <div>
     <label for="p-username">Username</label>
     <input id="p-username" type="text" name="username" value="{{ old('username', $user->username) }}" maxlength="30" required>
    </div>
   </div>
   <small class="bantu">Username dipakai untuk login. Isi 4 sampai 30 karakter, hanya huruf, angka, titik, dan garis bawah.</small>

   <div class="kolom2">
    <div>
     <label for="p-email">Email (opsional)</label>
     <input id="p-email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="100">
    </div>
    <div>
     <label for="p-hp">WhatsApp atau telepon (opsional)</label>
     <input id="p-hp" type="tel" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" inputmode="tel" maxlength="20" placeholder="081234567890">
    </div>
   </div>

   <label for="p-nip">NIP atau ID karyawan (opsional)</label>
   <input id="p-nip" type="text" name="nip" value="{{ old('nip', $user->nip) }}" maxlength="30">

   <button class="btn" type="submit">Simpan profil</button>
  </form>

  <form class="card" id="password" method="post" action="{{ route('profile.password') }}" autocomplete="off">
   @csrf
   @method('PUT')
   <h2 class="sub">Ganti password <small>Minimal 8 karakter</small></h2>
   @if ($errors->has('current_password') || $errors->has('password'))
   <div class="alert danger" role="alert">{{ $errors->first('current_password') ?: $errors->first('password') }}</div>
   @endif

   <label for="lama">Password lama</label>
   <div class="pw"><input id="lama" type="password" name="current_password" required autocomplete="current-password"><button type="button" data-pw="lama" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button></div>

   <label for="baru">Password baru</label>
   <div class="pw"><input id="baru" type="password" name="password" required autocomplete="new-password"><button type="button" data-pw="baru" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button></div>

   <label for="ulang">Ulangi password baru</label>
   <div class="pw"><input id="ulang" type="password" name="password_confirmation" required autocomplete="new-password"><button type="button" data-pw="ulang" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button></div>

   <button class="btn" type="submit">Simpan password</button>
  </form>
 </div>
</section>
@endsection