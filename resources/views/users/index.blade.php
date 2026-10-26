@extends('layouts.app')

@section('title', 'Kelola pengguna')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Kelola pengguna</h1>
  <p>Akun petugas dan pendaftar. Hanya admin yang dapat memberi peran admin, bendahara, atau ketua.</p>
 </div>
 <div class="bar">
  <div class="pills" role="group" aria-label="Filter status email">
   <a class="pill {{ $verifikasi === 'semua' ? 'on' : '' }}" href="{{ route('users.index', array_filter(['cari' => $cari])) }}" @if ($verifikasi === 'semua') aria-current="page" @endif>Semua</a>
   <a class="pill {{ $verifikasi === 'belum' ? 'on' : '' }}" href="{{ route('users.index', array_filter(['cari' => $cari, 'verifikasi' => 'belum'])) }}" @if ($verifikasi === 'belum') aria-current="page" @endif>Belum verifikasi</a>
  </div>
  <form class="acts" method="get">
   @if ($verifikasi !== 'semua')
    <input type="hidden" name="verifikasi" value="{{ $verifikasi }}">
   @endif
   <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari data" aria-label="Cari" style="margin:0;width:16rem;max-width:100%">
   <button class="btn sm" type="submit">Cari</button>
   @if ($cari !== '' || $verifikasi !== 'semua')
    <a class="btn o sm" href="{{ route('users.index') }}">Reset</a>
   @endif
  </form>
  @if ($bisaUbah)
   <button class="btn g sm" type="button" data-dialog="crud-form" data-crud="tambah" data-judul="Tambah data">+ Tambah data</button>
  @endif
 </div>

 <div class="card">
  <h2 class="sub">Daftar data <small>{{ $total }} data{{ $verifikasi === 'belum' ? ' · belum verifikasi' : '' }}</small></h2>
  <div class="scroll">
   <table class="tbl">
    <thead>
     <tr>
      <th>No</th>
      <th>Nama lengkap</th>
      <th>Username</th>
      <th>Email</th>
      <th>Status email</th>
      <th>Peran</th>
      @if ($bisaUbah)
      <th class="r">Aksi</th>
      @endif
     </tr>
    </thead>
    <tbody>
    @if ($baris->isEmpty())
     <tr>
      <td colspan="7">
       <div class="empty"><b>Belum ada data</b><p>{{ $verifikasi === 'belum' ? 'Tidak ada akun belum verifikasi yang cocok dengan pencarian ini.' : 'Data yang cocok dengan pencarian tidak ditemukan.' }}</p></div>
      </td>
     </tr>
    @endif
    @foreach ($baris as $i => $r)
     <tr>
      <td>{{ ($baris->currentPage() - 1) * $baris->perPage() + $i + 1 }}</td>
      <td><b>{{ $r->name }}</b></td>
      <td>{{ $r->username }}</td>
      <td>{{ $r->email ?: '-' }}</td>
      <td>
       @if (! $r->email)
        <span class="badge off">Tanpa email</span>
       @elseif ($r->hasVerifiedEmail())
        <span class="badge in">Terverifikasi</span>
       @else
        <span class="badge wait">Belum verifikasi</span>
        @if ($bisaUbah)
         <form method="post" action="{{ route('users.resendVerification', $r->id) }}" style="display:inline;margin:0 0 0 .35rem;" data-konfirmasi="Kirim ulang link verifikasi ke {{ $r->email }}?">
          @csrf
          <button class="lnk" type="submit" title="Kirim ulang link verifikasi ke {{ $r->email }}">Kirim ulang</button>
         </form>
        @endif
       @endif
      </td>
      <td><i class="cat">{{ $peran[$r->role] ?? $r->role }}</i></td>
      @if ($bisaUbah)
       @php
         $rowData = [
           'id' => (int)$r->id,
           'name' => $r->name,
           'username' => $r->username,
           'email' => (string) $r->email,
           'role' => $r->role,
         ];
       @endphp
       <td class="r nw">
        <button class="lnk" type="button" data-dialog="crud-form" data-crud="ubah" data-judul="Ubah data" data-row="{{ json_encode($rowData) }}">Ubah</button>
        <button class="lnk del" type="button" data-dialog="crud-hapus" data-id="{{ (int)$r->id }}" data-nama="{{ $r->name }}">Hapus</button>
       </td>
      @endif
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
  @if ($baris->hasPages())
  <div class="pg">
   <small>Halaman {{ $baris->currentPage() }} dari {{ $baris->lastPage() }}</small>
   <div>
    @if (!$baris->onFirstPage())
     <a href="{{ $baris->previousPageUrl() }}">←</a>
    @endif
    @for ($i = max(1, $baris->currentPage() - 2); $i <= min($baris->lastPage(), $baris->currentPage() + 2); $i++)
     <a class="{{ $i === $baris->currentPage() ? 'on' : '' }}" href="{{ $baris->url($i) }}">{{ $i }}</a>
    @endfor
    @if ($baris->hasMorePages())
     <a href="{{ $baris->nextPageUrl() }}">→</a>
    @endif
   </div>
  </div>
  @endif
 </div>
</section>

@if ($bisaUbah)
<dialog class="dlg" id="crud-form" aria-labelledby="crud-judul">
 <form class="dbox" method="post" action="{{ route('users.store') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="crud-judul"><span>Tambah data</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="tambah">
  <input type="hidden" name="id" value="0">
  
  <label for="cf-name">Nama lengkap *</label>
  <input id="cf-name" type="text" name="name" maxlength="150" required data-f>

  <label for="cf-username">Username *</label>
  <input id="cf-username" type="text" name="username" maxlength="150" required data-f>

  <label for="cf-email">Email <span style="font-weight:normal;opacity:0.75">(untuk reset password)</span></label>
  <input id="cf-email" type="email" name="email" maxlength="100" data-f>

  <label for="cf-password">Password (minimal 8 karakter)</label>
  <div class="pw">
   <input id="cf-password" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Kosongkan bila tidak diganti" data-f>
   <button type="button" data-pw="cf-password" aria-label="Tampilkan password">
    <svg><use href="#eye"/></svg>
   </button>
  </div>

  <label for="cf-role">Peran *</label>
  <select id="cf-role" name="role" data-f>
   @foreach ($peran as $k => $lb)
   <option value="{{ $k }}">{{ $lb }}</option>
   @endforeach
  </select>

  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Simpan</button>
  </div>
 </form>
</dialog>

<dialog class="dlg" id="crud-hapus" aria-labelledby="ch-judul">
 <form class="dbox" method="post" action="{{ route('users.store') }}">
  <button class="x" type="button" data-close aria-label="Tutup">×</button>
  <h3 id="ch-judul"><span>Hapus data?</span></h3>
  @csrf
  <input type="hidden" name="aksi" value="hapus">
  <input type="hidden" name="id" id="ch-id">
  <p>Data <b id="ch-nama"></b> akan dihapus.</p>
  <div class="acts end">
   <button class="btn o" type="button" data-close>Batal</button>
   <button class="btn" type="submit">Ya, hapus</button>
  </div>
 </form>
</dialog>
@endif
@endsection
