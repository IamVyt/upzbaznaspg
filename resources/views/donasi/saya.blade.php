@extends('layouts.portal')

@section('title', 'Donasi saya')

@section('content')
<section class="top">
 <div class="head"><h1 class="tag">Donasi saya</h1><p>Pantau status donasi Anda. Donasi berstatus Sudah tersalurkan setelah pengurus memeriksa pembayaran, dan buktinya bisa dicetak.</p></div>

 <div class="grid3">
  <div class="c g stat"><small>Sudah tersalurkan</small><b>{{ rupiah($tersalurkan) }}</b><small>Tercatat di kas UPZ</small></div>
  <div class="c l stat"><small>Sedang berjalan</small><b>{{ rupiah($berjalan) }}</b><small>Menunggu pembayaran atau pengecekan</small></div>
  <div class="c d stat"><small>Total donasi dikirim</small><b>{{ $totalCount }}</b><small>Semua status</small></div>
 </div>

 <div class="bar">
  <span></span>
  <div class="acts"><a class="btn g sm" href="{{ route('donasi.create') }}">+ Salurkan donasi</a></div>
 </div>

 <div class="card">
  <h2 class="sub">Riwayat donasi <small>{{ $totalCount }} data</small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead><tr><th>Tanggal</th><th>Program dan paket</th><th>Metode</th><th class="r">Nominal</th><th>Status</th><th class="r">Aksi</th></tr></thead>
    <tbody>
    @if ($riwayat->isEmpty())
     <tr><td colspan="6"><div class="empty"><b>Belum ada donasi</b><p>Donasi yang Anda kirim akan muncul di sini beserta statusnya.</p>
      <a class="btn g sm" href="{{ route('donasi.create') }}">Salurkan sekarang</a></div></td></tr>
    @endif
    @foreach ($riwayat as $r)
    @php
      [$lbl, $kls] = label_status_donasi($r->status);
      $namaMetode = config('upz.metode_pembayaran.' . $r->metode . '.0', ucfirst($r->metode));
    @endphp
     <tr>
      <td class="nw">{{ $r->created_at->format('d/m/Y') }}<small>{{ $r->created_at->format('H:i') }}</small></td>
      <td><b>{{ $r->program_nama }}</b>
       <span class="meta"><i class="cat">{{ $r->paket }}</i><small>{{ $r->kode }}</small></span></td>
      <td class="nw">{{ $namaMetode }}@if ($r->channel_nama && $r->channel_nama !== $namaMetode)<small>{{ $r->channel_nama }}</small>@endif</td>
      <td class="r nw"><b>{{ rupiah($r->jumlah) }}</b></td>
      <td><span class="badge {{ $kls }}">{{ $lbl }}</span>
       @if ($r->status === 'ditolak' && $r->alasan_tolak)<small>{{ $r->alasan_tolak }}</small>@endif</td>
      <td class="r nw">
       @if ($r->status === 'menunggu')
        <a class="lnk" href="{{ route('donasi.sukses', $r->kode) }}">Bayar</a>
       @elseif ($r->status === 'diterima')
        <a class="lnk" href="{{ route('donasi.cetak', $r->kode) }}" target="_blank" rel="noopener">Cetak bukti</a>
       @else
        <a class="lnk" href="{{ route('donasi.sukses', $r->kode) }}">Detail</a>
       @endif
      </td>
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
  @if ($riwayat->hasPages())
  <div class="pg">
   <small>Halaman {{ $riwayat->currentPage() }} dari {{ $riwayat->lastPage() }}</small>
   <div>
    @if (!$riwayat->onFirstPage())<a href="{{ $riwayat->previousPageUrl() }}">←</a>@endif
    @for ($i = max(1, $riwayat->currentPage() - 2); $i <= min($riwayat->lastPage(), $riwayat->currentPage() + 2); $i++)
    <a class="{{ $i === $riwayat->currentPage() ? 'on' : '' }}" href="{{ $riwayat->url($i) }}">{{ $i }}</a>
    @endfor
    @if ($riwayat->hasMorePages())<a href="{{ $riwayat->nextPageUrl() }}">→</a>@endif
   </div>
  </div>
  @endif
 </div>
</section>
@endsection