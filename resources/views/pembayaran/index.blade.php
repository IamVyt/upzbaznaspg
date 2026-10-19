@extends('layouts.app')

@section('title', 'Pengaturan Metode Pembayaran')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Metode Pembayaran</h1>
        <p>Atur rekening bank resmi dan nomor e-wallet yang aktif dan dapat dipilih donatur saat berdonasi online.</p>
    </div>

    @if (auth()->user()->isAdmin())
        <form method="POST" action="{{ route('pembayaran.update') }}" style="margin-top: 1.5rem;">
            @csrf
            @method('PUT')
    @endif

    <div class="cols" style="margin-top: 1.5rem;">
        <div class="card" style="flex: 1;">
            <h2 class="sub">Rekening Transfer Bank</h2>
            <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 12px;">
                @foreach ($bankList as $bank)
                    <div style="padding: 14px; border: 1px solid #191A23; border-radius: 10px; background: #fafafa;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <b style="font-size: 1.1rem;">{{ $bank->nama }}</b>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.9rem; cursor: pointer;">
                                <input type="checkbox" name="rekening[{{ $bank->id }}][aktif]" value="1" {{ $bank->aktif ? 'checked' : '' }} {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                                <span>Aktifkan</span>
                            </label>
                        </div>
                        <div class="kolom2">
                            <div>
                                <label style="font-size: 0.85rem; margin-bottom: 4px;">Nomor Rekening</label>
                                <input type="text" name="rekening[{{ $bank->id }}][nomor]" value="{{ $bank->nomor }}" placeholder="Nomor rekening" {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                            </div>
                            <div>
                                <label style="font-size: 0.85rem; margin-bottom: 4px;">Atas Nama</label>
                                <input type="text" name="rekening[{{ $bank->id }}][atas_nama]" value="{{ $bank->atas_nama }}" placeholder="Nama pemilik rekening" {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card" style="flex: 1;">
            <h2 class="sub">Kanal E-Wallet</h2>
            <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 12px;">
                @foreach ($ewalletList as $ew)
                    <div style="padding: 14px; border: 1px solid #191A23; border-radius: 10px; background: #fafafa;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <b style="font-size: 1.1rem;">{{ $ew->nama }}</b>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.9rem; cursor: pointer;">
                                <input type="checkbox" name="rekening[{{ $ew->id }}][aktif]" value="1" {{ $ew->aktif ? 'checked' : '' }} {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                                <span>Aktifkan</span>
                            </label>
                        </div>
                        <div class="kolom2">
                            <div>
                                <label style="font-size: 0.85rem; margin-bottom: 4px;">Nomor Telepon Akun</label>
                                <input type="text" name="rekening[{{ $ew->id }}][nomor]" value="{{ $ew->nomor }}" placeholder="08xxxxxxxxxx" {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                            </div>
                            <div>
                                <label style="font-size: 0.85rem; margin-bottom: 4px;">Atas Nama</label>
                                <input type="text" name="rekening[{{ $ew->id }}][atas_nama]" value="{{ $ew->atas_nama }}" placeholder="Nama akun" {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div style="margin-top: 1.5rem; text-align: right;">
            <button class="btn" type="submit" style="padding: 12px 28px;">Simpan Perubahan Pembayaran</button>
        </div>
        </form>
    @endif
</section>
@endsection
