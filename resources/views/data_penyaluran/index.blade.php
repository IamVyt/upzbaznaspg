@extends('layouts.app')

@section('title', 'Data Penyaluran Program')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Data Penyaluran</h1>
        <p>Ringkasan realisasi donasi yang telah berhasil disalurkan melalui program-program UPZ BAZNAS Petrokimia Gresik.</p>
    </div>

    <div class="grid3" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 1.5rem;">
        @foreach ($rekapProgram as $rp)
            <div class="c {{ $rp['warna'] }}" style="padding: 20px; border-radius: 14px; border: 2px solid var(--ink, #191A23); box-shadow: 0 4px 0 #191A23;">
                <h3 style="margin: 0 0 8px 0; font-size: 1.25rem;">{{ $rp['nama'] }}</h3>
                <b style="font-size: 1.4rem; display: block; margin-bottom: 6px;">{{ format_rupiah($rp['total_donasi']) }}</b>
                <small style="display: block; opacity: 0.85;">Dari {{ $rp['jumlah_donatur'] }} donasi tersalurkan</small>
            </div>
        @endforeach
    </div>

    <div class="card" style="margin-top: 2rem;">
        <h2 class="sub">Riwayat Penyaluran Donasi Terkonfirmasi</h2>
        <div class="scroll">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Tanggal Terima</th>
                        <th>Donatur</th>
                        <th>Program & Paket</th>
                        <th>Nominal</th>
                        <th>Kanal Pembayaran</th>
                        <th class="r">Kuitansi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatPenyaluran as $d)
                        <tr>
                            <td class="nw">
                                {{ $d->diproses_at ? $d->diproses_at->format('d/m/Y H:i') : $d->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <b>{{ $d->nama_tampil }}</b>
                                @if ($d->samarkan_nama)
                                    <i class="cat" style="font-size: 0.75rem;">(Hamba Allah)</i>
                                @endif
                                <small style="display: block; color: #555;">{{ $d->kontak }}</small>
                            </td>
                            <td>
                                <b>{{ $d->program_nama }}</b><br>
                                <small>{{ $d->paket }} ({{ $d->kode }})</small>
                            </td>
                            <td class="nw" style="font-weight: 700; color: #15803d;">
                                {{ format_rupiah($d->jumlah) }}
                            </td>
                            <td>
                                {{ strtoupper($d->metode) }} {{ $d->channel_nama ? '- ' . $d->channel_nama : '' }}
                            </td>
                            <td class="r nw">
                                <a class="btn o sm" href="{{ route('donasi.cetak', $d->kode) }}" target="_blank">
                                    Cetak Kuitansi ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 24px;">
                                Belum ada donasi yang disalurkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $riwayatPenyaluran->links() }}
        </div>
    </div>
</section>
@endsection
