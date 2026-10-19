@extends('layouts.app')

@section('title', 'Metode pembayaran')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Metode pembayaran</h1>
  <p>Atur QRIS, rekening bank, dan e-wallet UPZ. Donatur hanya bisa memilih metode yang sudah diaktifkan dan terisi nomornya{{ $boleh ? '.' : '. Peran Anda hanya dapat melihat data.' }}</p>
 </div>

 <div class="grid4">
  @foreach (['transfer' => 'Transfer bank', 'ewallet' => 'E-wallet', 'qris' => 'QRIS', 'tunai' => 'Tunai'] as $k => $lbl)
  <div class="c {{ $siap[$k] ? 'g' : 'l' }} stat">
   <small>{{ $lbl }}</small>
   <b>{{ $siap[$k] ? 'Tersedia' : 'Belum aktif' }}</b>
   <small>{{ $siap[$k] ? 'Bisa dipilih donatur' : 'Tidak tampil sebagai pilihan' }}</small>
  </div>
  @endforeach
 </div>

 <div class="card">
  <h2 class="sub">Kode QRIS <small>Gambar resmi dari bank atau penyedia pembayaran UPZ</small></h2>
  <div class="qris-admin">
   @if ($qris)
   <img class="qris" src="{{ asset($qris) }}?v={{ @filemtime(public_path($qris)) }}" alt="QRIS UPZ BAZNAS" width="180" height="180">
   @else
   <div class="kosong-qris">QRIS belum diunggah</div>
   @endif
   <div>
    <p class="bantu">Unggah gambar kode QRIS yang diterbitkan resmi untuk UPZ (JPG, PNG, atau WebP, maksimal 2 MB). Sistem ini tidak membuat kode QRIS sendiri, karena kode harus berasal dari bank atau penyedia pembayaran yang menampung dana UPZ.</p>
    @if ($boleh)
    <form method="post" action="{{ route('pembayaran.proses') }}" enctype="multipart/form-data" class="acts">
     @csrf
     <input type="hidden" name="aksi" value="qris_unggah">
     <input type="file" name="qris" accept="image/jpeg,image/png,image/webp" required>
     <button class="btn sm" type="submit">{{ $qris ? 'Ganti QRIS' : 'Unggah QRIS' }}</button>
    </form>
    @if ($qris)
    <form method="post" action="{{ route('pembayaran.proses') }}" data-konfirmasi="Hapus gambar QRIS? Donatur tidak bisa lagi memilih QRIS.">
     @csrf
     <input type="hidden" name="aksi" value="qris_hapus">
     <button class="lnk del" type="submit">Hapus QRIS</button>
    </form>
    @endif
    @endif
   </div>
  </div>
 </div>

 <div class="bar">
  <span></span>
  @if ($boleh)
  <div class="acts">
   <button class="btn g sm" type="button" data-dialog="dlg-rek" data-rek data-judul="Tambah rekening atau e-wallet">+ Tambah</button>
  </div>
  @endif
 </div>

 <div class="card">
  <h2 class="sub">Rekening bank dan e-wallet <small>{{ count($daftar) }} data</small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>Nama</th>
      <th>Jenis</th>
      <th>Nomor</th>
      <th>Atas nama</th>
      <th>Status</th>
      @if ($boleh)
      <th class="r">Aksi</th>
      @endif
     </tr>
    </thead>
    <tbody>
    @if ($daftar->isEmpty())
     <tr>
      <td colspan="{{ $boleh ? 6 : 5 }}">
       <div class="empty"><b>Belum ada data</b></div>
      </td>
     </tr>
    @endif
    @foreach ($daftar as $r)
     @php $tampil = $r->aktif && $r->nomor !== ''; @endphp
     <tr>
      <td><b>{{ $r->nama }}</b></td>
      <td>{{ $r->jenis === 'bank' ? 'Bank' : 'E-wallet' }}</td>
      <td class="nw">{!! $r->nomor !== '' ? e($r->nomor) : '<small>Belum diisi</small>' !!}</td>
      <td>{!! $r->atas_nama !== '' ? e($r->atas_nama) : '<small>-</small>' !!}</td>
      <td><span class="badge {{ $tampil ? 'in' : 'off' }}">{{ $tampil ? 'Aktif' : 'Nonaktif' }}</span></td>
      @if ($boleh)
      <td class="r nw">
       <button class="lnk" type="button" data-dialog="dlg-rek" data-rek data-judul="Ubah {{ $r->nama }}" data-row="{{ json_encode(['id' => (int)$r->id, 'jenis' => $r->jenis, 'nama' => $r->nama, 'nomor' => $r->nomor, 'atas_nama' => $r->atas_nama, 'aktif' => (int)$r->aktif]) }}">Ubah</button>
       <button class="lnk del" type="button" data-dialog="dlg-hapus-rek" data-hapus-rek="{{ (int)$r->id }}" data-nama="{{ $r->nama }}">Hapus</button>
      </td>
      @endif
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
 </div>
</section>

@if ($boleh)
<dialog class="dlg" id="dlg-rek" aria-labelledby="r-judul">
 <form class="dbox" method="post" action="{{ route('pembayaran.proses') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="r-judul"><span>Tambah rekening atau e-wallet</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" id="r-id" value="0">
  <label for="r-jenis">Jenis</label>
  <select id="r-jenis" name="jenis">
   <option value="bank">Bank</option>
   <option value="ewallet">E-wallet</option>
  </select>
  <label for="r-nama">Nama bank atau e-wallet</label>
  <input id="r-nama" type="text" name="nama" maxlength="60" required>
  <label for="r-nomor">Nomor rekening atau nomor e-wallet</label>
  <input id="r-nomor" type="text" name="nomor" maxlength="40" inputmode="numeric">
  <label for="r-an">Atas nama</label>
  <input id="r-an" type="text" name="atas_nama" maxlength="100">
  <label class="cek">
   <input id="r-aktif" type="checkbox" name="aktif" value="1">
   <span>Aktif, tampil sebagai pilihan bagi donatur</span>
  </label>
  <small class="bantu">Perubahan nomor hanya berlaku untuk donasi baru. Donasi yang sudah dibuat tetap menyimpan nomor yang dipilih donatur saat itu.</small>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Simpan</button>
  </div>
 </form>
</dialog>

<dialog class="dlg" id="dlg-hapus-rek" aria-labelledby="h-judul">
 <form class="dbox" method="post" action="{{ route('pembayaran.proses') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="h-judul"><span>Hapus data ini?</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="hapus">
  <input type="hidden" name="id" id="h-id">
  <p><b id="h-nama"></b> akan dihapus dari daftar pilihan donatur.</p>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Hapus</button>
  </div>
 </form>
</dialog>
@endif
@endsection
