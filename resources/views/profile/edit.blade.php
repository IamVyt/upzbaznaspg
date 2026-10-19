@extends(auth()->user()->isStaff() ? 'layouts.app' : 'layouts.portal')

@section('title', 'Pengaturan Akun')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Pengaturan akun</h1>
        <p>Perbarui profil dan password Anda. Data profil juga dipakai untuk mengisi form donasi secara otomatis.</p>
    </div>

    <div class="akun-grid">
        <form class="card" id="profil" method="POST" action="{{ route('profile.update') }}" autocomplete="off">
            @csrf
            @method('PUT')
            <h2 class="sub">Profil <small>Peran: {{ ucfirst($user->role) }}</small></h2>

            <div class="kolom2">
                <div>
                    <label for="p-nama">Nama lengkap</label>
                    <input id="p-nama" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required>
                </div>
                <div>
                    <label for="p-username">Username</label>
                    <input id="p-username" type="text" name="username" value="{{ old('username', $user->username) }}" maxlength="50" required>
                </div>
            </div>
            <small class="bantu">Username dipakai untuk login ke sistem.</small>

            <div class="kolom2">
                <div>
                    <label for="p-email">Email (opsional)</label>
                    <input id="p-email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="100">
                </div>
                <div>
                    <label for="p-hp">WhatsApp atau telepon</label>
                    <input id="p-hp" type="tel" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" inputmode="tel" maxlength="30" placeholder="08xxxxxxxxxx" required>
                </div>
            </div>

            <label for="p-nip">NIP atau ID karyawan (opsional)</label>
            <input id="p-nip" type="text" name="nip" value="{{ old('nip', $user->nip) }}" maxlength="30" placeholder="Bila insan Petrokimia Gresik">

            <button class="btn" type="submit" style="margin-top: 14px;">Simpan profil</button>
        </form>

        <form class="card" id="password" method="POST" action="{{ route('profile.password') }}" autocomplete="off">
            @csrf
            @method('PUT')
            <h2 class="sub">Ganti password <small>Minimal 6 karakter</small></h2>

            <label for="current_password">Password saat ini</label>
            <div class="pw">
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                <button type="button" data-pw="current_password" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
            </div>

            <label for="new_password">Password baru</label>
            <div class="pw">
                <input id="new_password" type="password" name="password" required autocomplete="new-password">
                <button type="button" data-pw="new_password" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
            </div>

            <label for="password_confirmation">Ulangi password baru</label>
            <div class="pw">
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                <button type="button" data-pw="password_confirmation" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
            </div>

            <button class="btn" type="submit" style="margin-top: 14px;">Simpan password</button>
        </form>
    </div>
</section>
@endsection
