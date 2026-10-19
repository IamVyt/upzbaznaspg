@extends('layouts.app')

@section('title', 'Donasi masuk')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Donasi masuk</h1>
  <p>Daftar donasi online dari halaman Salurkan. Admin dan bendahara dapat mengonfirmasi setelah memastikan uangnya diterima di rekening, e-wallet, atau kas.</p>
 </div>

 <div class="grid4">
  <div class="c g stat">
   <small>Perlu dicek</small>
   <b>{{ $hitung['dibayar']['n'] }} donasi</b>
   <small>{{ rupiah($hitung['dibayar']['total']) }} sudah dibayar donatur</small>
  </div>
  <div class="c l stat">
   <small>Menunggu pembayaran</small>
   <b>{{ $hitung['menunggu']['n'] }} donasi</b>
   <small>{{ rupiah($hitung['menunggu']['total']) }} belum dibayar</small>
  </div>
  <div class="c l stat">
   <small>Sudah tersalurkan</small>
   <b>{{ $hitung['diterima']['n'] }} donasi</b>
   <small>{{ rupiah($hitung['diterima']['total']) }} tercatat di kas</small>
  </div>
  <div class="c d stat">
   <small>Ditolak atau dibatalkan</small>
   <b>{{ $batal['n'] }} donasi</b>
   <small>{{ rupiah($batal['total']) }} tidak diproses</small>
  </div>
 </div>

 <div class="bar">
  <div class="pills">
   @foreach ($tab as $k => $t)
   <a class="pill {{ $status === $k ? 'on' : '' }}" href="?{{ http_build_query(array_merge(request()->except(['status', 'hal', 'page']), ['status' => $k, 'page' => 1])) }}">{{ $t[0] }}</a>
   @endforeach
  </div>
  <form class="acts" method="get">
   <input type="hidden" name="status" value="{{ $status }}">
   <input class="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama, kode, atau kontak" aria-label="Cari donasi">
   <button class="btn o sm" type="submit">Cari</button>
  </form>
  @if ($bolehHapus && in_array($status, ['menunggu', 'batal'], true))
   @php
     $jmlHapus = $status === 'menunggu' ? $hitung['menunggu']['n'] : $batal['n'];
   @endphp
   <form method="post" action="{{ route('donasi_masuk.proses') }}" data-konfirmasi="Hapus SEMUA {{ $jmlHapus }} donasi di tab {{ $tab[$status][0] }} (bukan hanya yang tampil di halaman ini atau hasil pencarian)? Donasi tersebut juga hilang dari riwayat Donasi saya milik donatur. Donasi yang sudah dibayar atau sudah tersalurkan tidak ikut terhapus. Tindakan ini tidak bisa dibatalkan.">
    @csrf
    <input type="hidden" name="aksi" value="hapus_semua">
    <input type="hidden" name="status" value="{{ $status }}">
    <button class="btn o sm bahaya" type="submit" {{ (int)$jmlHapus === 0 ? 'disabled' : '' }}>Hapus semua</button>
   </form>
  @endif
 </div>

 <div class="card">
  <h2 class="sub">Daftar donasi <small>{{ $baris }} data</small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>Masuk</th>
      <th>Donatur</th>
      <th>Program dan paket</th>
      <th>Metode</th>
      <th class="r">Nominal</th>
      <th>Status</th>
      @if (can_input())
      <th class="r">Aksi</th>
      @endif
     </tr>
    </thead>
    <tbody>
    @if ($daftar->isEmpty())
     <tr>
      <td colspan="{{ can_input() ? 7 : 6 }}">
       <div class="empty">
        <b>Tidak ada donasi</b>
        <p>Belum ada donasi yang cocok dengan filter ini.</p>
       </div>
      </td>
     </tr>
    @endif
    @foreach ($daftar as $r)
     @php
       [$lbl, $kls] = label_status_donasi($r->status);
       $namaMetode = $metode[$r->metode][0] ?? strtoupper($r->metode);
       $dibuatAt = $r->dibuat_at ? \Carbon\Carbon::parse($r->dibuat_at) : $r->created_at;
       $dibayarAt = $r->dibayar_at ? \Carbon\Carbon::parse($r->dibayar_at) : null;
       $diprosesAt = $r->diproses_at ? \Carbon\Carbon::parse($r->diproses_at) : null;
     @endphp
     <tr>
      <td class="nw">
       {{ $dibuatAt->format('d/m/Y') }}
       <small>{{ $dibuatAt->format('H:i') }}</small>
      </td>
      <td>
       <b>{{ $r->nama }}</b>@if ($r->samarkan_nama) <i class="cat">disamarkan</i>@endif
       <small>{{ $r->kontak }}{{ $r->email ? ', ' . $r->email : '' }}{{ $r->nip ? ', NIP ' . $r->nip : '' }}</small>
       @if ($r->catatan)<small title="{{ $r->catatan }}">Catatan: {{ \Illuminate\Support\Str::limit($r->catatan, 60) }}</small>@endif
       @if (!empty($r->tampil_beranda))<small><i class="cat">Tampil di beranda{{ !empty($r->tampil_doa) ? ' bersama doa' : '' }}</i></small>@endif
      </td>
      <td>
       <b>{{ $r->program_nama }}</b>
       <small>{{ $r->paket }}, {{ $r->kode }}</small>
      </td>
      <td class="nw">
       {{ $namaMetode }}
       @if ($r->channel_nama && $r->channel_nama !== $namaMetode)
        <small>{{ $r->channel_nama }}</small>
       @endif
      </td>
      <td class="r nw">
       <b>{{ rupiah($r->jumlah) }}</b>
      </td>
      <td>
       <span class="badge {{ $kls }}">{{ $lbl }}</span>
       @if ($dibayarAt && $r->status === 'dibayar')
        <small>Dibayar {{ $dibayarAt->format('d/m/Y H:i') }}</small>
       @endif
       @if ($r->bukti)
        <small><a class="ul" href="{{ route('donasi.bukti', $r->kode) }}" target="_blank" rel="noopener">Lihat bukti</a></small>
       @endif
       @if (in_array($r->status, ['ditolak'], true) && $r->alasan_tolak)
        <small>{{ $r->alasan_tolak }}</small>
       @endif
       @if ($r->status === 'diterima' && $diprosesAt)
        <small>Masuk kas {{ $diprosesAt->format('d/m/Y') }}</small>
       @endif
      </td>
      @if (can_input())
      <td class="r nw">
       @if (in_array($r->status, ['menunggu', 'dibayar'], true))
        @php $ringkasan = $r->kode . ', ' . $r->nama . ', ' . rupiah($r->jumlah); @endphp
        <button class="lnk" type="button" data-dialog="dlg-terima" data-proses="t" data-id="{{ $r->id }}" data-ringkas="{{ $ringkasan }}">Konfirmasi</button>
        <button class="lnk del" type="button" data-dialog="dlg-tolak" data-proses="k" data-id="{{ $r->id }}" data-ringkas="{{ $ringkasan }}">Tolak</button>
        @if ($bolehHapus && $r->status === 'menunggu')
         <form class="inline" method="post" action="{{ route('donasi_masuk.proses') }}">
          @csrf
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="id" value="{{ $r->id }}">
          <button class="lnk del" type="submit" data-konfirmasi="Hapus donasi {{ $r->kode }} atas nama {{ $r->nama }}? Donasi belum dibayar ini akan hilang dari sistem.">Hapus</button>
         </form>
        @endif
       @elseif ($bolehHapus && in_array($r->status, ['ditolak', 'dibatalkan'], true))
        <form class="inline" method="post" action="{{ route('donasi_masuk.proses') }}">
         @csrf
         <input type="hidden" name="aksi" value="hapus">
         <input type="hidden" name="id" value="{{ $r->id }}">
         <button class="lnk del" type="submit" data-konfirmasi="Hapus donasi {{ $r->kode }} atas nama {{ $r->nama }}? Tindakan ini tidak bisa dibatalkan.">Hapus</button>
        </form>
       @else
        <small>Selesai</small>
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

