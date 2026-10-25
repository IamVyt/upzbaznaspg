@extends('layouts.portal')

@section('title', 'Lupa password')

@section('content')
<section class="auth">
 <div class="c g">
  <h1>Pulihkan akses akun Anda</h1>
  <p>Masukkan email yang terdaftar pada akun UPZ BAZNAS. Kami akan mengirim link untuk membuat password baru.</p>
  <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
 </div>

 <form class="c l form" method="post" action="{{ route('password.email') }}">
  <h2><span class="tg">Lupa password</span></h2>
  <p>Gunakan email yang sudah diverifikasi. Link reset berlaku selama 60 menit dan hanya bisa dipakai satu kali.</p>
  @csrf
  <label for="email">Email terdaftar</label>
  <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="100" autocomplete="email" required autofocus>
  <button class="btn" type="submit">Kirim link reset</button>
  <small>Ingat password? <a class="ul" href="{{ route('login') }}">Kembali ke login</a></small>
  <small>Email belum diverifikasi? Masuk ke akun Anda, lalu kirim ulang link verifikasi dari halaman verifikasi.</small>
  <small>Akun belum punya email? Hubungi pengurus UPZ untuk reset manual.</small>
 </form>
</section>
@endsection
