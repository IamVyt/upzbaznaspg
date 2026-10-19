@extends('layouts.portal')

@section('title', 'Isi data donasi')

@section('content')
<section class="top">
 <div class="head"><h1 class="tag">Isi data</h1><p>Lengkapi data berikut. Setelah dikirim, Anda akan mendapat nomor referensi dan petunjuk pembayaran. Metode pembayaran masih bisa diganti sebelum Anda membayar.</p></div>
 <ol class="steps" aria-label="Langkah penyaluran">
  <li class="done"><b>✓</b>Pilih paket</li>
  <li class="on"><b>2</b>Isi data</li>
  <li><b>3</b>Bayar</li>
 </ol>
</section>

<section class="bayar">
 <div class="c g ringkas">
  <small>Paket pilihan Anda</small>
  <h2>{{ $prog['nama'] }}</h2>
  <p class="pk">{{ $namaPaket }}</p>
  <b class="besar">{{ $bebas ? 'Nominal bebas' : rupiah($nominalTetap) }}</b>
  <p>{{ $deskPaket }}</p>
  <a class="ul" href="{{ route('donasi.create', ['program' => $slug]) }}">Ganti paket</a>
 </div>

 <form class="c l form" method="post" action="{{ route('donasi.prosesBayar') }}" autocomplete="off" data-sekali>
  <h2><span class="tg">Data donasi</span></h2>
  @if ($errors->any())
  <div class="alert danger" role="alert">{{ implode(' ', $errors->all()) }}</div>
  @endif
  @csrf
  <input type="hidden" name="program" value="{{ $slug }}">
  <input type="hidden" name="paket" value="{{ $idx }}">

  @php
    $nominalKiri  = $bebas;
    $samarkanKiri = !$bebas || $opsiPaket !== 'hitung';
    $catatanKiri  = !$bebas;
  @endphp

  <div class="dua">
  <div class="kol">
  <label for="nama">Nama lengkap</label>
  <input id="nama" type="text" name="nama" value="{{ $isi['nama'] }}" maxlength="150" required>

  <div class="kolom2">
   <div>
    <label for="kontak">No. WhatsApp/HP</label>
    <input id="kontak" type="tel" name="kontak" value="{{ $isi['kontak'] }}" inputmode="tel" maxlength="20" placeholder="081234567890" required>
   </div>
   <div>
    <label for="nip" title="NIP atau ID karyawan (opsional)">NIP</label>
    <input id="nip" type="text" name="nip" value="{{ $isi['nip'] }}" maxlength="30" placeholder="Opsional">
   </div>
  </div>

  <label for="email">Email (opsional)</label>
  <input id="email" type="email" name="email" value="{{ $isi['email'] }}" maxlength="100">

  @if ($nominalKiri)
    @if ($opsiPaket === 'hitung')
    <label for="gaji">Penghasilan Anda (Rp, opsional)</label>
    <div class="hitung">
     <input id="gaji" type="number" min="0" step="1" inputmode="numeric" placeholder="Mis. 8000000">
     <button class="btn o" type="button" data-hitung="2.5" data-sumber="gaji" data-target="jumlah">Hitung 2,5%</button>
    </div>
    <small class="bantu">Zakat penghasilan umumnya 2,5% dari penghasilan yang mencapai nisab. Cek nisab terbaru di BAZNAS.</small>
    @endif
    <label for="jumlah">Nominal (Rp)</label>
    <input id="jumlah" type="number" name="jumlah" min="{{ $minimal }}" max="{{ $maksimal }}" step="1" inputmode="numeric" value="{{ $isi['jumlah'] }}" placeholder="Minimal {{ number_format($minimal, 0, ',', '.') }}" required>
    <div class="chips" aria-label="Pilihan nominal cepat">
     @foreach ([25000, 50000, 100000, 250000, 500000] as $n)
     <button class="pill" type="button" data-isi="{{ $n }}" data-target="jumlah">{{ rupiah($n) }}</button>
     @endforeach
    </div>
  @endif

  @if ($samarkanKiri)
  <fieldset class="tampil-nama">
   <legend>Nama saya di beranda dan kas</legend>
   <div class="pilih-bank">
    <label class="bank"><input type="radio" name="tampil_nama" value="lengkap" {{ $isi['tampil_nama'] === 'lengkap' ? 'checked' : '' }}><span>Nama lengkap</span></label>
    <label class="bank"><input type="radio" name="tampil_nama" value="singkat" {{ $isi['tampil_nama'] === 'singkat' ? 'checked' : '' }}><span>Disingkat: {{ nama_singkat($isi['nama'] ?: 'Nama Anda') }}</span></label>
    <label class="bank"><input type="radio" name="tampil_nama" value="anonim" {{ $isi['tampil_nama'] === 'anonim' ? 'checked' : '' }}><span>Hamba Allah</span></label>
   </div>
   <small class="bantu">Hamba Allah juga menyamarkan nama di pembukuan kas. Nama disingkat hanya berlaku di beranda. Pengurus tetap melihat data asli untuk verifikasi, dan pilihan ini bisa diubah nanti di halaman detail donasi.</small>
  </fieldset>
  @endif

  @if ($catatanKiri)
  <label for="catatan">Catatan (opsional)</label>
  <textarea id="catatan" name="catatan" rows="3" maxlength="500" placeholder="Mis. niat donasi atau pesan untuk pengurus">{{ $isi['catatan'] }}</textarea>
  @endif
  </div>

  <div class="kol">
  @if (!$nominalKiri)
  <span class="lbl">Nominal paket</span>
  <div class="nominal-tetap"><b>{{ rupiah($nominalTetap) }}</b></div>
  @endif

  @include('donasi.partials.pilih_metode')

  @if (!$samarkanKiri)
  <fieldset class="tampil-nama">
   <legend>Nama saya di beranda dan kas</legend>
   <div class="pilih-bank">
    <label class="bank"><input type="radio" name="tampil_nama" value="lengkap" {{ $isi['tampil_nama'] === 'lengkap' ? 'checked' : '' }}><span>Nama lengkap</span></label>
    <label class="bank"><input type="radio" name="tampil_nama" value="singkat" {{ $isi['tampil_nama'] === 'singkat' ? 'checked' : '' }}><span>Disingkat: {{ nama_singkat($isi['nama'] ?: 'Nama Anda') }}</span></label>
    <label class="bank"><input type="radio" name="tampil_nama" value="anonim" {{ $isi['tampil_nama'] === 'anonim' ? 'checked' : '' }}><span>Hamba Allah</span></label>
   </div>
   <small class="bantu">Hamba Allah juga menyamarkan nama di pembukuan kas. Nama disingkat hanya berlaku di beranda. Pengurus tetap melihat data asli untuk verifikasi, dan pilihan ini bisa diubah nanti di halaman detail donasi.</small>
  </fieldset>
  @endif

  @if (!$catatanKiri)
  <label for="catatan">Catatan (opsional)</label>
  <textarea id="catatan" name="catatan" rows="3" maxlength="500" placeholder="Mis. niat donasi atau pesan untuk pengurus">{{ $isi['catatan'] }}</textarea>
  @endif
  </div>
  </div>

  <div class="dua izin">
  <label class="cek"><input type="checkbox" name="tampil_beranda" value="1" {{ $isi['tampil_beranda'] === '1' ? 'checked' : '' }}><span>Tampilkan donasi saya di daftar Donatur terbaru di beranda setelah dikonfirmasi (hanya nama, program, dan waktu).</span></label>
  <label class="cek"><input type="checkbox" name="tampil_doa" value="1" {{ $isi['tampil_doa'] === '1' ? 'checked' : '' }}><span>Sertakan catatan saya sebagai doa di beranda. Jangan menulis data pribadi.</span></label>
  </div>

  <button class="btn" type="submit">Kirim donasi</button>
  <small class="bantu">Donasi tercatat di kas UPZ setelah pembayaran Anda dicek dan dikonfirmasi pengurus.</small>
 </form>
</section>
@endsection