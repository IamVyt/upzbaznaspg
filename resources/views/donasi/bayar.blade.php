@extends('layouts.portal')

@section('title', 'Pembayaran Donasi - ' . $donasi->kode)

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Instruksi Pembayaran</h1>
        <p>Kode Referensi Donasi: <b>{{ $donasi->kode }}</b></p>
    </div>

    <ol class="steps" aria-label="Langkah penyaluran">
        <li class="done"><b>✓</b>Pilih paket</li>
        <li class="done"><b>✓</b>Isi data</li>
        <li class="{{ $donasi->status === 'diterima' ? 'done' : 'on' }}"><b>{{ $donasi->status === 'diterima' ? '✓' : '3' }}</b>Bayar & Konfirmasi</li>
    </ol>
</section>

<section class="bayar" style="margin-top: 20px;">
    <div class="c g ringkas">
        <small>Rincian Tagihan</small>
        <h2>{{ $donasi->program_nama }}</h2>
        <p class="pk">{{ $donasi->paket }}</p>
        <b class="besar">{{ format_rupiah($donasi->jumlah) }}</b>

        <div style="margin-top: 14px; padding: 12px; background: #fff; border-radius: 8px; border: 1px solid #ddd;">
            <div style="font-size: 0.85rem; color: #666;">Status Donasi:</div>
            <div style="font-size: 1.1rem; font-weight: 700; margin-top: 4px;">
                @if ($donasi->status === 'menunggu')
                    <span style="color: #d97706;">⏳ Menunggu Pembayaran</span>
                @elseif ($donasi->status === 'dibayar')
                    <span style="color: #2563eb;">🔍 Menunggu Verifikasi Petugas</span>
                @elseif ($donasi->status === 'diterima')
                    <span style="color: #16a34a;">✅ Donasi Diterima & Tersalurkan</span>
                @elseif ($donasi->status === 'ditolak')
                    <span style="color: #dc2626;">❌ Ditolak ({{ $donasi->alasan_tolak }})</span>
                @elseif ($donasi->status === 'dibatalkan')
                    <span style="color: #6b7280;">🚫 Dibatalkan</span>
                @endif
            </div>
        </div>

        <div style="margin-top: 16px;">
            <p><b>Nama Donatur:</b> {{ $donasi->nama_tampil }}</p>
            <p><b>Tanggal:</b> {{ $donasi->created_at->format('d/m/Y H:i') }}</p>
            @if ($donasi->catatan)
                <p><b>Catatan:</b> <i>"{{ $donasi->catatan }}"</i></p>
            @endif
        </div>

        <div style="margin-top: 20px;">
            <a class="btn o sm" href="{{ route('donasi.cetak', $donasi->kode) }}" target="_blank">
                Cetak Bukti / Tanda Terima ↗
            </a>
        </div>
    </div>

    <div class="c l form">
        @if ($donasi->status === 'menunggu' || $donasi->status === 'dibayar')
            <h2><span class="tg">Rekening Tujuan</span></h2>
            <div style="background: #fdfdfd; border: 2px dashed var(--ink, #191A23); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                <p style="margin: 0 0 6px 0; font-size: 0.95rem;">Transfer tepat sejumlah:</p>
                <div style="font-size: 1.8rem; font-weight: 700; color: #191A23; margin-bottom: 12px;">
                    {{ format_rupiah($donasi->jumlah) }}
                </div>

                @if ($donasi->metode === 'transfer')
                    <div style="background: #f4f6f0; padding: 12px; border-radius: 8px;">
                        <span style="font-size: 0.85rem; text-transform: uppercase; color: #666;">Bank Tujuan:</span>
                        <div style="font-size: 1.2rem; font-weight: 700;">{{ $donasi->channel_nama ?? 'Bank BSI' }}</div>
                        <div style="font-size: 1.1rem; margin: 4px 0;">No. Rekening: <b>{{ $donasi->channel_info ?? config('upz.site.rekening.nomor') }}</b></div>
                    </div>
                @elseif ($donasi->metode === 'ewallet')
                    <div style="background: #f4f6f0; padding: 12px; border-radius: 8px;">
                        <span style="font-size: 0.85rem; text-transform: uppercase; color: #666;">E-Wallet:</span>
                        <div style="font-size: 1.2rem; font-weight: 700;">{{ $donasi->channel_nama ?? 'E-Wallet' }}</div>
                        <div style="font-size: 1.1rem; margin: 4px 0;">Nomor Tujuan: <b>{{ $donasi->channel_info ?? config('upz.site.wa') }}</b></div>
                    </div>
                @elseif ($donasi->metode === 'qris')
                    <div style="text-align: center; padding: 12px;">
                        <p>Silakan scan QRIS resmi UPZ BAZNAS Petrokimia Gresik:</p>
                        <div style="display: inline-block; background: #fff; padding: 12px; border: 1px solid #ccc; border-radius: 8px;">
                            <img src="{{ asset('img/maskot.svg') }}" width="180" height="180" alt="QRIS UPZ">
                        </div>
                    </div>
                @else
                    <div style="background: #f4f6f0; padding: 12px; border-radius: 8px;">
                        <b>Pembayaran Tunai:</b>
                        <p style="margin: 4px 0;">Silakan datang langsung ke kantor {{ config('upz.site.lengkap') }} di {{ config('upz.site.alamat') }} dengan membawa kode referensi <b>{{ $donasi->kode }}</b>.</p>
                    </div>
                @endif
            </div>

            <h2><span class="tg">Unggah Bukti Transfer</span></h2>
            <form method="POST" action="{{ route('donasi.uploadBukti', $donasi->kode) }}" enctype="multipart/form-data">
                @csrf
                <p>Setelah melakukan transfer, silakan unggah foto atau tangkapan layar bukti transfer agar petugas dapat memverifikasi:</p>

                <label for="bukti">Berkas Bukti (JPG, PNG, atau WebP, maks 4MB)</label>
                <input id="bukti" type="file" name="bukti" accept="image/jpeg,image/png,image/webp" required style="padding: 10px; margin-bottom: 14px;">

                @if ($donasi->bukti)
                    <div style="margin: 12px 0;">
                        <p style="font-size: 0.9rem; margin-bottom: 6px;">Bukti yang sudah diunggah saat ini:</p>
                        <img src="{{ route('donasi.bukti', $donasi->kode) }}" alt="Bukti Transfer" style="max-width: 200px; border-radius: 8px; border: 1px solid #ccc;">
                    </div>
                @endif

                <button class="btn" type="submit" style="width: 100%;">
                    {{ $donasi->bukti ? 'Perbarui Bukti Pembayaran' : 'Saya Sudah Bayar & Unggah Bukti' }}
                </button>
            </form>

            @if ($donasi->status === 'menunggu')
                <form method="POST" action="{{ route('donasi.batal', $donasi->kode) }}" style="margin-top: 16px;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan donasi ini?');">
                    @csrf
                    <button type="submit" class="lnk del" style="background: none; border: none; color: #dc2626; cursor: pointer; text-decoration: underline; font: inherit;">
                        Batalkan Donasi Ini
                    </button>
                </form>
            @endif
        @else
            <h2><span class="tg">Bukti Pembayaran</span></h2>
            @if ($donasi->bukti)
                <div style="margin: 16px 0;">
                    <img src="{{ route('donasi.bukti', $donasi->kode) }}" alt="Bukti Pembayaran" style="max-width: 100%; max-height: 400px; border-radius: 8px; border: 1px solid #ccc;">
                </div>
            @endif
            <p>Donasi ini telah diproses dengan status: <b>{{ ucfirst($donasi->status) }}</b>.</p>
            <a class="btn" href="{{ route('donasi.cetak', $donasi->kode) }}" target="_blank">Cetak Kuitansi Resmi</a>
        @endif
    </div>
</section>
@endsection
