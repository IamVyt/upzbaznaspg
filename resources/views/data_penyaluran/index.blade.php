@extends('layouts.app')

@section('title', 'Data penyaluran')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Data penyaluran</h1>
  <p>Ringkasan donasi yang sudah tersalurkan untuk tiap jenis penyaluran. Klik Lihat riwayat untuk mengetahui siapa yang berdonasi dan donasi apa.</p>
 </div>

 <div class="grid4">
  <div class="c g stat">
   <small>Total donasi tersalurkan</small>
   <b>{{ rupiah($totRp) }}</b>
   <small>Sudah dikonfirmasi petugas</small>
  </div>
  <div class="c l stat">
   <small>Jumlah donasi</small>
   <b>{{ $totN }} donasi</b>
   <small>Berstatus sudah tersalurkan</small>
  </div>
  <div class="c l stat">
   <small>Jumlah donatur</small>
   <b>{{ $totDonatur }} orang</b>
   <small>Donatur berbeda</small>
  </div>
  <div class="c d stat">
   <small>Masih diproses</small>
   <b>{{ $totProses }} donasi</b>
   <small>Menunggu bayar atau dicek</small>
  </div>
 </div>

 <div class="card" style="margin-top:1.6rem">
  <h2 class="sub">Daftar data <small>{{ count($baris) }} jenis penyaluran</small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>No</th>
      <th>Jenis penyaluran</th>
      <th>Riwayat</th>
      <th>Keterangan</th>
      <th class="r">Total donasi</th>
      <th class="r">Aksi</th>
     </tr>
    </thead>
    <tbody>
    @if (empty($baris))
     <tr>
      <td colspan="6">
       <div class="empty">
        <b>Belum ada data</b>
        <p>Daftar program belum tersedia.</p>
       </div>
      </td>
     </tr>
    @endif
    @foreach ($baris as $i => $r)
     @php
       $n = (int)($r['n'] ?? 0);
       $terakhir = !empty($r['terakhir']) ? \Carbon\Carbon::parse($r['terakhir'])->format('d/m/Y') : '';
     @endphp
     <tr>
      <td>{{ $i + 1 }}</td>
      <td><b>{{ $r['nama'] }}</b></td>
      <td class="nw">
       @if ($n > 0)
        <b>{{ $n }} donasi</b>
        <small>{{ (int)($r['donatur'] ?? 0) }} donatur{{ $terakhir ? ', terakhir ' . $terakhir : '' }}</small>
       @else
        Belum ada donasi
       @endif
       @if ((int)($r['proses'] ?? 0) > 0)
        <small>{{ (int)$r['proses'] }} masih diproses</small>
       @endif
      </td>
      <td><span class="meta"><small title="{{ $r['ket'] }}">{{ $r['ket'] }}</small></span></td>
      <td class="r nw"><b>{{ rupiah($r['total'] ?? 0) }}</b></td>
      <td class="r nw">
       <button class="lnk" type="button" data-dialog="rw-{{ $i }}" {{ $n === 0 ? 'disabled' : '' }}>Lihat riwayat</button>
      </td>
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
 </div>
</section>

@foreach ($baris as $i => $r)
 @php $daftar = $riwayat[$r['slug']] ?? []; @endphp
 @if (empty($daftar)) @continue @endif
 <dialog class="dlg lebar" id="rw-{{ $i }}" aria-labelledby="rw-j{{ $i }}">
  <div class="dbox">
   <button class="x" type="button" data-close aria-label="Tutup">×</button>
   <h3 id="rw-j{{ $i }}"><span>{{ $r['nama'] }}</span></h3>
   <p>{{ (int)($r['n'] ?? 0) }} donasi dari {{ (int)($r['donatur'] ?? 0) }} donatur, total <b>{{ rupiah($r['total'] ?? 0) }}</b>.{{ (int)($r['n'] ?? 0) > count($daftar) ? ' Menampilkan ' . count($daftar) . ' donasi terbaru.' : '' }}</p>
   <div class="scroll">
    <table class="tbl">
     <thead>
      <tr>
       <th>Tanggal</th>
       <th>Donatur</th>
       <th>Donasi apa</th>
       <th class="r">Nominal</th>
      </tr>
     </thead>
     <tbody>
     @foreach ($daftar as $d)
      @php
        $waktu = $d->diproses_at ? \Carbon\Carbon::parse($d->diproses_at) : ($d->dibuat_at ? \Carbon\Carbon::parse($d->dibuat_at) : $d->created_at);
        $metodeConfig = config('upz.metode_pembayaran', []);
        $metodeNama = $metodeConfig[$d->metode][0] ?? strtoupper($d->metode);
      @endphp
      <tr>
       <td class="nw">{{ $waktu->format('d/m/Y') }}</td>
       <td>
        <b>{{ $d->nama }}</b>@if ($d->samarkan_nama) <i class="cat">disamarkan</i>@endif
        <small>{{ $d->user ? '@' . $d->user->username : 'Tanpa akun' }}, {{ $d->kode }}</small>
       </td>
       <td>
        {{ $d->paket }}
        <small>{{ $metodeNama }}</small>
       </td>
       <td class="r nw"><b>{{ rupiah($d->jumlah) }}</b></td>
      </tr>
     @endforeach
     </tbody>
    </table>
   </div>
   <div class="acts end">
    <button class="btn o" type="button" data-close>Tutup</button>
   </div>
  </div>
 </dialog>
@endforeach
@endsection
