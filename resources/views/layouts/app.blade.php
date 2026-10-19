<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Dashboard') | {{ config('upz.site.lengkap') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="dash">
    @include('partials.sprite')

    @php
        $peran = auth()->user()->role ?? 'staff';
        $menungguCount = \App\Models\Donasi::whereIn('status', ['menunggu', 'dibayar'])->count();
    @endphp

    <div class="stage">
        <div class="sheet">
            <div class="shell">
                <aside class="sidenav" id="sidenav">
                    <a class="logo" href="{{ route('dashboard') }}">
                        <svg width="26" height="26" fill="currentColor"><use href="#st"/></svg>{{ config('upz.site.nama', 'UPZ BAZNAS PG') }}
                    </a>
                    <span class="rolechip">Panel {{ ucfirst($peran) }}</span>

                    <div class="menu">
                        <a class="item {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard') }}">
                            <svg class="ico"><use href="#i-home"/></svg>Dashboard kas
                        </a>
                        <a class="item {{ request()->routeIs('donasi_masuk.*') ? 'on' : '' }}" href="{{ route('donasi_masuk.index') }}">
                            <svg class="ico"><use href="#i-inbox"/></svg>Donasi masuk
                            @if ($menungguCount > 0)
                                <span class="lencana" title="Menunggu verifikasi">{{ $menungguCount }}</span>
                            @endif
                        </a>
                        <a class="item {{ request()->routeIs('pembayaran.*') ? 'on' : '' }}" href="{{ route('pembayaran.index') }}">
                            <svg class="ico"><use href="#i-card"/></svg>Metode pembayaran
                        </a>
                        <a class="item {{ request()->routeIs('data_penyaluran.*') ? 'on' : '' }}" href="{{ route('data_penyaluran.index') }}">
                            <svg class="ico"><use href="#i-heart"/></svg>Data penyaluran
                        </a>
                        <a class="item {{ request()->routeIs('data_donasi.*') ? 'on' : '' }}" href="{{ route('data_donasi.index') }}">
                            <svg class="ico"><use href="#i-user"/></svg>Data donasi
                        </a>
                        <a class="item {{ request()->routeIs('program.*') ? 'on' : '' }}" href="{{ route('program.index') }}">
                            <svg class="ico"><use href="#i-tag"/></svg>Program dan kategori
                        </a>

                        @if (auth()->user()->isAdmin())
                            <a class="item {{ request()->routeIs('users.*') ? 'on' : '' }}" href="{{ route('users.index') }}">
                                <svg class="ico"><use href="#i-users"/></svg>Kelola pengguna
                            </a>
                            <a class="item {{ request()->routeIs('audit.*') ? 'on' : '' }}" href="{{ route('audit.index') }}">
                                <svg class="ico"><use href="#i-clock"/></svg>Riwayat aktivitas
                            </a>
                        @endif
                    </div>

                    <div class="me">
                        <b>{{ auth()->user()->name ?? 'Petugas' }}</b>
                        <small>{{ ucfirst($peran) }}</small>
                        <a class="item" href="{{ route('home') }}">
                            <svg class="ico"><use href="#i-site"/></svg>Beranda situs
                        </a>
                        <a class="item {{ request()->routeIs('profile.*') ? 'on' : '' }}" href="{{ route('profile.edit') }}">
                            <svg class="ico"><use href="#i-key"/></svg>Pengaturan akun
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="item" style="width: 100%; background: none; border: none; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font: inherit;">
                                <svg class="ico"><use href="#i-out"/></svg>Keluar
                            </button>
                        </form>
                    </div>
                </aside>

                <div class="scrim" data-sb></div>

                <main class="main">
                    <div class="mbar">
                        <button class="sbbtn" type="button" data-sb aria-label="Buka menu">
                            <svg class="ico"><use href="#i-menu"/></svg>
                        </button>
                        <span class="logo">
                            <svg width="22" height="22" fill="currentColor"><use href="#st"/></svg>{{ config('upz.site.nama', 'UPZ BAZNAS PG') }}
                        </span>
                    </div>

                    @include('partials.alerts')

                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
