@auth
    @php
        $namaLengkap = auth()->user()->name ?? 'Akun';
        $namaDepan = strtok($namaLengkap, ' ') ?: 'Akun';
        $inisial = mb_strtoupper(mb_substr($namaDepan, 0, 1));
    @endphp
    <li class="akun">
        <button class="btn o akun-btn" type="button" aria-expanded="false" aria-controls="menu-akun">
            <span class="av" aria-hidden="true">{{ $inisial }}</span>{{ $namaDepan }}
        </button>
        <div class="akun-menu" id="menu-akun" hidden>
            <div class="akun-info">
                <b>{{ $namaLengkap }}</b>
                <small>{{ '@' . auth()->user()->username }}</small>
            </div>
            <a href="{{ route('profile.edit') }}">
                <svg><use href="#i-user"/></svg>Pengaturan akun
            </a>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="keluar" style="width: 100%; background: none; border: none; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 10px 14px; font: inherit;">
                    <svg><use href="#i-out"/></svg>Keluar
                </button>
            </form>
        </div>
    </li>
@endauth
