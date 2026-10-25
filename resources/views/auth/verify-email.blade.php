@extends('layouts.portal')

@section('title', 'Verifikasi email')

@section('content')
<section class="auth">
 <div class="c g">
  <h1>Satu langkah lagi</h1>
  <p>Verifikasi email membantu kami memastikan link reset password hanya dikirim ke pemilik akun yang sah.</p>
  <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
 </div>

 <div class="c l form">
  <h2><span class="tg">Verifikasi email</span></h2>

  @if (auth()->user()->email)
   <p>Kami mengirim link verifikasi ke <b>{{ auth()->user()->email }}</b>. Buka email tersebut dan klik tombol verifikasi.</p>
   <p>Link berlaku selama 60 menit. Jika belum menerima email, periksa folder spam atau kirim ulang.</p>

   <form action="{{ route('verification.send') }}" method="POST">
    @csrf
    <button class="btn" type="submit">Kirim ulang email verifikasi</button>
   </form>

   <small>Email salah? <a class="ul" href="{{ route('profile.edit') }}">Ubah email di pengaturan akun</a>.</small>
  @else
   <p>Akun Anda belum memiliki alamat email. Tambahkan email terlebih dahulu agar bisa menerima link verifikasi dan reset password.</p>
   <a class="btn" href="{{ route('profile.edit') }}">Tambahkan email</a>
  @endif

  <form action="{{ route('logout') }}" method="POST" style="margin-top: 15px;">
   @csrf
   <button class="btn o" type="submit">Keluar dari akun</button>
  </form>
 </div>
</section>
@endsection
