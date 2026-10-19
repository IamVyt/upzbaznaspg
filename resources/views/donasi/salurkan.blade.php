@extends('layouts.portal')

@section('title', 'Salurkan donasi')

@section('content')
<section class="top">
 <div class="head"><h1 class="tag">Salurkan</h1><p>Tunaikan zakat, infak, dan sedekah Anda melalui UPZ BAZNAS Petrokimia Gresik. Pilih program dan paket, isi data, lalu lakukan pembayaran.</p></div>

 <ol class="steps" aria-label="Langkah penyaluran">
  <li class="on"><b>1</b>Pilih paket</li>
  <li><b>2</b>Isi data</li>
  <li><b>3</b>Bayar</li>
 </ol>

 <div class="pills pilih" aria-label="Pilih program">
  @foreach ($tersedia as $s => $p)
  <a class="pill {{ $s === $slug ? 'on' : '' }}" href="{{ route('donasi.create', ['program' => $s]) }}">{{ $p['nama'] }}</a>
  @endforeach
 </div>

 @if ($prog)
 <p class="lead">{{ $prog['ringkas'] }}</p>
 <div class="grid2">
  @php $warnaList = ['l', 'g', 'd']; @endphp
  @foreach ($penyaluran[$slug]['paket'] as $i => $pk)
  @php $warna = $warnaList[$i % 3]; @endphp
  <a class="c {{ $warna }} paket" href="{{ route('donasi.bayarForm', ['program' => $slug, 'paket' => $i]) }}">
   <h3><span>{{ $pk['nama'] }}</span></h3>
   <b class="harga">{{ ($pk['nominal'] ?? 0) > 0 ? rupiah($pk['nominal']) : 'Nominal bebas' }}</b>
   <p>{{ $pk['ket'] ?? '' }}</p>
   <span class="more"><i>↗</i>Pilih paket ini</span>
  </a>
  @endforeach
 </div>
 @else
 <p class="lead">Pilih program yang ingin Anda dukung.</p>
 <div class="grid2">
  @foreach ($tersedia as $s => $p)
  <a class="c {{ $p['warna'] }} paket" href="{{ route('donasi.create', ['program' => $s]) }}">
   <h3><span>{{ $p['nama'] }}</span></h3>
   <p>{{ $p['ringkas'] }}</p>
   <span class="more"><i>↗</i>Lihat paket</span>
  </a>
  @endforeach
 </div>
 @endif

 <p class="catatan">
  @auth
  Sudah pernah berdonasi? <a class="ul" href="{{ route('donasi.saya') }}">Lihat donasi saya</a>.
  @else
  Untuk menyalurkan donasi, Anda akan diminta masuk atau mendaftar terlebih dahulu. Pilihan paket Anda tetap tersimpan.
  @endauth
 </p>
</section>
@endsection