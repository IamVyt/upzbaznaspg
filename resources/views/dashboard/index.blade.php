@extends('layouts.app')

@section('title', 'Transaksi kas')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
window.KATEGORI = {!! json_encode($katMap) !!};
window.GRAFIK = {
  label: {!! json_encode($label) !!},
  masuk: {!! json_encode($chartMasuk) !!},
  keluar: {!! json_encode($chartKeluar) !!}
};
</script>
@endpush

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Transaksi kas</h1>
  <p>Selamat datang kembali, <b>{{ auth()->user()->name ?? 'Petugas' }}</b>. Peran Anda: <b>{{ ucfirst(auth()->user()->role ?? 'admin') }}</b>{{ can_input() ? '.' : ', hanya dapat melihat data.' }}</p>
 </div>

 @if ($menunggu > 0)
 <div class="alert info" role="status">
  <b>{{ (int)$menunggu }} donasi online sudah dibayar donatur dan menunggu pengecekan.</b> Donasi baru masuk ke saldo setelah Anda mengonfirmasinya. <a class="ul" href="{{ route('donasi_masuk.index') }}">Cek sekarang</a>
 </div>
 @endif

 <div class="grid4">
  <div class="c g stat">
   <small>Saldo kas saat ini</small>
   <b>{{ rupiah($saldo) }}</b>
   <small>Akumulasi total dana kas</small>
  </div>
  <div class="c l stat">
   <small>Total pemasukan</small>
   <b>{{ rupiah($total['masuk']) }}</b>
   <small>{{ $jml['masuk'] }} donasi diterima</small>
  </div>
  <div class="c l stat">
   <small>Total pengeluaran</small>
   <b>{{ rupiah($total['keluar']) }}</b>
   <small>{{ $jml['keluar'] }} transaksi tersalurkan</small>
  </div>
  <div class="c d stat">
   <small>Aktivitas transaksi</small>
   <b>{{ $jml['masuk'] + $jml['keluar'] }}</b>
   <small>Total mutasi kas tercatat</small>
  </div>
 </div>

 <div class="bar">
  <div class="pills">
   @foreach (['semua' => 'Semua transaksi', 'masuk' => 'Pemasukan', 'keluar' => 'Pengeluaran'] as $k => $lbl)
   <a class="pill {{ $f['tipe'] === $k ? 'on' : '' }}" href="?{{ http_build_query(array_merge($qs, ['tipe' => $k, 'page' => 1])) }}">{{ $lbl }}</a>
   @endforeach
  </div>
  <div class="acts">
   @if (can_input())
   <button class="btn g sm" type="button" data-dialog="dlg-form" data-aksi="tambah" data-tipe="masuk">+ Pemasukan</button>
   <button class="btn sm" type="button" data-dialog="dlg-form" data-aksi="tambah" data-tipe="keluar">+ Pengeluaran</button>
   @endif
   <button class="btn o sm" type="button" data-toggle="filter">Filter{{ $adaFilter ? ' (aktif)' : '' }}</button>
   <a class="btn o sm" href="{{ route('transaksi.export') }}?{{ http_build_query($qs) }}">Excel</a>
  </div>
 </div>

 <form class="card fbox" id="filter" method="get" {{ $adaFilter ? '' : 'hidden' }}>
  <input type="hidden" name="tipe" value="{{ $f['tipe'] }}">
  <div>
   <label for="cari">Cari nama atau keterangan</label>
   <input id="cari" type="text" name="cari" value="{{ $f['cari'] }}" placeholder="Ketik nama atau catatan">
  </div>
  <div>
   <label for="kt">Kategori dana</label>
   <select id="kt" name="kategori">
    <option value="">Semua kategori</option>
    @foreach ($katAll as $k)
    <option value="{{ $k->id }}" {{ $f['kategori'] == $k->id ? 'selected' : '' }}>[{{ $k->tipe === 'masuk' ? 'Masuk' : 'Keluar' }}] {{ $k->nama }}</option>
    @endforeach
   </select>
  </div>
  <div>
   <label for="m">Dari tanggal</label>
   <input id="m" type="date" name="tgl_mulai" value="{{ $f['mulai'] }}">
  </div>
  <div>
   <label for="s">Sampai tanggal</label>
   <input id="s" type="date" name="tgl_selesai" value="{{ $f['selesai'] }}">
  </div>
  <div class="acts">
   <button class="btn sm" type="submit">Terapkan</button>
   <a class="btn o sm" href="{{ route('dashboard') }}">Reset</a>
  </div>
 </form>
