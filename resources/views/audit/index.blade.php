@extends('layouts.app')

@section('title', 'Riwayat Aktivitas (Audit Log)')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Riwayat Aktivitas</h1>
        <p>Catatan audit terperinci dari seluruh aktivitas krusial, mutasi kas, login, dan aksi pengguna.</p>
    </div>

    <div class="bar" style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;">
        <form class="acts" method="GET" action="{{ route('audit.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <input class="cari" type="text" name="q" value="{{ $q }}" placeholder="Cari username, tabel, IP..." style="min-width: 200px;">
            <select name="aksi" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #191A23;">
                <option value="">Semua Aksi</option>
                @foreach ($daftarAksi as $ak)
                    <option value="{{ $ak }}" {{ $aksi === $ak ? 'selected' : '' }}>
                        {{ audit_label_aksi($ak) }}
                    </option>
                @endforeach
            </select>
            <button class="btn o sm" type="submit">Filter</button>
            @if ($q || $aksi)
                <a class="btn o sm" href="{{ route('audit.index') }}">Reset</a>
            @endif
        </form>

        @if (auth()->user()->isAdmin())
            <form method="POST" action="{{ route('audit.clear') }}" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan riwayat aktivitas? Tindakan ini akan dicatat ke audit log.');">
                @csrf
                <select name="rentang" style="padding: 6px 10px; border-radius: 8px; border: 1px solid #191A23; font-size: 0.85rem;">
                    <option value="1minggu">> 1 Minggu Lalu</option>
                    <option value="1bulan">> 1 Bulan Lalu</option>
                    <option value="semua">Semua Log</option>
                </select>
                <button class="btn o sm del" type="submit" style="color: #dc2626; border-color: #dc2626;">
                    Bersihkan Riwayat
                </button>
            </form>
        @endif
    </div>

    <div class="card" style="margin-top: 1.5rem;">
        <h2 class="sub">Log Aktivitas</h2>
        <div class="scroll">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Penjelasan Aktivitas</th>
                        <th>Alamat IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="nw">
                                {{ $log->waktu ? $log->waktu->format('d/m/Y') : $log->created_at->format('d/m/Y') }}<br>
                                <small>{{ $log->waktu ? $log->waktu->format('H:i:s') : $log->created_at->format('H:i:s') }} WIB</small>
                            </td>
                            <td>
                                <b>{{ $log->username ?? 'Tamu' }}</b>
                                @if ($log->user)
                                    <small style="display: block; color: #666;">({{ ucfirst($log->user->role) }})</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge in" style="background: #191A23; color: #B9FF66;">
                                    {{ audit_label_aksi($log->aksi) }}
                                </span>
                            </td>
                            <td>
                                <div>{{ audit_uraian_ringkas($log) }}</div>
                                @if ($log->tabel)
                                    <small style="color: #666;">Objek: {{ $log->tabel }} #{{ $log->record_id }}</small>
                                @endif
                            </td>
                            <td class="nw">
                                <code>{{ $log->ip }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 24px;">
                                Belum ada catatan riwayat aktivitas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $logs->links() }}
        </div>
    </div>
</section>
@endsection
