@extends('layouts.portal')

@section('title', 'Login')

@section('content')
<section class="auth">
 <div class="c g">
  <h1>Selamat datang di UPZ BAZNAS</h1>
  <p>Layanan pencatatan dan pengelolaan dana zakat, infak, dan sedekah Petrokimia Gresik yang transparan dan amanah.</p>
  <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
 </div>

 <form class="c l form" method="post" action="{{ route('login') }}" autocomplete="off">
  <h2><span class="tg">Masuk</span></h2>
  <p>Masukkan username dan kata sandi Anda.</p>
  @if ($errors->any())
  <div class="alert danger" role="alert">{{ $errors->first() }}</div>
  @endif
  @csrf
  <label for="username">Username</label>
  <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus>
  <label for="password">Password</label>
  <div class="pw">
   <input id="password" type="password" name="password" required>
   <button type="button" data-pw="password" aria-label="Tampilkan password"><svg><use href="#eye"/></svg></button>
  </div>
  <small><a class="ul" href="{{ route('password.request') }}">Lupa password?</a></small>
  <button class="btn" type="submit">Login</button>
  <small>Belum punya akun? <a class="ul" href="{{ route('register') }}">Daftar sekarang</a></small>
 </form>
</section>
@endsection