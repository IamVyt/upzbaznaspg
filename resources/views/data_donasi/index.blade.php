@extends('layouts.app')

@section('title', 'Data Donatur & Donasi')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Data Donasi</h1>
        <p>Seluruh donasi yang telah terverifikasi dan masuk kas, beserta data kontak donatur untuk keperluan arsip dan pelaporan.</p>
    </div>

    <div class="kpi" style="margin-top: 1rem;">
        <div class="c g">
            <span class="tg">Total Dana Diterima</span>
            <b>{{ format_rupiah($totalNominal) }}</b>
            <small>Dari filter saat ini</small>
        </div>
        <div class="c l">
            <span class="tg">Total Transaksi Donasi</span>
            <b>{{ $totalDonasi }} Transaksi</b>
            <small>Donatur terverifikasi</small>
        </div>
    </div>

    <div class="bar" style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;">
        <form class="acts" method="GET" action="{{ route('data_donasi.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <input class="cari" type="text" name="q" value="{{ $q }}" placeholder="Cari nama, kode, kontak..." aria-label="Cari donasi" style="min-width: 220px;">
            <select name="program" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #191A23;">
                <option value="">Semua Program</option>
                @foreach ($programs as $prog)
                    <option value="{{ $prog['slug'] }}" {{ $program === $prog['slug'] ? 'selected' : '' }}>
                        {{ $prog['nama'] }}
                    </option>
                @endforeach
            </select>
            <button class="btn o sm" type="submit">Filter</button>
            @if ($q || $program)
                <a class="btn o sm" href="{{ route('data_donasi.index') }}">Reset</a>
            @endif
        </form>

        @if (auth()->user()->isAdmin())
            <form method="POST" action="{{ route('data_donasi.destroyAll') }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data donasi yang tersaring? Tindakan ini tidak dapat dibatalkan.');">
                @csrf
                <input type="hidden" name="program" value="{{ $program }}">
                <button class="btn o sm del" type="submit" style="color: #dc2626; border-color: #dc2626;">
                    🗑 Hapus Data Tersaring
                </button>
            </form>
        @endif
    </div>

    <div class="card" style="margin-top: 1.5rem;">
        <h2 class="sub">Daftar Donatur</h2>
        <div class="scroll">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Kode Donasi</th>
                        <th>Donatur</th>
                        <th>Kontak / Identitas</th>
                        <th>Program & Paket</th>
                        <th class="r">Nominal</th>
                        <th>Tanggal Terima</th>
                        @if (auth()->user()->isAdmin())
                            <th class="r">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($donasiList as $d)
                        <tr>
                            <td class="nw font-bold">
                                <a href="{{ route('donasi.bayar', $d->kode) }}" style="text-decoration: underline; color: #191A23;">
                                    {{ $d->kode }}
                                </a>
                            </td>
                            <td>
                                <b>{{ $d->nama }}</b>
                                @if ($d->samarkan_nama)
                                    <span class="badge kuning" style="font-size: 0.7rem; padding: 2px 6px;">Hamba Allah</span>
                                @endif
                                @if ($d->catatan)
                                    <small style="display: block; color: #666; font-style: italic;">"{{ $d->catatan }}"</small>
                                @endif
                            </td>
                            <td>
                                <div>WA: {{ $d->kontak }}</div>
                                @if ($d->email)
                                    <small style="color: #666;">{{ $d->email }}</small><br>
                                @endif
                                @if ($d->nip)
                                    <small style="color: #666;">NIP: {{ $d->nip }}</small>
                                @endif
                            </td>
                            <td>
                                <b>{{ $d->program_nama }}</b><br>
                                <small>{{ $d->paket }}</small>
                            </td>
                            <td class="r nw" style="font-weight: 700; color: #15803d;">
                                {{ format_rupiah($d->jumlah) }}
                            </td>
                            <td class="nw">
                                {{ $d->diproses_at ? $d->diproses_at->format('d/m/Y H:i') : $d->created_at->format('d/m/Y H:i') }}
                            </td>
                            @if (auth()->user()->isAdmin())
                                <td class="r nw">
                                    <form method="POST" action="{{ route('data_donasi.destroy', $d->id) }}" style="display: inline;" onsubmit="return confirm('Hapus donasi {{ $d->kode }} secara permanen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lnk del" type="submit">Hapus</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" style="text-align: center; padding: 24px;">
                                Belum ada data donasi yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $donasiList->links() }}
        </div>
    </div>
</section>
@endsection