@if (can_input())
<dialog class="dlg" id="dlg-terima" aria-labelledby="t-judul">
 <form class="dbox" method="post" action="{{ route('donasi_masuk.proses') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="t-judul"><span>Konfirmasi donasi diterima?</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="terima">
  <input type="hidden" name="id" id="t-id">
  <p>Donasi <b id="t-ringkas"></b> akan berstatus Sudah tersalurkan dan dicatat sebagai pemasukan kas, sehingga saldo bertambah. Lanjutkan hanya bila uangnya sudah benar-benar terlihat di mutasi rekening, e-wallet, atau kas tunai.</p>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Ya, uang sudah diterima</button>
  </div>
 </form>
</dialog>

<dialog class="dlg" id="dlg-tolak" aria-labelledby="k-judul">
 <form class="dbox" method="post" action="{{ route('donasi_masuk.proses') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="k-judul"><span>Tolak donasi?</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="tolak">
  <input type="hidden" name="id" id="k-id">
  <p>Donasi <b id="k-ringkas"></b> akan ditandai ditolak dan tidak masuk ke kas. Alasannya terlihat oleh donatur.</p>
  <label for="k-alasan">Alasan penolakan</label>
  <textarea id="k-alasan" name="alasan" rows="2" maxlength="255" placeholder="Contoh: pembayaran tidak ditemukan di mutasi rekening" required></textarea>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Tolak donasi</button>
  </div>
 </form>
</dialog>
@endif
@endsection
