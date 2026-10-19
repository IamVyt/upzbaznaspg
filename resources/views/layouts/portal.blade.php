<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Layanan Donasi') | {{ config('upz.site.lengkap') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.sprite')

    <div class="stage">
        <div class="sheet">
            <div class="w">
                <nav>
                    <a class="logo" href="{{ route('home') }}">
                        <svg width="26" height="26" fill="currentColor"><use href="#st"/></svg>{{ config('upz.site.nama', 'UPZ BAZNAS PG') }}
                    </a>
                    <button class="burger" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="menu">
                        <span></span>
                    </button>
                    <ul id="menu">
                        <li><a href="{{ route('home') }}">Beranda</a></li>
                        <li><a href="{{ route('donasi.create') }}">Salurkan</a></li>
                        @auth
                            @if (auth()->user()->isStaff())
                                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            @endif
                            <li><a href="{{ route('donasi.saya') }}">Donasi saya</a></li>
                            @include('partials.menu_akun')
                        @else
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a class="btn o" href="{{ route('register') }}">Daftar</a></li>
                        @endauth
                    </ul>
                </nav>

                @include('partials.alerts')

                @yield('content')
            </div>

            <div class="w">
                <div class="mitra">
                    <small>Didukung oleh</small>
                    <div class="logos">
                        <span>BAZNAS</span>
                        <span>Petrokimia Gresik</span>
                        <span>Pupuk Indonesia</span>
                    </div>
                    <p>© {{ date('Y') }} {{ config('upz.site.lengkap') }}. Amanah, transparan, dan akuntabel.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
