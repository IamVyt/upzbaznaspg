@extends('layouts.app')

@section('title', 'Dashboard Keuangan')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Dashboard Keuangan</h1>
        <p>Ringkasan pembukuan arus kas dan pengelolaan donasi UPZ BAZNAS Petrokimia Gresik.</p>
    </div>

    <div class="kpi">
        <div class="c g">
            <span class="tg">Total Saldo Kas</span>
            <b>{{ format_rupiah($saldoKas) }}</b>
            <small>Dana bersih yang dikelola saat ini</small>
        </div>
        <div class="c l">
            <span class="tg">Total Pemasukan</span>
            <b>{{ format_rupiah($totalMasuk) }}</b>
            <small>Bulan ini: {{ format_rupiah($masukBulanIni) }}</small>
        </div>
        <div class="c d">
            <span class="tg">Total Penyaluran</span>
            <b>{{ format_rupiah($totalKeluar) }}</b>
            <small>Bulan ini: {{ format_rupiah($keluarBulanIni) }}</small>
        </div>
        @if ($donasiMenungguCount > 0)
            <div class="c l" style="border-left: 6px solid #eab308;">
                <span class="tg">Donasi Online Masuk</span>
                <b style="color: #ca8a04;">{{ $donasiMenungguCount }} Menunggu</b>
                <a href="{{ route('donasi_masuk.index') }}" style="font-size: 0.85rem; text-decoration: underline; color: #191A23;">Verifikasi sekarang ↗</a>
            </div>
        @endif
    </div>

    @if (auth()->user()->canManageFinance())
        <div class="acts" style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 10px;">
            <button class="btn g sm" type="button" onclick="bukaModalKas('masuk')">+ Catat Pemasukan</button>
            <button class="btn o sm" type="button" onclick="bukaModalKas('keluar')">- Catat Penyaluran (Keluar)</button>
            <a class="btn o sm" href="{{ route('transaksi.export') }}" target="_blank">📥 Unduh Rekap (CSV/Excel)</a>
        </div>
    @endif
</section>

<section class="cols" style="margin-top: 2rem;">
    <div class="card" style="flex: 2;">
        <h2 class="sub">10 Transaksi Kas Terbaru</h2>
        <div class="scroll">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama & Keterangan</th>
                        <th>Jenis</th>
                        <th class="r">Jumlah</th>
                        <th class="r">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksiTerbaru as $trx)
                        @php
                            $in = $trx->tipe === 'masuk';
                            $void = $trx->isBatal();
                        @endphp
                        <tr class="{{ $void ? 'void' : '' }}" {!! $void ? 'title="Alasan void: ' . e($trx->alasan_batal) . '"' : '' !!}>
                            <td class="nw">{{ $trx->tanggal->format('d/m/Y') }}</td>
                            <td>
                                <b>{{ $trx->nama_pihak ?? 'Hamba Allah' }}</b>
                                <span class="meta">
                                    @if ($trx->kategori)
                                        <i class="cat">{{ $trx->kategori->nama }}</i>
                                    @endif
                                    @if ($trx->keterangan)
                                        <small class="ket">{{ $trx->keterangan }}</small>
                                    @endif
                                </span>
                            </td>
                            <td>
                                @if ($void)
                                    <span class="badge void">Void</span>
                                @else
                                    <span class="badge {{ $in ? 'in' : 'out' }}">{{ $in ? 'Pemasukan' : 'Penyaluran' }}</span>
                                @endif
                            </td>
                            <td class="r nw">
                                <b>{{ $in ? '+' : '-' }} {{ format_rupiah($trx->jumlah) }}</b>
                            </td>
                            <td class="r nw">
                                @if ($void)
                                    <small style="color: #999;">Dibatalkan</small>
                                @elseif (auth()->user()->canManageFinance())
                                    <button class="lnk del" type="button" onclick="bukaModalVoid({{ $trx->id }}, '{{ addslashes($trx->nama_pihak) }}')">
                                        Void
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 24px;">
                                Belum ada riwayat transaksi kas tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="side" style="flex: 1;">
        <div class="card">
            <h2 class="sub">Arus Kas <small>6 Bulan Terakhir</small></h2>
            <div class="chart" style="position: relative; height: 260px;">
                <canvas id="grafikArusKas"></canvas>
            </div>
        </div>

        <div class="card c d info" style="margin-top: 1rem;">
            <span class="tg">Unit Pengumpul Zakat</span>
            <p>Pengelolaan dana zakat, infak, dan sedekah PT Petrokimia Gresik secara aman, transparan, dan akuntabel.</p>
            <p>Petugas aktif: <b>{{ auth()->user()->name }}</b> ({{ ucfirst(auth()->user()->role) }})</p>
        </div>
    </div>
</section>

