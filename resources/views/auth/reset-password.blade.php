@extends('layouts.portal')

@section('title', 'Password baru')

@section('content')
<section class="auth">
 <div class="c g">
  <h1>Buat password baru</h1>
  <p>Password baru sebaiknya unik, tidak dipakai di layanan lain, dan minimal 8 karakter.</p>
  <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
 </div>

 <form class="c l form" method="post" action="{{ route('password.update') }}" autocomplete="off">
  <h2><span class="tg">Password baru</span></h2>
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">

  <label for="email">Email akun</label>
  <input id="email" type="email" name="email" value="{{ old('email', $email) }}" maxlength="100" autocomplete="username" required>

  <label for="password">Password baru</label>
  <div class="pw">
   <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required autofocus>
   <button type="button" data-pw="password" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
  </div>

  <label for="password_confirmation">Ulangi password baru</label>
  <div class="pw">
   <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
   <button type="button" data-pw="password_confirmation" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
  </div>

  <button class="btn" type="submit">Simpan password baru</button>
  <small>Sudah ingat password? <a class="ul" href="{{ route('login') }}">Kembali ke login</a></small>
 </form>
</section>
@endsection
