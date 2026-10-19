@extends('layouts.app')

@section('title', 'Manajemen Petugas')

@section('content')
<section class="top">
    <div class="head">
        <h1 class="tag">Kelola Pengguna</h1>
        <p>Manajemen akun petugas pengelola UPZ BAZNAS Petrokimia Gresik (Administrator, Bendahara, dan Ketua).</p>
    </div>

    <div class="cols" style="margin-top: 1.5rem;">
        <div class="card" style="flex: 2;">
            <h2 class="sub">Daftar Pengguna Petugas</h2>
            <div class="scroll">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Nama & Username</th>
                            <th>Peran</th>
                            <th>Kontak / NIP</th>
                            <th>Terdaftar</th>
                            <th class="r">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr>
                                <td>
                                    <b>{{ $u->name }}</b><br>
                                    <small>{{ '@' . $u->username }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $u->role === 'admin' ? 'in' : ($u->role === 'bendahara' ? 'out' : 'abu') }}">
                                        {{ ucfirst($u->role) }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $u->no_hp ?? '-' }}</div>
                                    <small style="color: #666;">{{ $u->email ?? '' }} {{ $u->nip ? '| NIP ' . $u->nip : '' }}</small>
                                </td>
                                <td class="nw">
                                    {{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}
                                </td>
                                <td class="r nw">
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $u->id) }}" style="display: inline;" onsubmit="return confirm('Hapus akun petugas {{ $u->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="lnk del" type="submit">Hapus</button>
                                        </form>
                                    @else
                                        <small style="color: #888;">(Akun Anda)</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem;">
                {{ $users->links() }}
            </div>
        </div>

        <div class="card" style="flex: 1;">
            <h2 class="sub">Tambah Petugas Baru</h2>
            <form method="POST" action="{{ route('users.store') }}" autocomplete="off" style="margin-top: 12px;">
                @csrf
                <label for="u-name">Nama Lengkap</label>
                <input id="u-name" type="text" name="name" required placeholder="Nama lengkap petugas">

                <label for="u-username">Username</label>
                <input id="u-username" type="text" name="username" required placeholder="Username unik">

                <label for="u-role">Hak Akses / Peran</label>
                <select id="u-role" name="role" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #191A23; margin-bottom: 12px;">
                    <option value="bendahara">Bendahara (Kelola Transaksi & Donasi)</option>
                    <option value="admin">Administrator (Akses Penuh Sistem)</option>
                    <option value="ketua">Ketua (Hanya Melihat Laporan)</option>
                </select>

                <label for="u-hp">No. WhatsApp / HP</label>
                <input id="u-hp" type="text" name="no_hp" placeholder="08xxxxxxxxxx">

                <label for="u-nip">NIP / ID Karyawan (opsional)</label>
                <input id="u-nip" type="text" name="nip" placeholder="Nomor pegawai">

                <label for="u-email">Email (opsional)</label>
                <input id="u-email" type="email" name="email" placeholder="email@baznas.id">

                <label for="u-pw">Password Awal</label>
                <input id="u-pw" type="password" name="password" minlength="6" required placeholder="Minimal 6 karakter">

                <button class="btn" type="submit" style="margin-top: 14px; width: 100%;">Tambah Akun</button>
            </form>
        </div>
    </div>
</section>
@endsection
