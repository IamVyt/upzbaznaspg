@extends('layouts.portal')

@section('title', 'Daftar')

@section('content')
<section class="auth daftar">
 <div class="c g">
  <h1>Mari berbagi kebaikan bersama</h1>
  <p>Buat akun untuk mengikuti program zakat, infak, dan sedekah UPZ BAZNAS Petrokimia Gresik.</p>
  <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
 </div>

 <form class="c l form" method="post" action="{{ route('register') }}" autocomplete="off">
  <h2><span class="tg">Daftar</span></h2>
  <p>Isi data berikut untuk membuat akun.</p>
  @if ($errors->any())
  <div class="alert danger" role="alert">{{ implode(' ', $errors->all()) }}</div>
  @endif
  @csrf
  <div class="kolom2">
   <div>
    <label for="name">Nama lengkap</label>
    <input id="name" type="text" name="name" value="{{ old('name') }}" maxlength="100" required autofocus>
    <label for="username">Username</label>
    <input id="username" type="text" name="username" value="{{ old('username') }}" maxlength="30" required>
   </div>
   <div>
    <label for="password">Password</label>
    <div class="pw">
     <input id="password" type="password" name="password" minlength="8" required>
     <button type="button" data-pw="password" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
    </div>
    <label for="ulang">Ulangi password</label>
    <div class="pw">
     <input id="ulang" type="password" name="password_confirmation" minlength="8" required>
     <button type="button" data-pw="ulang" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
    </div>
   </div>
  </div>
  <button class="btn" type="submit">Buat akun</button>
  <small>Sudah punya akun? <a class="ul" href="{{ route('login') }}">Login di sini</a></small>
 </form>
</section>
@endsection