@php
$resmi   = $d->status === 'diterima';
$petugas = $d->pemroses?->name ?? '';
[$labelStatus] = label_status_donasi($d->status);
$m = config('upz.metode_pembayaran.' . $d->metode . '.0', ucfirst($d->metode));

$tgl = function ($waktu) {
    return $waktu ? date('d/m/Y H:i', strtotime($waktu)) . ' WIB' : '-';
};
$bagian   = explode(' a.n. ', (string)$d->channel_info, 2);
$tujuan   = $d->channel_info ? ($d->channel_nama . ', ' . $bagian[0] . (isset($bagian[1]) ? ' a.n. ' . $bagian[1] : '')) : ($d->channel_nama ?: $m);
$judul    = $resmi ? 'Bukti Penyaluran Donasi' : 'Tanda Terima Sementara';
$kelasCap = ['diterima' => 'hijau', 'dibayar' => 'biru', 'menunggu' => 'kuning'][$d->status] ?? 'merah';
$teksCap  = $resmi ? 'SUDAH TERSALURKAN' : ($d->status === 'dibayar' || $d->status === 'menunggu' ? 'BELUM TERVERIFIKASI' : strtoupper($labelStatus));

$baris = [
    ['Nomor referensi', $d->kode],
    ['Nama donatur', $d->nama . ($d->nip ? ' (' . $d->nip . ')' : '')],
    ['Program', $d->program_nama],
    ['Paket', $d->paket],
    ['Nominal', rupiah($d->jumlah)],
    ['Metode pembayaran', $m],
    ['Tujuan pembayaran', $tujuan],
    ['Tanggal pesanan', $tgl($d->created_at)],
    ['Dilaporkan dibayar', $tgl($d->dibayar_at)],
];
if ($resmi) {
    $baris[] = ['Diterima dan dicatat', $tgl($d->diproses_at)];
    $baris[] = ['Diverifikasi oleh', $petugas !== '' ? $petugas : 'Pengurus UPZ'];
}
if ($d->status === 'ditolak' && $d->alasan_tolak) $baris[] = ['Alasan penolakan', $d->alasan_tolak];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $judul . ' ' . $d->kode }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
body{margin:0;background:#f1f1f1;color:#191A23;font-family:'Space Grotesk',Arial,sans-serif;font-size:15px;line-height:1.5}
.kertas{max-width:780px;margin:1.5rem auto;background:#fff;padding:2.4rem 2.6rem;border:1px solid #191A23;border-radius:14px;box-shadow:0 4px 0 #191A23}
.kop{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;border-bottom:2px solid #191A23;padding-bottom:1rem;margin-bottom:1.4rem}
.kop b{display:block;font-size:1.25rem}
.kop small{opacity:.7}
h1{font-size:1.7rem;margin:0 0 .2rem;font-weight:500}
.kode{font-size:1.15rem;letter-spacing:.04em;margin:0 0 1.2rem}
.cap{display:inline-block;padding:.35rem 1rem;border:2px solid #191A23;border-radius:8px;font-weight:700;letter-spacing:.06em;transform:rotate(-3deg);margin:.2rem 0 1.2rem}
.cap.hijau{background:#B9FF66}
.cap.biru{background:#d9ecff}
.cap.kuning{background:#fff1b8}
.cap.merah{background:#ffd9d9}
table{width:100%;border-collapse:collapse}
td{padding:.55rem .2rem;border-bottom:1px solid #d8d8d8;vertical-align:top}
td:first-child{width:38%;opacity:.7}
td:last-child{font-weight:500}
.catatan{margin-top:1.3rem;padding:.9rem 1.1rem;border:1px dashed #191A23;border-radius:10px;font-size:.9rem}
.tanda{display:flex;justify-content:space-between;gap:2rem;margin-top:2.2rem;font-size:.9rem}
.tanda div{flex:1;text-align:center}
.tanda .garis{height:3.4rem;border-bottom:1px solid #191A23;margin-bottom:.3rem}
.kaki{margin-top:1.6rem;font-size:.8rem;opacity:.65}
.tombol{max-width:780px;margin:1rem auto 0;display:flex;gap:.8rem;justify-content:flex-end;padding:0 .5rem}
.tombol a,.tombol button{font:inherit;padding:.7rem 1.4rem;border-radius:.8rem;border:1px solid #191A23;background:#fff;color:#191A23;cursor:pointer;text-decoration:none}
.tombol button{background:#191A23;color:#fff}
@media print{
 @page{size:A4;margin:16mm}
 body{background:#fff}
 .kertas{border:0;box-shadow:none;margin:0;max-width:none;padding:0;border-radius:0}
 .tombol{display:none!important}
 *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>
<div class="tombol">
 <a href="{{ route('donasi.sukses', $d->kode) }}">Kembali</a>
 <button type="button" onclick="window.print()">Cetak atau simpan PDF</button>
</div>

<main class="kertas">
 <div class="kop">
  <div><b>UPZ BAZNAS Petrokimia Gresik</b><small>Layanan zakat, infak, dan sedekah</small></div>
  <small>Dicetak {{ date('d/m/Y H:i') }} WIB</small>
 </div>

 <h1>{{ $judul }}</h1>
 <p class="kode">{{ $d->kode }}</p>
 <div class="cap {{ $kelasCap }}">{{ $teksCap }}</div>

 <table>
  @foreach ($baris as [$label, $nilai])
  <tr><td>{{ $label }}</td><td>{{ $nilai }}</td></tr>
  @endforeach
 </table>

 @if ($resmi)
 <div class="catatan">Donasi ini telah <b>diterima dan dicatat dalam pembukuan kas</b> UPZ BAZNAS Petrokimia Gresik. Keaslian dokumen dapat diperiksa pengurus UPZ dengan nomor referensi di atas.</div>
 <div class="tanda">
  <div><div class="garis"></div>Donatur<br>{{ $d->nama }}</div>
  <div><div class="garis"></div>Petugas UPZ<br>{{ $petugas !== '' ? $petugas : 'Pengurus UPZ' }}</div>
 </div>
 @else
 <div class="catatan"><b>Dokumen ini belum menjadi bukti penerimaan resmi.</b> Status saat ini: {{ $labelStatus }}. Bukti penyaluran resmi dapat dicetak setelah pengurus UPZ memeriksa pembayaran dan mengubah statusnya menjadi Sudah tersalurkan. Simpan juga bukti transfer atau bukti pembayaran Anda.</div>
 @endif

 <p class="kaki">Dokumen dibuat otomatis oleh sistem UPZ BAZNAS PG. Nomor referensi {{ $d->kode }}.</p>
</main>
</body>
</html>