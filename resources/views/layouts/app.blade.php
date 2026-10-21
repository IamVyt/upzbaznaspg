<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>@yield('title', 'Dashboard') | {{ config('upz.site.lengkap') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#B9FF66">
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
                        <div class="me-profile">
                            <span class="me-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}</span>
                            <div class="me-info">
                                <b>{{ auth()->user()->name ?? 'Petugas' }}</b>
                                <small>{{ ucfirst($peran) }}</small>
                            </div>
                        </div>
                        <a class="item" href="{{ route('home') }}" target="_blank" rel="noopener">
                            <svg class="ico"><use href="#i-site"/></svg>Beranda situs
                        </a>
                        <a class="item {{ request()->routeIs('profile.*') ? 'on' : '' }}" href="{{ route('profile.edit') }}">
                            <svg class="ico"><use href="#i-key"/></svg>Pengaturan akun
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="item logout-btn" style="width: 100%; border: none; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font: inherit;">
                                <svg class="ico"><use href="#i-out"/></svg><b>Keluar</b>
                            </button>
                        </form>
                    </div>
                </aside>

                <div class="scrim" data-sb></div>

                <main class="main">
                    <header class="staff-header">
                        <div class="staff-header-left">
                            <button class="sbbtn" type="button" data-sb aria-label="Buka menu navigasi">
                                <svg class="ico"><use href="#i-menu"/></svg>
                            </button>
                            <div class="staff-header-title">
                                <span class="staff-badge">Panel {{ ucfirst($peran) }}</span>
                                <span class="staff-site-name">{{ config('upz.site.nama', 'UPZ BAZNAS PG') }}</span>
                            </div>
                        </div>
                        <div class="staff-header-right">
                            <a href="{{ route('home') }}" class="btn-ghost" title="Buka Beranda Situs UPZ" target="_blank" rel="noopener">
                                <svg class="ico"><use href="#i-site"/></svg>
                                <span class="hide-mobile">Lihat Web</span>
                            </a>
                            <a href="{{ route('profile.edit') }}" class="staff-profile-pill" title="Profil Petugas ({{ ucfirst($peran) }})">
                                <span class="staff-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}</span>
                                <div class="staff-meta hide-mobile">
                                    <span class="staff-name">{{ auth()->user()->name ?? 'Petugas' }}</span>
                                    <span class="staff-role">{{ ucfirst($peran) }}</span>
                                </div>
                            </a>
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0; display: inline-flex;">
                                @csrf
                                <button type="submit" class="btn-logout" title="Keluar dari akun petugas">
                                    <svg class="ico"><use href="#i-out"/></svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </header>

                    @include('partials.alerts')

                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
