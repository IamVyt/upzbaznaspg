@php
    $resmi = $donasi->status === 'diterima';
    $petugas = $donasi->pemroses?->name ?? 'Pengurus UPZ';
    $judul = $resmi ? 'Bukti Penyaluran Donasi' : 'Tanda Terima Sementara';
    $kelasCap = [
        'diterima' => 'hijau',
        'dibayar' => 'biru',
        'menunggu' => 'kuning',
    ][$donasi->status] ?? 'merah';

    $teksCap = $resmi ? 'SUDAH TERSALURKAN' : ($donasi->status === 'dibayar' || $donasi->status === 'menunggu' ? 'BELUM TERVERIFIKASI' : strtoupper($donasi->status));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - {{ $donasi->kode }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f1f1; color: #191A23; font-family: 'Space Grotesk', Arial, sans-serif; font-size: 15px; line-height: 1.5; }
        .kertas { max-width: 780px; margin: 1.5rem auto; background: #fff; padding: 2.4rem 2.6rem; border: 1px solid #191A23; border-radius: 14px; box-shadow: 0 4px 0 #191A23; }
        .kop { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; border-bottom: 2px solid #191A23; padding-bottom: 1rem; margin-bottom: 1.4rem; }
        .kop b { display: block; font-size: 1.25rem; }
        .kop small { opacity: .7; }
        h1 { font-size: 1.7rem; margin: 0 0 .2rem; font-weight: 500; }
        .kode { font-size: 1.15rem; letter-spacing: .04em; margin: 0 0 1.2rem; }
        .cap { display: inline-block; padding: .35rem 1rem; border: 2px solid #191A23; border-radius: 8px; font-weight: 700; letter-spacing: .06em; transform: rotate(-3deg); margin: .2rem 0 1.2rem; }
        .cap.hijau { background: #B9FF66; }
        .cap.biru { background: #d9ecff; }
        .cap.kuning { background: #fff1b8; }
        .cap.merah { background: #ffd9d9; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: .55rem .2rem; border-bottom: 1px solid #d8d8d8; vertical-align: top; }
        td:first-child { width: 38%; opacity: .7; }
        td:last-child { font-weight: 500; }
        .catatan { margin-top: 1.3rem; padding: .9rem 1.1rem; border: 1px dashed #191A23; border-radius: 10px; font-size: .9rem; }
        .tanda { display: flex; justify-content: space-between; gap: 2rem; margin-top: 2.2rem; font-size: .9rem; }
        .tanda div { flex: 1; text-align: center; }
        .tanda .garis { height: 3.4rem; border-bottom: 1px solid #191A23; margin-bottom: .3rem; }
        .kaki { margin-top: 1.6rem; font-size: .8rem; opacity: .65; }
        .tombol { max-width: 780px; margin: 1rem auto 0; display: flex; gap: .8rem; justify-content: flex-end; padding: 0 .5rem; }
        .tombol a, .tombol button { font: inherit; padding: .7rem 1.4rem; border-radius: .8rem; border: 1px solid #191A23; background: #fff; color: #191A23; cursor: pointer; text-decoration: none; }
        .tombol button { background: #191A23; color: #fff; }
        @media print {
            @page { size: A4; margin: 16mm; }
            body { background: #fff; }
            .kertas { border: 0; box-shadow: none; margin: 0; max-width: none; padding: 0; border-radius: 0; }
            .tombol { display: none !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="tombol">
        <a href="{{ route('donasi.bayar', $donasi->kode) }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak atau simpan PDF</button>
    </div>

    <main class="kertas">
        <div class="kop">
            <div>
                <b>{{ config('upz.site.lengkap') }}</b>
                <small>Layanan zakat, infak, dan sedekah</small>
            </div>
            <small>Dicetak {{ now()->format('d/m/Y H:i') }} WIB</small>
        </div>

        <h1>{{ $judul }}</h1>
        <p class="kode">{{ $donasi->kode }}</p>
        <div class="cap {{ $kelasCap }}">{{ $teksCap }}</div>

        <table>
            <tr><td>Nomor referensi</td><td>{{ $donasi->kode }}</td></tr>
            <tr><td>Nama donatur</td><td>{{ $donasi->nama }}{{ $donasi->nip ? ' (' . $donasi->nip . ')' : '' }}</td></tr>
            <tr><td>Program</td><td>{{ $donasi->program_nama }}</td></tr>
            <tr><td>Paket</td><td>{{ $donasi->paket }}</td></tr>
            <tr><td>Nominal</td><td>{{ format_rupiah($donasi->jumlah) }}</td></tr>
            <tr><td>Metode pembayaran</td><td>{{ strtoupper($donasi->metode) }}</td></tr>
            <tr><td>Kanal pembayaran</td><td>{{ $donasi->channel_nama ?? '-' }} ({{ $donasi->channel_info ?? '-' }})</td></tr>
            <tr><td>Tanggal diajukan</td><td>{{ $donasi->created_at->format('d/m/Y H:i') }} WIB</td></tr>
            @if ($donasi->dibayar_at)
                <tr><td>Dilaporkan dibayar</td><td>{{ $donasi->dibayar_at->format('d/m/Y H:i') }} WIB</td></tr>
            @endif
            @if ($resmi && $donasi->diproses_at)
                <tr><td>Diterima dan dicatat</td><td>{{ $donasi->diproses_at->format('d/m/Y H:i') }} WIB</td></tr>
                <tr><td>Diverifikasi oleh</td><td>{{ $petugas }}</td></tr>
            @endif
            @if ($donasi->status === 'ditolak')
                <tr><td>Alasan penolakan</td><td>{{ $donasi->alasan_tolak ?? '-' }}</td></tr>
            @endif
        </table>

        @if ($resmi)
            <div class="catatan">
                Donasi ini telah <b>diterima dan dicatat dalam pembukuan kas</b> {{ config('upz.site.lengkap') }}. Keaslian dokumen dapat diverifikasi oleh pengurus UPZ menggunakan nomor referensi di atas.
            </div>
            <div class="tanda">
                <div><div class="garis"></div>Donatur<br>{{ $donasi->nama }}</div>
                <div><div class="garis"></div>Petugas UPZ<br>{{ $petugas }}</div>
            </div>
        @else
            <div class="catatan">
                <b>Dokumen ini tanda terima sementara.</b> Status saat ini: <b>{{ strtoupper($donasi->status) }}</b>. Bukti penyaluran resmi dapat dicetak setelah pengurus UPZ memverifikasi pembayaran Anda.
            </div>
        @endif

        <p class="kaki">Dokumen dibuat secara otomatis oleh sistem {{ config('upz.site.nama') }}. Kode: {{ $donasi->kode }}.</p>
    </main>
</body>
</html>
