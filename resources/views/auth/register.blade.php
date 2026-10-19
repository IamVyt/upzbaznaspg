@extends('layouts.portal')

@section('title', 'Daftar Akun Baru')

@section('content')
<section class="auth daftar">
    <div class="c g">
        <h1>Mari berbagi kebaikan bersama</h1>
        <p>Buat akun untuk mengikuti program zakat, infak, dan sedekah UPZ BAZNAS Petrokimia Gresik secara mudah dan terpantau.</p>
        <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
    </div>

    <form class="c l form" method="POST" action="{{ route('register') }}" autocomplete="off">
        @csrf
        <h2><span class="tg">Daftar</span></h2>
        <p>Isi data berikut untuk membuat akun donatur.</p>

        <div class="kolom2">
            <div>
                <label for="name">Nama lengkap</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" maxlength="100" required autofocus placeholder="Nama lengkap Anda">

                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" maxlength="30" required placeholder="Contoh: ahmad_pkg">

                <label for="email">Alamat Email (opsional)</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="100" placeholder="nama@email.com">
            </div>
            <div>
                <label for="no_hp">No. WhatsApp / HP</label>
                <input id="no_hp" type="text" name="no_hp" value="{{ old('no_hp') }}" maxlength="30" required placeholder="08xxxxxxxxxx">

                <label for="nip">NIP / Nomor Pegawai (opsional)</label>
                <input id="nip" type="text" name="nip" value="{{ old('nip') }}" maxlength="30" placeholder="Bila insan Petrokimia Gresik">

                <label for="password">Password</label>
                <div class="pw">
                    <input id="password" type="password" name="password" minlength="6" required>
                    <button type="button" data-pw="password" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
                </div>

                <label for="password_confirmation">Ulangi password</label>
                <div class="pw">
                    <input id="password_confirmation" type="password" name="password_confirmation" minlength="6" required>
                    <button type="button" data-pw="password_confirmation" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
                </div>
            </div>
        </div>

        <button class="btn" type="submit" style="margin-top: 10px;">Buat akun</button>
        <small style="display: block; margin-top: 16px;">Sudah punya akun? <a class="ul" href="{{ route('login') }}">Login di sini</a></small>
    </form>
</section>
@endsection
