@extends('layouts.app')

@section('title', 'Katalog Program & Kategori Kas')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Program & Kategori Kas</h1>
        <p>Katalog program penyaluran donasi publik dan pemetaan kategori akun pada pembukuan kas UPZ.</p>
    </div>

    <div class="cols" style="margin-top: 1.5rem;">
        <div class="card" style="flex: 2;">
            <h2 class="sub">Katalog Program Penyaluran</h2>
            <div class="scroll">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Kategori Kas</th>
                            <th>Pilihan Paket</th>
                            <th class="r">Total Tersalurkan</th>
                            <th class="r">Donasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programStats as $ps)
                            <tr>
                                <td>
                                    <b>{{ $ps['program']['nama'] }}</b>
                                    <small style="display: block; color: #666;">{{ $ps['program']['ringkas'] }}</small>
                                </td>
                                <td>
                                    <i class="cat">{{ $ps['kategori'] }}</i>
                                </td>
                                <td>
                                    <ul style="margin: 0; padding-left: 16px; font-size: 0.85rem;">
                                        @foreach ($ps['paket'] as $pk)
                                            <li>{{ $pk['nama'] }} ({{ $pk['nominal'] > 0 ? format_rupiah($pk['nominal']) : 'Bebas' }})</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="r nw" style="font-weight: 700; color: #15803d;">
                                    {{ format_rupiah($ps['total_dana']) }}
                                </td>
                                <td class="r nw">
                                    {{ $ps['count_donatur'] }} kali
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="flex: 1;">
            <h2 class="sub">Daftar Kategori Kas</h2>
            <div class="scroll">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Nama Kategori</th>
                            <th>Kode</th>
                            <th>Arus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kategoriDana as $kd)
                            <tr>
                                <td><b>{{ $kd->nama }}</b></td>
                                <td><code>{{ $kd->kode }}</code></td>
                                <td>
                                    @if ($kd->tipe === 'masuk')
                                        <span class="badge in">Masuk</span>
                                    @else
                                        <span class="badge out">Keluar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
