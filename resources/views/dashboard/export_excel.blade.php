<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
body{font-family:Arial,sans-serif;font-size:11pt;color:#222}
.c{text-align:center}.r{text-align:right}
table{border-collapse:collapse;width:100%}
th{background:#191A23;color:#B9FF66;border:1px solid #999;padding:8px 6px}
td{border:1px solid #ccc;padding:6px 8px}
.sum{background:#F3F3F3;font-weight:bold}
</style>
</head>
<body>
<div class="c"><b style="font-size:15pt">UNIT PENGUMPUL ZAKAT (UPZ) BAZNAS</b><br><b>PT PETROKIMIA GRESIK</b><br>LAPORAN ARUS KAS PEMASUKAN DAN PENGELUARAN DANA<br>
Periode: {{ $periode }} | Dicetak: {{ date('d/m/Y H:i') }} WIB oleh {{ $petugas->name ?? 'Petugas' }}</div>

@php
    $masuk = 0;
    $keluar = 0;
@endphp
<table>
 <thead><tr><th>No</th><th>Tanggal</th><th>Jenis</th><th>Nama donatur atau penerima manfaat</th><th>Kategori</th><th>Pemasukan (Rp)</th><th>Pengeluaran (Rp)</th></tr></thead>
 <tbody>
 @if ($daftar->isEmpty())
  <tr><td colspan="7" class="c">Tidak ada data yang sesuai dengan filter.</td></tr>
 @endif
 @foreach ($daftar as $i => $d)
  @php
    $in = $d->tipe === 'masuk';
    if ($in) {
        $masuk += $d->jumlah;
    } else {
        $keluar += $d->jumlah;
    }
    $tgl = $d->tanggal instanceof \Carbon\Carbon ? $d->tanggal : \Carbon\Carbon::parse($d->tanggal);
  @endphp
  <tr>
   <td class="c">{{ $i + 1 }}</td>
   <td class="c">{{ $tgl->format('d/m/Y') }}</td>
   <td class="c">{{ $in ? 'Pemasukan' : 'Pengeluaran' }}</td>
   <td>{{ $d->nama_pihak }}</td>
   <td>{{ $d->kategori?->nama ?? '-' }}</td>
   <td class="r">{{ $in ? number_format($d->jumlah, 0, ',', '.') : '-' }}</td>
   <td class="r">{{ $in ? '-' : number_format($d->jumlah, 0, ',', '.') }}</td>
  </tr>
 @endforeach
 </tbody>
 <tfoot>
  <tr class="sum"><td colspan="5" class="r">TOTAL PERIODE INI</td><td class="r">Rp {{ number_format($masuk, 0, ',', '.') }}</td><td class="r">Rp {{ number_format($keluar, 0, ',', '.') }}</td></tr>
  <tr class="sum"><td colspan="5" class="r">SURPLUS ATAU DEFISIT PERIODE INI</td><td colspan="2" class="r">Rp {{ number_format($masuk - $keluar, 0, ',', '.') }}</td></tr>
  <tr class="sum"><td colspan="5" class="r">SALDO KAS SAAT INI (KESELURUHAN)</td><td colspan="2" class="r">Rp {{ number_format($saldoTotal, 0, ',', '.') }}</td></tr>
 </tfoot>
</table>

<p class="r" style="margin-top:35px">Gresik, {{ date('d-m-Y') }}<br>Petugas Pengelola Kas UPZ BAZNAS,<br><br><br><br><b>{{ $petugas->name ?? 'Petugas' }}</b><br>ID: @{{ $petugas->username ?? '' }}</p>
</body>
</html>