</section>

<section class="cols">
 <div class="card">
  <h2 class="sub">Riwayat transaksi <small>{{ $baris }} data{{ $adaFilter ? ', filter diterapkan' : '' }}</small></h2>
  @if ($isAdmin && $jmlVoid > 0)
  <form class="acts end" method="post" action="{{ route('dashboard.proses') }}" style="margin:-.4rem 0 1rem">
   @csrf
   <input type="hidden" name="aksi" value="hapus_semua_void">
   <button class="btn o sm bahaya" type="submit" data-konfirmasi="Hapus permanen SEMUA {{ $jmlVoid }} transaksi void? Datanya hilang dari tabel dan tidak bisa dikembalikan. Saldo kas tidak berubah karena transaksi void memang tidak dihitung. Isinya tetap tercatat di Riwayat aktivitas.">Hapus semua yang void ({{ $jmlVoid }})</button>
  </form>
  @endif
  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>Tanggal</th>
      <th>Nama dan keterangan</th>
      <th>Jenis</th>
      <th class="r">Jumlah</th>
      <th class="r">Aksi</th>
     </tr>
    </thead>
    <tbody>
    @if ($riwayat->isEmpty())
     <tr>
      <td colspan="5">
       <div class="empty">
        <b>Tidak ada transaksi</b>
        <p>Belum ada pencatatan yang cocok dengan kriteria Anda.</p>
        @if (can_input())
        <button class="btn g sm" type="button" data-dialog="dlg-form" data-aksi="tambah" data-tipe="masuk">Catat transaksi pertama</button>
        @endif
       </div>
      </td>
     </tr>
    @endif
    @foreach ($riwayat as $r)
     @php
       $in = $r->tipe === 'masuk';
       $void = $r->dibatalkan_at !== null;
       $tgl = $r->tanggal instanceof \Carbon\Carbon ? $r->tanggal : \Carbon\Carbon::parse($r->tanggal);
     @endphp
     <tr class="{{ $void ? 'void' : '' }}" {!! $void ? 'title="Void: ' . e($r->alasan_batal ?? '-') . '"' : '' !!}>
      <td class="nw">{{ $tgl->format('d/m/Y') }}</td>
      <td>
       <b>{{ $r->nama_pihak ?? 'Hamba Allah' }}</b>
       <span class="meta">
        @if ($r->kategori)<i class="cat">{{ $r->kategori->nama }}</i>@endif
        @if ($r->keterangan)<small class="ket" title="{{ $r->keterangan }}">{{ $r->keterangan }}</small>@endif
       </span>
      </td>
      <td>
       @if ($void)
        <span class="badge void">Void</span>
       @else
        <span class="badge {{ $in ? 'in' : 'out' }}">{{ $in ? 'Pemasukan' : 'Pengeluaran' }}</span>
       @endif
      </td>
      <td class="r nw"><b>{{ $in ? '+' : '-' }} {{ rupiah($r->jumlah) }}</b></td>
      <td class="r nw">
       @if ($void)
        <small>Dibatalkan</small>
        @if ($isAdmin)
        <form class="inline" method="post" action="{{ route('dashboard.proses') }}">
         @csrf
         <input type="hidden" name="aksi" value="hapus_void">
         <input type="hidden" name="id" value="{{ $r->id }}">
         <button class="lnk del" type="submit" data-konfirmasi="Hapus permanen transaksi {{ $r->nama_pihak }} sebesar {{ rupiah($r->jumlah) }}? Datanya hilang dari tabel dan tidak bisa dikembalikan. Saldo tidak berubah karena transaksi ini sudah void.">Hapus permanen</button>
        </form>
        @endif
       @elseif (can_input())
        <button class="lnk" type="button" data-dialog="dlg-form" data-aksi="ubah" data-tipe="{{ $r->tipe }}" data-id="{{ $r->id }}" data-nama="{{ $r->nama_pihak }}" data-jumlah="{{ (int)$r->jumlah }}" data-tanggal="{{ $tgl->format('Y-m-d') }}" data-kategori="{{ $r->kategori_id }}" data-keterangan="{{ $r->keterangan }}">Ubah</button>
        <button class="lnk del" type="button" data-dialog="dlg-void" data-id="{{ $r->id }}" data-nama="{{ $r->nama_pihak }}">Batalkan</button>
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
    @if (!$riwayat->onFirstPage())
     <a href="{{ $riwayat->previousPageUrl() }}">←</a>
    @endif
    @for ($i = max(1, $riwayat->currentPage() - 2); $i <= min($riwayat->lastPage(), $riwayat->currentPage() + 2); $i++)
     <a class="{{ $i === $riwayat->currentPage() ? 'on' : '' }}" href="{{ $riwayat->url($i) }}">{{ $i }}</a>
    @endfor
    @if ($riwayat->hasMorePages())
     <a href="{{ $riwayat->nextPageUrl() }}">→</a>
    @endif
   </div>
  </div>
  @endif
 </div>

 <div class="side">
  <div class="card">
   <h2 class="sub">Arus kas bulanan <small>6 bulan terakhir</small></h2>
   <div class="chart"><canvas id="grafik" aria-label="Grafik arus kas 6 bulan terakhir"></canvas></div>
  </div>
  <div class="card c d info">
   <span class="tg">Unit Pengumpul Zakat</span>
   <p>Pengelolaan dana zakat, infak, dan sedekah PT Petrokimia Gresik secara aman, transparan, dan akuntabel.</p>
   <p>Petugas aktif: <b>{{ auth()->user()->name ?? 'Petugas' }}</b></p>
  </div>
 </div>