{{-- Modal Catat Transaksi Kas --}}
@if (auth()->user()->canManageFinance())
<dialog class="dlg" id="dlg-kas" aria-labelledby="f-judul">
    <form class="dbox" method="POST" action="{{ route('transaksi.store') }}">
        <button class="x" type="button" onclick="document.getElementById('dlg-kas').close()" aria-label="Tutup">×</button>
        <h3 id="f-judul"><span id="txt-judul">Catat Transaksi Kas</span></h3>
        @csrf
        <input type="hidden" name="tipe" id="f-tipe" value="masuk">

        <label for="f-nama">Nama Pihak (Muzakki / Mustahik)</label>
        <input id="f-nama" type="text" name="nama_pihak" maxlength="150" placeholder="Nama pihak terkait" required>

        <label for="f-kategori">Kategori Dana</label>
        <select id="f-kategori" name="kategori_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #191A23; margin-bottom: 12px;">
            <optgroup label="Pemasukan" id="opt-masuk">
                @foreach ($kategoriMasuk as $km)
                    <option value="{{ $km->id }}">{{ $km->nama }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Pengeluaran / Penyaluran" id="opt-keluar">
                @foreach ($kategoriKeluar as $kk)
                    <option value="{{ $kk->id }}">{{ $kk->nama }}</option>
                @endforeach
            </optgroup>
        </select>

        <label for="f-jumlah">Nominal (Rp)</label>
        <input id="f-jumlah" type="number" name="jumlah" min="100" step="1" required placeholder="Contoh: 500000">

        <label for="f-tgl">Tanggal Transaksi</label>
        <input id="f-tgl" type="date" name="tanggal" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>

        <label for="f-ket">Keterangan Tambahan</label>
        <textarea id="f-ket" name="keterangan" rows="2" placeholder="Catatan transaksi"></textarea>

        <div class="acts end" style="margin-top: 14px;">
            <button class="btn o" type="button" onclick="document.getElementById('dlg-kas').close()">Batal</button>
            <button class="btn" type="submit">Simpan Transaksi</button>
        </div>
    </form>
</dialog>

{{-- Modal Void Transaksi --}}
<dialog class="dlg" id="dlg-void">
    <form class="dbox" method="POST" id="form-void" action="">
        <button class="x" type="button" onclick="document.getElementById('dlg-void').close()" aria-label="Tutup">×</button>
        <h3><span>Batalkan Transaksi (Void)?</span></h3>
        @csrf
        <p>Transaksi atas nama <b id="void-nama">-</b> akan dibatalkan (void). Data tetap tersimpan namun saldo kas dikembalikan.</p>

        <label for="void-alasan">Alasan Pembatalan</label>
        <textarea id="void-alasan" name="alasan_batal" rows="2" maxlength="255" required placeholder="Contoh: salah input nominal / duplikat"></textarea>

        <div class="acts end" style="margin-top: 14px;">
            <button class="btn o" type="button" onclick="document.getElementById('dlg-void').close()">Batal</button>
            <button class="btn del" type="submit" style="background: #dc2626; color: #fff; border: 1px solid #191A23;">Batalkan (Void)</button>
        </div>
    </form>
</dialog>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function bukaModalKas(tipe) {
        document.getElementById('f-tipe').value = tipe;
        document.getElementById('txt-judul').innerText = tipe === 'masuk' ? 'Catat Kas Pemasukan' : 'Catat Kas Penyaluran (Keluar)';
        const optMasuk = document.getElementById('opt-masuk');
        const optKeluar = document.getElementById('opt-keluar');

        if (tipe === 'masuk') {
            optMasuk.disabled = false;
            optKeluar.disabled = true;
            document.getElementById('f-kategori').value = optMasuk.children[0]?.value || '';
        } else {
            optMasuk.disabled = true;
            optKeluar.disabled = false;
            document.getElementById('f-kategori').value = optKeluar.children[0]?.value || '';
        }

        document.getElementById('dlg-kas').showModal();
    }

    function bukaModalVoid(id, nama) {
        document.getElementById('void-nama').innerText = nama;
        document.getElementById('form-void').action = '/admin/transaksi/' + id + '/void';
        document.getElementById('dlg-void').showModal();
    }

    // Chart.js Arus Kas
    const ctx = document.getElementById('grafikArusKas');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: {!! json_encode($chartMasuk) !!},
                        backgroundColor: '#B9FF66',
                        borderColor: '#191A23',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Penyaluran',
                        data: {!! json_encode($chartKeluar) !!},
                        backgroundColor: '#191A23',
                        borderColor: '#191A23',
                        borderWidth: 1.5,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                if (val >= 1000000) return (val / 1000000) + ' Jt';
                                if (val >= 1000) return (val / 1000) + ' Rb';
                                return val;
                            }
                        }
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
