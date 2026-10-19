@extends('layouts.app')

@section('title', 'Riwayat aktivitas')

@section('content')
<section class="top">
 <div class="head">
  <h1 class="tag">Riwayat aktivitas</h1>
  <p>Catatan siapa melakukan apa di sistem: login, perubahan transaksi, donasi, dan perubahan data oleh seluruh pengguna.</p>
 </div>

 <div class="grid4">
  <div class="c g stat">
   <small>Total aktivitas</small>
   <b>{{ (int)$ringkasan['semua'] }}</b>
   <small>Seluruh catatan tersimpan</small>
  </div>
  <div class="c l stat">
   <small>Hari ini</small>
   <b>{{ (int)$ringkasan['hari_ini'] }}</b>
   <small>Aktivitas sejak pukul 00.00</small>
  </div>
  <div class="c l stat">
   <small>Pengguna aktif</small>
   <b>{{ (int)$ringkasan['aktif'] }}</b>
   <small>Dalam 7 hari terakhir</small>
  </div>
  <div class="c d stat">
   <small>Paling sering</small>
   <b style="font-size:1.1rem">{{ $terbanyak ? audit_label_aksi($terbanyak->aksi) : '-' }}</b>
   <small>{{ $terbanyak ? (int)$terbanyak->n . ' kali tercatat' : 'Belum ada data' }}</small>
  </div>
 </div>

 <form class="card fbox audit" method="get">
  <div>
   <label for="cari">Cari pengguna, objek, atau alamat IP</label>
   <input id="cari" type="text" name="cari" value="{{ $cari }}">
  </div>
  <div>
   <label for="aksi">Jenis aktivitas</label>
   <select id="aksi" name="aksi">
    <option value="">Semua aktivitas</option>
    @foreach ($daftarAksi as $kode => $nama)
    <option value="{{ $kode }}" {{ $aksi === $kode ? 'selected' : '' }}>{{ $nama }}</option>
    @endforeach
   </select>
  </div>
  <div class="acts">
   <button class="btn sm" type="submit">Terapkan</button>
   <a class="btn o sm" href="{{ route('audit.index') }}">Reset</a>
  </div>
 </form>

 <div class="card">
  <div class="subbar">
   <h2 class="sub">Log aktivitas <small>{{ $total }} catatan</small></h2>
   <div class="dd">
    <button class="btn o sm dd-btn bahaya" type="button" aria-expanded="false" aria-controls="dd-hapus">Hapus riwayat</button>
    <div class="dd-menu" id="dd-hapus" hidden>
     @foreach ($rentangLog as $kode => [$nama, $kondisi])
      @php $n = (int)$hitung[$kode]; @endphp
      <form method="post" action="{{ route('audit.clear') }}" data-konfirmasi="{{ $pesanHapus[$kode] }}">
       @csrf
       <input type="hidden" name="rentang" value="{{ $kode }}">
       <button class="dd-item {{ $kode === 'semua' ? 'bahaya' : '' }}" type="submit" {{ $n === 0 ? 'disabled' : '' }}>Hapus {{ $nama }}<small>{{ $n }} catatan</small></button>
      </form>
     @endforeach
    </div>
   </div>
  </div>

  <div class="scroll">
   <table class="tbl log">
    <thead>
     <tr>
      <th>Kapan</th>
      <th>Siapa</th>
      <th>Apa yang terjadi</th>
      <th>IP</th>
     </tr>
    </thead>
    <tbody>
    @if ($baris->isEmpty())
     <tr>
      <td colspan="4">
       <div class="empty">
        <b>Belum ada catatan</b>
        <p>Aktivitas akan muncul di sini setelah ada yang login atau mengubah data.</p>
       </div>
      </td>
     </tr>
    @endif
    @foreach ($baris as $r)
     @php
       $waktu = $r->waktu instanceof \Carbon\Carbon ? $r->waktu : \Carbon\Carbon::parse($r->waktu);
       [$ipUtama, $ipKecil] = audit_label_ip($r->ip);
       $kls = audit_golongan($r->aksi);
       $rel = audit_waktu_lalu($waktu);
       $uraian = audit_uraian_ringkas($r);
     @endphp
     <tr>
      <td class="nw">
       {{ $waktu->format('d/m/Y') }}
       <small>{{ $waktu->format('H:i') }}{{ $rel !== '' ? ', ' . $rel : '' }}</small>
      </td>
      <td><b>{{ $r->username ?? 'Tamu' }}</b></td>
      <td class="detail">
       <p class="kalimat"><i class="titik k-{{ $kls }}"></i>{{ $uraian }}</p>
      </td>
      <td class="nw ip" title="{{ $ipUtama }}">{{ $r->ip ?: '-' }}</td>
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
@endsection
