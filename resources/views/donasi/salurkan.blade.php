@extends('layouts.portal')

@section('title', 'Salurkan Donasi | ' . $selectedProgram['nama'])

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Salurkan</h1>
        <p>Tunaikan zakat, infak, dan sedekah Anda melalui UPZ BAZNAS Petrokimia Gresik. Pilih program dan paket, isi data, lalu lakukan pembayaran.</p>
    </div>

    <ol class="steps" aria-label="Langkah penyaluran">
        <li class="done"><b>1</b>Pilih paket</li>
        <li class="on"><b>2</b>Isi data</li>
        <li><b>3</b>Bayar</li>
    </ol>

    <div class="pills pilih" aria-label="Pilih program">
        @foreach ($programs as $prog)
            <a class="pill {{ $prog['slug'] === $programPilihan ? 'on' : '' }}" href="{{ route('donasi.create', ['program' => $prog['slug']]) }}">
                {{ $prog['nama'] }}
            </a>
        @endforeach
    </div>
</section>

<section class="bayar" style="margin-top: 20px;">
    <div class="c g ringkas">
        <small>Program pilihan Anda</small>
        <h2>{{ $selectedProgram['nama'] }}</h2>
        <p class="lead" style="margin-top: 8px;">{{ $selectedProgram['ringkas'] }}</p>
        <div style="margin-top: 16px;">
            <span class="tg">Pilihan Paket:</span>
            <ul style="margin: 8px 0 0 16px; font-size: 0.95rem; line-height: 1.5;">
                @foreach ($paketList as $pk)
                    <li><b>{{ $pk['nama'] }}</b>: {{ $pk['nominal'] > 0 ? format_rupiah($pk['nominal']) : 'Nominal bebas' }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <form class="c l form" method="POST" action="{{ route('donasi.store') }}" autocomplete="off">
        @csrf
        <input type="hidden" name="program" value="{{ $selectedProgram['slug'] }}">

        <h2><span class="tg">Data Donasi</span></h2>
        <p>Lengkapi rincian donasi di bawah ini:</p>

        <label for="paket">Pilihan Paket Penyaluran</label>
        <select id="paket" name="paket" required style="width: 100%; padding: 12px; border-radius: 12px; border: 2px solid var(--ink, #191A23); font-size: 1rem; margin-bottom: 14px;">
            @foreach ($paketList as $pk)
                <option value="{{ $pk['nama'] }}" data-nominal="{{ $pk['nominal'] }}" {{ old('paket') === $pk['nama'] ? 'selected' : '' }}>
                    {{ $pk['nama'] }} ({{ $pk['nominal'] > 0 ? format_rupiah($pk['nominal']) : 'Nominal bebas' }})
                </option>
            @endforeach
        </select>

        <div class="dua">
            <div class="kol">
                <label for="nama">Nama Lengkap Donatur</label>
                <input id="nama" type="text" name="nama" value="{{ old('nama', $user?->name) }}" maxlength="150" required placeholder="Nama lengkap">

                <div class="kolom2">
                    <div>
                        <label for="kontak">No. WhatsApp/HP</label>
                        <input id="kontak" type="tel" name="kontak" value="{{ old('kontak', $user?->no_hp) }}" maxlength="30" required placeholder="08xxxxxxxxxx">
                    </div>
                    <div>
                        <label for="nip">NIP Pegawai</label>
                        <input id="nip" type="text" name="nip" value="{{ old('nip', $user?->nip) }}" maxlength="30" placeholder="Opsional">
                    </div>
                </div>

                <label for="email">Alamat Email (opsional)</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" maxlength="100" placeholder="nama@email.com">

                <label for="catatan">Catatan / Doa (opsional)</label>
                <textarea id="catatan" name="catatan" rows="3" maxlength="500" placeholder="Tuliskan niat, hajat, atau doa Anda">{{ old('catatan') }}</textarea>
            </div>

            <div class="kol">
                <label for="jumlah">Nominal Donasi (Rp)</label>
                <input id="jumlah" type="number" name="jumlah" min="10000" max="1000000000" step="1" inputmode="numeric" value="{{ old('jumlah', $paketList[0]['nominal'] ?? 50000) }}" required placeholder="Minimal Rp 10.000">

                <div class="chips" aria-label="Pilihan nominal cepat" style="margin-bottom: 16px;">
                    @foreach ([25000, 50000, 100000, 250000, 500000, 1000000] as $n)
                        <button class="pill" type="button" onclick="document.getElementById('jumlah').value = '{{ $n }}'">{{ format_rupiah($n) }}</button>
                    @endforeach
                </div>

                <label>Metode Pembayaran</label>
                <div class="metode-box" style="margin-bottom: 16px;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label class="bank" style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 2px solid var(--ink, #191A23); border-radius: 10px; cursor: pointer;">
                            <input type="radio" name="metode" value="transfer" checked onchange="toggleMetode('transfer')">
                            <span><b>Transfer Bank</b> (BSI, BCA, BNI, Mandiri, BRI)</span>
                        </label>

                        <div id="pilihan-bank" style="padding: 10px 14px; background: #f4f6f0; border-radius: 8px; margin-left: 20px;">
                            <label for="channel-bank" style="font-size: 0.9rem;">Pilih Bank Tujuan:</label>
                            <select id="channel-bank" name="channel" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                                @foreach ($rekeningBank as $rb)
                                    <option value="{{ $rb->kode }}">{{ $rb->nama }} - {{ $rb->nomor }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label class="bank" style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 2px solid var(--ink, #191A23); border-radius: 10px; cursor: pointer;">
                            <input type="radio" name="metode" value="ewallet" onchange="toggleMetode('ewallet')">
                            <span><b>E-Wallet</b> (GoPay, OVO, DANA, ShopeePay)</span>
                        </label>

                        <div id="pilihan-ewallet" style="display: none; padding: 10px 14px; background: #f4f6f0; border-radius: 8px; margin-left: 20px;">
                            <label for="channel-ewallet" style="font-size: 0.9rem;">Pilih E-Wallet:</label>
                            <select id="channel-ewallet" name="channel_ewallet" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                                @foreach ($rekeningEwallet as $re)
                                    <option value="{{ $re->kode }}">{{ $re->nama }} - {{ $re->nomor }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label class="bank" style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 2px solid var(--ink, #191A23); border-radius: 10px; cursor: pointer;">
                            <input type="radio" name="metode" value="qris" onchange="toggleMetode('qris')">
                            <span><b>QRIS</b> (Scan QR Code Instan)</span>
                        </label>

                        <label class="bank" style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 2px solid var(--ink, #191A23); border-radius: 10px; cursor: pointer;">
                            <input type="radio" name="metode" value="tunai" onchange="toggleMetode('tunai')">
                            <span><b>Tunai di Kantor UPZ</b> (Serahkan langsung)</span>
                        </label>
                    </div>
                </div>

                <div style="background: #fafafa; border: 1px solid #ddd; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; margin-bottom: 8px;">
                        <input type="checkbox" name="samarkan_nama" value="1" {{ old('samarkan_nama') ? 'checked' : '' }}>
                        <span>Samarkan nama saya sebagai <b>Hamba Allah</b></span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; margin-bottom: 8px;">
                        <input type="checkbox" name="tampil_beranda" value="1" {{ old('tampil_beranda') ? 'checked' : '' }}>
                        <span>Tampilkan di daftar donatur terbaru di beranda</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                        <input type="checkbox" name="tampil_doa" value="1" {{ old('tampil_doa') ? 'checked' : '' }}>
                        <span>Sertakan catatan/doa saya di beranda</span>
                    </label>
                </div>
            </div>
        </div>

        <button class="btn" type="submit" style="margin-top: 14px; width: 100%; max-width: 320px;">Lanjut Pembayaran →</button>
        <small class="bantu" style="display: block; margin-top: 8px;">Nomor referensi pembayaran unik dan petunjuk transfer akan diberikan pada langkah berikutnya.</small>
    </form>
</section>

@push('scripts')
<script>
    document.getElementById('paket')?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const nominal = parseInt(selected.getAttribute('data-nominal') || '0', 10);
        if (nominal > 0) {
            document.getElementById('jumlah').value = nominal;
        }
    });

    function toggleMetode(metode) {
        const bankBox = document.getElementById('pilihan-bank');
        const ewalletBox = document.getElementById('pilihan-ewallet');
        const channelBank = document.getElementById('channel-bank');
        const channelEwallet = document.getElementById('channel-ewallet');

        if (metode === 'transfer') {
            bankBox.style.display = 'block';
            ewalletBox.style.display = 'none';
            channelBank.name = 'channel';
            channelEwallet.name = 'channel_disabled';
        } else if (metode === 'ewallet') {
            bankBox.style.display = 'none';
            ewalletBox.style.display = 'block';
            channelBank.name = 'channel_disabled';
            channelEwallet.name = 'channel';
        } else {
            bankBox.style.display = 'none';
            ewalletBox.style.display = 'none';
            channelBank.name = 'channel_disabled';
            channelEwallet.name = 'channel_disabled';
        }
    }
</script>
@endpush
@endsection