</section>

@if (can_input())
<dialog class="dlg" id="dlg-form" aria-labelledby="f-judul">
 <form class="dbox" method="post" action="{{ route('dashboard.proses') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="f-judul"><span>Catat pemasukan</span></h3>
  @csrf
  <input type="hidden" name="aksi" id="f-aksi" value="tambah">
  <input type="hidden" name="tipe" id="f-tipe" value="masuk">
  <input type="hidden" name="id" id="f-id" value="0">
  <input type="hidden" name="pihak_id" id="f-pihak" value="">
  <div id="g-masuk">
   <label for="f-muzakki">Pilih muzakki (master donatur)</label>
   <select id="f-muzakki" data-pihak>
    <option value="">Donatur baru atau input cepat</option>
    @foreach ($muzakki as $m)
    <option value="{{ $m->id }}" data-nama="{{ $m->nama }}">{{ $m->nama }}</option>
    @endforeach
   </select>
  </div>
  <div id="g-keluar" hidden>
   <label for="f-mustahik">Pilih mustahik (master penerima)</label>
   <select id="f-mustahik" data-pihak>
    <option value="">Penerima baru atau input cepat</option>
    @foreach ($mustahik as $m)
    <option value="{{ $m->id }}" data-nama="{{ $m->nama }}">{{ $m->nama }}</option>
    @endforeach
   </select>
  </div>
  <label for="f-nama" id="f-lnama">Nama penyumbang atau donatur <button class="lnk" type="button" id="f-hamba">Isi "Hamba Allah"</button></label>
  <input id="f-nama" type="text" name="nama" maxlength="150" required>
  <label for="f-kat" id="f-lkat">Kategori pemasukan</label>
  <select id="f-kat" name="kategori_id" required></select>
  <label for="f-jumlah">Nominal (Rp)</label>
  <input id="f-jumlah" type="number" name="jumlah" min="1" step="1" required>
  <label for="f-tgl">Tanggal transaksi</label>
  <input id="f-tgl" type="date" name="tanggal" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
  <label for="f-ket">Keterangan atau catatan tambahan</label>
  <textarea id="f-ket" name="keterangan" rows="2" placeholder="Catatan transaksi bila ada"></textarea>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Simpan</button>
  </div>
 </form>
</dialog>

<dialog class="dlg" id="dlg-void" aria-labelledby="v-judul">
 <form class="dbox" method="post" action="{{ route('dashboard.proses') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="v-judul"><span>Batalkan transaksi?</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="void">
  <input type="hidden" name="id" id="v-id">
  <p>Transaksi atas nama <b id="v-nama"></b> akan dibatalkan (void). Data tetap tersimpan dengan penanda void dan tidak dihitung ke saldo.</p>
  <label for="v-alasan">Alasan pembatalan</label>
  <textarea id="v-alasan" name="alasan" rows="2" maxlength="255" placeholder="Contoh: data duplikat atau salah input" required></textarea>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Batalkan (void)</button>
  </div>
 </form>
</dialog>
@endif
@endsection
