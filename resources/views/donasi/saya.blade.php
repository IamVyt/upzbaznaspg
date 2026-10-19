@extends('layouts.portal')

@section('title', 'Riwayat Donasi Saya')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Donasi Saya</h1>
        <p>Daftar seluruh riwayat donasi online yang pernah Anda ajukan melalui UPZ BAZNAS Petrokimia Gresik.</p>
    </div>

    @if ($donasiSaya->isEmpty())
        <div class="kosong-donatur" style="margin-top: 30px;">
            <p><b>Anda belum memiliki riwayat donasi.</b></p>
            <p>Mari mulai berbagi kebaikan melalui program-program pilihan kami.</p>
            <a class="btn" href="{{ route('donasi.create') }}">Salurkan Donasi Pertama</a>
        </div>
    @else
        <div style="background: #fff; border: 2px solid var(--ink, #191A23); border-radius: 14px; overflow: hidden; margin-top: 20px; box-shadow: 0 4px 0 #191A23;">
            <table class="tabel" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f4f6f0; border-bottom: 2px solid #191A23; text-align: left;">
                        <th style="padding: 12px 16px;">Kode Donasi</th>
                        <th style="padding: 12px 16px;">Tanggal</th>
                        <th style="padding: 12px 16px;">Program & Paket</th>
                        <th style="padding: 12px 16px;">Jumlah</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($donasiSaya as $d)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px 16px; font-weight: 700;">
                                <a href="{{ route('donasi.bayar', $d->kode) }}" style="color: #191A23; text-decoration: underline;">
                                    {{ $d->kode }}
                                </a>
                            </td>
                            <td style="padding: 12px 16px;">
                                {{ $d->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="padding: 12px 16px;">
                                <b>{{ $d->program_nama }}</b><br>
                                <small style="color: #666;">{{ $d->paket }}</small>
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700;">
                                {{ format_rupiah($d->jumlah) }}
                            </td>
                            <td style="padding: 12px 16px;">
                                @if ($d->status === 'menunggu')
                                    <span class="badge kuning" style="padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; background: #fff1b8;">Menunggu Bayar</span>
                                @elseif ($d->status === 'dibayar')
                                    <span class="badge biru" style="padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; background: #d9ecff;">Menunggu Verifikasi</span>
                                @elseif ($d->status === 'diterima')
                                    <span class="badge hijau" style="padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; background: #B9FF66;">Tersalurkan</span>
                                @elseif ($d->status === 'ditolak')
                                    <span class="badge merah" style="padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; background: #ffd9d9;">Ditolak</span>
                                @else
                                    <span class="badge abu" style="padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; background: #eee;">Dibatalkan</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <a class="btn o sm" href="{{ route('donasi.bayar', $d->kode) }}" style="padding: 6px 12px; font-size: 0.85rem;">
                                    Detail / Bayar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $donasiSaya->links() }}
        </div>
    @endif
</section>
@endsection
