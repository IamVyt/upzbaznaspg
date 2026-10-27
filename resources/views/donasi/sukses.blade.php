@extends('layouts.portal')

@section('title', 'Detail donasi')

@section('content')
<section class="top donasi-sukses">
 <div class="head">
  <h1>{{ $judul }}</h1>
  <p>{{ $kalimat }}</p>
  <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS: koin ramah yang membawa hati" width="340" height="342" decoding="async">
 </div>

 <div class="c l{{ $adaKiri ? ' dua' : '' }}">
  <h2><span class="tg">Detail donasi</span></h2>

  <div class="ringkas-donasi">
  @if ($tahap)
  <ol class="steps" aria-label="Tahapan donasi">
   <li class="{{ $tahap[0] }}"><b>{{ $tahap[0] === 'done' ? '✓' : '1' }}</b>Pesanan</li>
   <li class="{{ $tahap[1] }}"><b>{{ $tahap[1] === 'done' ? '✓' : '2' }}</b>Pembayaran</li>
   <li class="{{ $tahap[2] }}"><b>{{ $tahap[2] === 'done' ? '✓' : '3' }}</b>Tersalurkan</li>
  </ol>
  @endif

  <dl class="rinci">
   <div><dt>Nomor referensi</dt><dd class="kode">{{ $d->kode }}</dd></div>
   <div><dt>Status</dt><dd><span class="badge {{ $kelasStatus }}">{{ $labelStatus }}</span></dd></div>
   <div><dt>Program</dt><dd>{{ $d->program_nama }}</dd></div>
   <div><dt>Paket</dt><dd>{{ $d->paket }}</dd></div>
   <div><dt>Nominal</dt><dd>{{ rupiah($d->jumlah) }}</dd></div>
   <div><dt>Metode</dt><dd>{{ $m[0] }}{{ ($d->channel_nama && $d->channel_nama !== $m[0]) ? ', ' . $d->channel_nama : '' }}</dd></div>
   <div><dt>Dikirim</dt><dd>{{ $d->created_at->format('d/m/Y H:i') }}</dd></div>
   @if ($d->dibayar_at)<div><dt>Dilaporkan dibayar</dt><dd>{{ $d->dibayar_at->format('d/m/Y H:i') }}</dd></div>@endif
   @if ($st === 'diterima' && $d->diproses_at)<div><dt>Diterima pengurus</dt><dd>{{ $d->diproses_at->format('d/m/Y H:i') }}</dd></div>@endif
  </dl>
  </div>

  @if ($adaKiri)<div class="kol-bayar">@endif
  @if ($st === 'menunggu')
  <div class="petunjuk">
   <h3>Cara pembayaran: {{ $d->channel_nama ?: $m[0] }}</h3>
   <p class="tagihan">Bayar tepat <b>{{ rupiah($d->jumlah) }}</b></p>
   @if ($d->metode === 'qris')
    @if ($qris)
    <img class="qris" src="{{ asset($qris) }}" alt="Kode QRIS UPZ BAZNAS Petrokimia Gresik" width="224" height="224">
    @else
    <p class="peringatan">Kode QRIS belum tersedia saat ini. Silakan ganti metode pembayaran di bawah atau hubungi pengurus UPZ.</p>
    @endif
   @elseif ($nomor !== '')
    <div class="tujuan">
     <small>{{ $d->channel_nama }}</small>
     <b class="nomor">{{ $nomor }}</b>
     @if ($atasNama !== '')<small>a.n. {{ $atasNama }}</small>@endif
     <button class="btn o sm" type="button" data-salin="{{ preg_replace('/\D/', '', $nomor) }}">Salin nomor</button>
    </div>
   @endif
   <ol>
    @foreach ($m[2] as $langkah)<li>{{ $langkah }}</li>@endforeach
   </ol>
   <p class="bantu">Nomor referensi Anda: <b>{{ $d->kode }}</b></p>
  </div>
  @endif

  @if ($d->bukti && ($milik || Auth::user()?->isStaff()))
  <div class="petunjuk">
   <h3>Bukti pembayaran</h3>
   <a href="{{ route('donasi.bukti', $d->kode) }}" target="_blank" rel="noopener"><img class="bukti" src="{{ route('donasi.bukti', $d->kode) }}" alt="Bukti pembayaran yang Anda unggah" width="200" loading="lazy"></a>
  </div>
  @endif

  @if ($adaKiri)</div>@endif

  @if ($milik && $st === 'menunggu')
  <form class="lapor" method="post" action="{{ route('donasi.laporBayar', $d->kode) }}" enctype="multipart/form-data" data-sekali>
   @csrf
   <label for="bukti">Bukti pembayaran (wajib, gambar JPG, PNG, atau WebP, maksimal 2 MB)</label>
   <input id="bukti" type="file" name="bukti" accept="image/jpeg,image/png,image/webp" required>
   <button class="btn" type="submit">Saya sudah membayar</button>
  </form>

  <details class="ganti ganti-metode">
   <summary>Ganti metode pembayaran</summary>
   <form method="post" action="{{ route('donasi.gantiMetode', $d->kode) }}" data-sekali>
    @csrf
    @include('donasi.partials.pilih_metode')
    <button class="btn o" type="submit">Simpan metode baru</button>
   </form>
  </details>

  <form class="batal" method="post" action="{{ route('donasi.batal', $d->kode) }}" data-konfirmasi="Batalkan donasi ini? Donasi yang dibatalkan tidak bisa dipakai lagi.">
   @csrf
   <button class="lnk del" type="submit">Batalkan donasi ini</button>
  </form>
  @elseif ($milik && $st === 'dibayar')
  <form class="lapor" method="post" action="{{ route('donasi.laporBayar', $d->kode) }}" enctype="multipart/form-data" data-sekali>
   @csrf
   <label for="bukti">Unggah atau ganti bukti pembayaran (JPG, PNG, atau WebP, maksimal 2 MB)</label>
   <input id="bukti" type="file" name="bukti" accept="image/jpeg,image/png,image/webp" required>
   <button class="btn o" type="submit">Simpan bukti</button>
  </form>
  @endif

  @if ($milik && in_array($st, ['menunggu', 'dibayar', 'diterima'], true))
  @php $modeNama = $d->samarkan_nama ? 'anonim' : ($d->nama_singkat ? 'singkat' : 'lengkap'); @endphp
  <details class="ganti ganti-tampil"{{ ($st === 'diterima' && !$d->tampil_beranda) ? ' open' : '' }}>
   <summary>Tampil di beranda: {{ $d->tampil_beranda ? ($d->beranda_blokir ? 'disembunyikan pengurus' : 'ya') : 'tidak' }}</summary>
   <form method="post" action="{{ route('donasi.ubahTampilan', $d->kode) }}" data-sekali>
    @csrf
    <label class="cek"><input type="checkbox" name="tampil_beranda" value="1" {{ $d->tampil_beranda ? 'checked' : '' }}><span>Tampilkan donasi saya di daftar Donatur terbaru di beranda setelah dikonfirmasi (hanya nama, program, dan waktu, tanpa nominal).</span></label>
    <fieldset class="tampil-nama">
     <legend>Nama saya di beranda</legend>
     <div class="pilih-bank">
      <label class="bank"><input type="radio" name="tampil_nama" value="lengkap" {{ $modeNama === 'lengkap' ? 'checked' : '' }}><span>Nama lengkap</span></label>
      <label class="bank"><input type="radio" name="tampil_nama" value="singkat" {{ $modeNama === 'singkat' ? 'checked' : '' }}><span>Disingkat: {{ nama_singkat((string)$d->nama) }}</span></label>
      <label class="bank"><input type="radio" name="tampil_nama" value="anonim" {{ $modeNama === 'anonim' ? 'checked' : '' }}><span>Hamba Allah</span></label>
     </div>
    </fieldset>
    @if (trim((string)$d->catatan) !== '')
    <label class="cek"><input type="checkbox" name="tampil_doa" value="1" {{ $d->tampil_doa ? 'checked' : '' }}><span>Sertakan catatan saya sebagai doa di beranda: <i>{{ \Illuminate\Support\Str::limit(trim((string)$d->catatan), 90, '…') }}</i></span></label>
    @endif
    <button class="btn o" type="submit">Simpan pengaturan</button>
    <small class="bantu">Pilihan ini bisa diubah atau dicabut kapan saja. Nama di pembukuan kas yang sudah tercatat tidak ikut berubah.{{ $d->beranda_blokir ? ' Pengurus menyembunyikan donasi ini dari beranda.' : '' }}</small>
   </form>
  </details>
  @endif

  <div class="acts">
   @if ($st === 'diterima')
   <a class="btn g" href="{{ route('donasi.cetak', $d->kode) }}" target="_blank" rel="noopener">Cetak bukti penyaluran</a>
   @elseif (in_array($st, ['menunggu', 'dibayar'], true))
   <a class="btn o" href="{{ route('donasi.cetak', $d->kode) }}" target="_blank" rel="noopener">Cetak tanda terima sementara</a>
   @endif
   <a class="btn o" href="{{ route('donasi.saya') }}">Donasi saya</a>
   <a class="btn o" href="{{ route('donasi.create') }}">Salurkan lagi</a>
  </div>
 </div>
</section>
@endsection