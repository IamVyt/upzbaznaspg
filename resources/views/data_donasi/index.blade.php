@extends('layouts.app')

@section('title', 'Data donasi')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Data donasi</h1>
  <p>Data donatur yang donasinya sudah berhasil tersalurkan. Donasi yang belum dikonfirmasi petugas ada di menu Donasi masuk.</p>
 </div>

 <div class="grid4">
  <div class="c g stat">
   <small>Total donasi berhasil</small>
   <b>{{ rupiah($ring['total']) }}</b>
   <small>Sudah dikonfirmasi petugas</small>
  </div>
  <div class="c l stat">
   <small>Jumlah donasi</small>
   <b>{{ (int)$ring['n'] }} donasi</b>
   <small>Berstatus sudah tersalurkan</small>
  </div>
  <div class="c l stat">
   <small>Jumlah donatur</small>
   <b>{{ (int)$ring['donatur'] }} orang</b>
   <small>Donatur berbeda</small>
  </div>
  <div class="c d stat">
   <small>Rata-rata donasi</small>
   <b>{{ rupiah($rata) }}</b>
   <small>Per donasi berhasil</small>
  </div>
 </div>

 <div class="bar">
  <form class="acts" method="get">
   <input class="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama, kode, kontak, atau akun" aria-label="Cari donasi">
   <select class="saring" name="program" aria-label="Filter program">
    <option value="">Semua program</option>
    @foreach ($opsiProgram as $s => $nama)
    <option value="{{ $s }}" {{ $prog === $s ? 'selected' : '' }}>{{ $nama }}</option>
    @endforeach
   </select>
   <button class="btn sm" type="submit">Cari</button>
   @if ($cari !== '' || $prog !== '')
    <a class="btn o sm" href="{{ route('data_donasi.index') }}">Reset</a>
   @endif
  </form>

  @if ($bolehHapus)
  <form method="post" action="{{ route('data_donasi.proses') }}" class="acts">
   @csrf
   <input type="hidden" name="aksi" value="hapus_semua">
   <button class="btn o sm bahaya" type="submit" {{ (int)$ring['n'] === 0 ? 'disabled' : '' }} data-konfirmasi="Hapus SEMUA {{ (int)$ring['n'] }} donasi berhasil (total {{ rupiah($ring['total']) }}), bukan hanya yang tampil di hasil pencarian? Semuanya juga hilang dari Donasi masuk dan dari riwayat Donasi saya milik donatur. Catatan di Dashboard kas TIDAK diubah, jadi saldo tetap dan transaksinya masih tampil. Tindakan ini tidak bisa dibatalkan.">Hapus semua</button>
   <button class="btn o sm bahaya" type="submit" name="batal_kas" value="1" {{ (int)$ring['n'] === 0 ? 'disabled' : '' }} data-konfirmasi="Hapus SEMUA {{ (int)$ring['n'] }} donasi berhasil (total {{ rupiah($ring['total']) }}) DAN batalkan catatan kas-nya? Saldo kas berkurang sebesar total donasi yang kasnya masih aktif. Catatan kas tetap tersimpan dengan tanda void di Dashboard kas, tetapi data donasinya hilang dan tidak bisa dikembalikan.">Hapus semua dan batalkan kas</button>
  </form>
  @endif
 </div>

 <div class="card">
  <h2 class="sub">Daftar data <small>{{ $jml }} data</small></h2>
  @if ($bolehHapus && (int)$yatimInfo['n'] > 0)
  <div class="peringatan">
   Ada <b>{{ (int)$yatimInfo['n'] }} catatan kas</b> dari donasi online (total <b>{{ rupiah($yatimInfo['total']) }}</b>) yang data donasinya sudah dihapus, tetapi masih dihitung di saldo dan tampil di Dashboard kas.
   <form method="post" action="{{ route('data_donasi.proses') }}" style="margin-top:.6rem">
    @csrf
    <input type="hidden" name="aksi" value="batalkan_yatim">
    <button class="btn o sm bahaya" type="submit" data-konfirmasi="Batalkan (void) {{ (int)$yatimInfo['n'] }} catatan kas senilai {{ rupiah($yatimInfo['total']) }}? Saldo kas berkurang sebesar itu. Catatan tetap tersimpan dengan tanda void di Dashboard kas.">Batalkan catatan kas ini (void)</button>
   </form>
  </div>
  @endif

  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>No</th>
      <th>Tanggal</th>
      <th>Donatur</th>
      <th>Kontak</th>
      <th>Program dan paket</th>
      <th>Metode</th>
      <th class="r">Nominal</th>
      @if ($bolehHapus)
      <th class="r">Aksi</th>
      @endif
     </tr>
    </thead>
    <tbody>
    @if ($daftar->isEmpty())
     <tr>
      <td colspan="{{ $bolehHapus ? 8 : 7 }}">
       <div class="empty">
        <b>Belum ada data</b>
        <p>Belum ada donasi berhasil yang cocok dengan pencarian ini.</p>
       </div>
      </td>
     </tr>
    @endif
    @foreach ($daftar as $i => $r)
     @php
       $namaMetode = $metode[$r->metode][0] ?? strtoupper($r->metode);
       $waktu = $r->diproses_at ? \Carbon\Carbon::parse($r->diproses_at) : ($r->dibuat_at ? \Carbon\Carbon::parse($r->dibuat_at) : $r->created_at);
       $trxId = $r->transaksi_id;
       $trxBatal = $r->transaksi && $r->transaksi->dibatalkan_at !== null;
     @endphp
     <tr>
      <td>{{ ($daftar->currentPage() - 1) * $daftar->perPage() + $i + 1 }}</td>
      <td class="nw">
       {{ $waktu->format('d/m/Y') }}
       <small>{{ $waktu->format('H:i') }}</small>
      </td>
      <td>
       <b>{{ $r->nama }}</b>@if ($r->samarkan_nama) <i class="cat">disamarkan</i>@endif
       <small>{{ $r->user ? '@' . $r->user->username : 'Tanpa akun' }}</small>
       <small>Beranda: {{ $r->tampil_beranda ? ($r->beranda_blokir ? 'disembunyikan pengurus' : 'tampil sebagai ' . nama_beranda((string)$r->nama, (int)$r->samarkan_nama, (int)$r->nama_singkat)) : 'tidak diizinkan donatur' }}</small>
      </td>
      <td>
       {{ $r->kontak }}
       @if ($r->email)<small>{{ $r->email }}</small>@endif
       @if ($r->nip)<small>NIP {{ $r->nip }}</small>@endif
      </td>
      <td>
       <b>{{ $r->program_nama }}</b>
       <small>{{ $r->paket }}</small>
      </td>
      <td class="nw">
       {{ $namaMetode }}
       @if ($r->channel_nama && $r->channel_nama !== $namaMetode)
        <small>{{ $r->channel_nama }}</small>
       @endif
      </td>
      <td class="r nw">
       <b>{{ rupiah($r->jumlah) }}</b>
       <small>{{ $r->kode }}</small>
       <small>{{ $trxId ? ($trxBatal ? 'Kas: sudah dibatalkan' : 'Kas: tercatat') : 'Kas: tidak ada' }}</small>
       @if ($r->bukti)
        <small><a class="ul" href="{{ route('donasi.bukti', $r->kode) }}" target="_blank" rel="noopener">Lihat bukti</a></small>
       @endif
      </td>
      @if ($bolehHapus)
      <td class="r nw">
       <form class="aksi-hapus" method="post" action="{{ route('data_donasi.proses') }}">
        @csrf
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" value="{{ $r->id }}">
        <input type="hidden" name="cari" value="{{ $cari }}">
        <input type="hidden" name="program" value="{{ $prog }}">
        <input type="hidden" name="page" value="{{ $daftar->currentPage() }}">
        <button class="lnk del" type="submit" data-konfirmasi="Hapus donasi {{ $r->kode }} dari {{ $r->nama }} sebesar {{ rupiah($r->jumlah) }}? Data ini juga hilang dari Donasi masuk dan dari riwayat Donasi saya milik donatur. Catatan di Dashboard kas TIDAK diubah, saldo tetap. Tindakan ini tidak bisa dibatalkan.">Hapus data</button>
        @if ($trxId && !$trxBatal)
        <button class="lnk del" type="submit" name="batal_kas" value="1" data-konfirmasi="Hapus donasi {{ $r->kode }} dari {{ $r->nama }} DAN batalkan catatan kas-nya sebesar {{ rupiah($r->jumlah) }}? Saldo kas berkurang sebesar itu. Catatan kas tetap tersimpan dengan tanda void di Dashboard kas, tetapi data donasinya hilang dan tidak bisa dikembalikan.">Hapus dan batalkan kas</button>
        @endif
       </form>
       @if ($r->tampil_beranda)
       <form class="aksi-hapus" method="post" action="{{ route('data_donasi.proses') }}">
        @csrf
        <input type="hidden" name="aksi" value="beranda">
        <input type="hidden" name="id" value="{{ $r->id }}">
        <input type="hidden" name="cari" value="{{ $cari }}">
        <input type="hidden" name="program" value="{{ $prog }}">
        <input type="hidden" name="page" value="{{ $daftar->currentPage() }}">
        <button class="lnk" type="submit">{{ $r->beranda_blokir ? 'Tampilkan lagi di beranda' : 'Sembunyikan dari beranda' }}</button>
       </form>
       @endif
      </td>
      @endif
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
  @if ($daftar->hasPages())
  <div class="pg">
   <small>Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }}</small>
   <div>
    @if (!$daftar->onFirstPage())
     <a href="{{ $daftar->previousPageUrl() }}">←</a>
    @endif
    @for ($i = max(1, $daftar->currentPage() - 2); $i <= min($daftar->lastPage(), $daftar->currentPage() + 2); $i++)
     <a class="{{ $i === $daftar->currentPage() ? 'on' : '' }}" href="{{ $daftar->url($i) }}">{{ $i }}</a>
    @endfor
    @if ($daftar->hasMorePages())
     <a href="{{ $daftar->nextPageUrl() }}">→</a>
    @endif
   </div>
  </div>
  @endif
 </div>
</section>
@endsection
