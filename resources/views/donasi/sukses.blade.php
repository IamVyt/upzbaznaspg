@extends('layouts.portal')

@section('title', 'Donasi Diajukan - ' . $donasi->kode)

@section('content')
<section class="auth">
    <div class="c g">
        <h1>Alhamdulillah, Donasi Diajukan!</h1>
        <p>Terima kasih atas kebaikan Anda. Formulir donasi Anda dengan kode referensi <b>{{ $donasi->kode }}</b> telah berhasil dicatat dalam sistem.</p>
        <img class="mas" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS" width="340" height="342" decoding="async">
    </div>

    <div class="c l form">
        <h2><span class="tg">Instruksi Pembayaran</span></h2>
        <p>Silakan selesaikan pembayaran donasi Anda sejumlah:</p>

        <div style="background: #191A23; color: #B9FF66; padding: 18px; border-radius: 12px; text-align: center; margin: 16px 0;">
            <span style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: #fff;">Total yang Harus Dibayar:</span>
            <div style="font-size: 2rem; font-weight: 700; margin-top: 4px;">{{ format_rupiah($donasi->jumlah) }}</div>
        </div>

        <dl class="rinci" style="margin: 16px 0;">
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ccc;">
                <dt>Kode Donasi</dt>
                <dd><b>{{ $donasi->kode }}</b></dd>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ccc;">
                <dt>Program</dt>
                <dd>{{ $donasi->program_nama }}</dd>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ccc;">
                <dt>Paket</dt>
                <dd>{{ $donasi->paket }}</dd>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ccc;">
                <dt>Metode</dt>
                <dd>{{ strtoupper($donasi->metode) }} {{ $donasi->channel_nama ? '(' . $donasi->channel_nama . ')' : '' }}</dd>
            </div>
        </dl>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
            <a class="btn" href="{{ route('donasi.bayar', $donasi->kode) }}" style="text-align: center;">
                Selesaikan Pembayaran & Unggah Bukti →
            </a>
            <a class="btn o" href="{{ route('donasi.cetak', $donasi->kode) }}" target="_blank" style="text-align: center;">
                Cetak Tanda Terima Sementara
            </a>
            <a class="ul" href="{{ route('home') }}" style="text-align: center; margin-top: 6px;">Kembali ke Beranda</a>
        </div>
    </div>
</section>
@endsection
