@extends('layouts.app')

@section('title', 'Verifikasi Donasi Masuk')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Donasi Masuk</h1>
        <p>Verifikasi bukti pembayaran donasi online dari para donatur dan catat ke kas resmi.</p>
    </div>

    <div class="bar" style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
        <div class="pills">
            <a class="pill {{ $status === 'semua' ? 'on' : '' }}" href="{{ route('donasi_masuk.index', ['status' => 'semua']) }}">
                Semua ({{ $counts['semua'] }})
            </a>
            <a class="pill {{ $status === 'dibayar' ? 'on' : '' }}" href="{{ route('donasi_masuk.index', ['status' => 'dibayar']) }}">
                Perlu Dicek ({{ $counts['dibayar'] }})
            </a>
            <a class="pill {{ $status === 'menunggu' ? 'on' : '' }}" href="{{ route('donasi_masuk.index', ['status' => 'menunggu']) }}">
                Menunggu Bayar ({{ $counts['menunggu'] }})
            </a>
            <a class="pill {{ $status === 'diterima' ? 'on' : '' }}" href="{{ route('donasi_masuk.index', ['status' => 'diterima']) }}">
                Tersalurkan ({{ $counts['diterima'] }})
            </a>
            <a class="pill {{ $status === 'ditolak' ? 'on' : '' }}" href="{{ route('donasi_masuk.index', ['status' => 'ditolak']) }}">
                Ditolak ({{ $counts['ditolak'] }})
            </a>
        </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
        <h2 class="sub">Daftar Donasi</h2>
        <div class="scroll">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Donatur</th>
                        <th>Program & Paket</th>
                        <th>Metode</th>
                        <th class="r">Nominal</th>
                        <th>Bukti / Status</th>
                        @if (auth()->user()->canManageFinance())
                            <th class="r">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($donasiList as $d)
                        <tr>
                            <td class="nw">
                                {{ $d->created_at->format('d/m/Y') }}<br>
                                <small>{{ $d->created_at->format('H:i') }} WIB</small>
                            </td>
                            <td>
                                <b>{{ $d->nama }}</b>
                                @if ($d->samarkan_nama)
                                    <i class="cat" style="font-size: 0.75rem;">(Hamba Allah)</i>
                                @endif
                                <small style="display: block; color: #555;">{{ $d->kontak }} {{ $d->nip ? '| NIP ' . $d->nip : '' }}</small>
                                @if ($d->catatan)
                                    <small style="display: block; font-style: italic;">"{{ $d->catatan }}"</small>
                                @endif
                            </td>
                            <td>
                                <b>{{ $d->program_nama }}</b><br>
                                <small>{{ $d->paket }} ({{ $d->kode }})</small>
                            </td>
                            <td class="nw">
                                <b>{{ strtoupper($d->metode) }}</b>
                                @if ($d->channel_nama)
                                    <small style="display: block;">{{ $d->channel_nama }}</small>
                                @endif
                            </td>
                            <td class="r nw">
                                <b>{{ format_rupiah($d->jumlah) }}</b>
                            </td>
                            <td>
                                @if ($d->status === 'menunggu')
                                    <span class="badge kuning">Menunggu</span>
                                @elseif ($d->status === 'dibayar')
                                    <span class="badge biru">Sudah Dibayar</span>
                                @elseif ($d->status === 'diterima')
                                    <span class="badge hijau">Tersalurkan</span>
                                @elseif ($d->status === 'ditolak')
                                    <span class="badge merah">Ditolak</span>
                                @else
                                    <span class="badge abu">Batal</span>
                                @endif

                                @if ($d->bukti)
                                    <div style="margin-top: 4px;">
                                        <a href="{{ route('donasi.bukti', $d->kode) }}" target="_blank" class="ul" style="font-size: 0.85rem; font-weight: 700;">
                                            Lihat Bukti ↗
                                        </a>
                                    </div>
                                @endif
                            </td>
                            @if (auth()->user()->canManageFinance())
                                <td class="r nw">
                                    @if (in_array($d->status, ['menunggu', 'dibayar'], true))
                                        <button class="lnk" type="button" onclick="bukaModalKonfirmasi({{ $d->id }}, '{{ $d->kode }}', '{{ addslashes($d->nama) }}', '{{ format_rupiah($d->jumlah) }}')">
                                            Konfirmasi
                                        </button>
                                        <button class="lnk del" type="button" onclick="bukaModalTolak({{ $d->id }}, '{{ $d->kode }}', '{{ addslashes($d->nama) }}')">
                                            Tolak
                                        </button>
                                    @else
                                        <small style="color: #888;">Selesai</small>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canManageFinance() ? 7 : 6 }}" style="text-align: center; padding: 24px;">
                                Tidak ada donasi yang sesuai dengan status ini.
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

{{-- Modal Konfirmasi Donasi --}}
<dialog class="dlg" id="dlg-konfirmasi">
    <form class="dbox" method="POST" id="form-konfirmasi" action="">
        <button class="x" type="button" onclick="document.getElementById('dlg-konfirmasi').close()" aria-label="Tutup">×</button>
        <h3><span>Konfirmasi Donasi Masuk</span></h3>
        @csrf
        <p>Donasi <b id="konf-info">-</b> akan disetujui dan otomatis dicatat sebagai pemasukan kas, sehingga saldo bertambah.</p>
        <div class="acts end" style="margin-top: 14px;">
            <button class="btn o" type="button" onclick="document.getElementById('dlg-konfirmasi').close()">Batal</button>
            <button class="btn g" type="submit">Ya, Konfirmasi Masuk Kas</button>
        </div>
    </form>
</dialog>

{{-- Modal Tolak Donasi --}}
<dialog class="dlg" id="dlg-tolak">
    <form class="dbox" method="POST" id="form-tolak" action="">
        <button class="x" type="button" onclick="document.getElementById('dlg-tolak').close()" aria-label="Tutup">×</button>
        <h3><span>Tolak Donasi?</span></h3>
        @csrf
        <p>Donasi <b id="tolak-info">-</b> akan ditandai ditolak dan tidak masuk ke pembukuan kas.</p>
        <label for="tolak-alasan">Alasan Penolakan</label>
        <textarea id="tolak-alasan" name="alasan_tolak" rows="2" maxlength="255" required placeholder="Contoh: bukti transfer tidak valid atau dana tidak masuk ke mutasi bank"></textarea>
        <div class="acts end" style="margin-top: 14px;">
            <button class="btn o" type="button" onclick="document.getElementById('dlg-tolak').close()">Batal</button>
            <button class="btn del" type="submit" style="background: #dc2626; color: #fff; border: 1px solid #191A23;">Tolak Donasi</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
    function bukaModalKonfirmasi(id, kode, nama, jumlah) {
        document.getElementById('konf-info').innerText = kode + ' (' + nama + ' - ' + jumlah + ')';
        document.getElementById('form-konfirmasi').action = '/admin/donasi-masuk/' + id + '/konfirmasi';
        document.getElementById('dlg-konfirmasi').showModal();
    }

    function bukaModalTolak(id, kode, nama) {
        document.getElementById('tolak-info').innerText = kode + ' (' + nama + ')';
        document.getElementById('form-tolak').action = '/admin/donasi-masuk/' + id + '/tolak';
        document.getElementById('dlg-tolak').showModal();
    }
</script>
@endpush
@endsection
