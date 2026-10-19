@extends('layouts.app')

@section('title', 'Program dan kategori')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Program dan kategori</h1>
  <p>Program penyaluran yang sama dengan bagian Program di beranda situs, lengkap dengan kategori kas dan donasi yang sudah tersalurkan.</p>
 </div>

 <div class="card">
  <h2 class="sub">Daftar data <small>{{ count($programs) }} program, sama dengan beranda situs. <a class="ul" href="{{ route('home') }}#program">Lihat di beranda</a></small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>No</th>
      <th>Program</th>
      <th>Keterangan</th>
      <th>Kategori kas</th>
      <th>Paket</th>
      <th class="r">Donasi tersalurkan</th>
     </tr>
    </thead>
    <tbody>
    @if (empty($programs))
     <tr>
      <td colspan="6">
       <div class="empty"><b>Belum ada data</b><p>Daftar program belum tersedia.</p></div>
      </td>
     </tr>
    @endif
    @foreach ($programs as $i => $p)
     @php
       $s = $p['slug'];
       $kode = $penyaluran[$s]['kategori'] ?? '';
       $st = $stat[$s] ?? null;
     @endphp
     <tr>
      <td>{{ $i + 1 }}</td>
      <td><b>{{ $p['nama'] }}</b></td>
      <td><span class="meta"><small title="{{ $p['ringkas'] }}">{{ $p['ringkas'] }}</small></span></td>
      <td class="nw">
       @if (isset($kat[$kode]))
        <i class="cat">{{ $kat[$kode] }}</i>
       @else
        <span class="badge void" title="Kategori tidak ditemukan atau nonaktif, sehingga donasi program ini belum bisa dikonfirmasi ke kas.">Belum ada kategori</span>
       @endif
      </td>
      <td class="nw">{{ count($penyaluran[$s]['paket'] ?? []) }} paket</td>
      <td class="r nw"><b>{{ rupiah($st['total'] ?? 0) }}</b><small>{{ (int)($st['n'] ?? 0) }} donasi</small></td>
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
 </div>
</section>
@endsection
