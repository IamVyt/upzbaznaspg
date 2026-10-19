<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'UPZ BAZNAS Petrokimia Gresik | Layanan Zakat, Infak, dan Sedekah')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
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
                        <li><a href="#tentang">Tentang</a></li>
                        <li><a href="#program">Program</a></li>
                        <li><a href="#dampak">Dampak</a></li>
                        <li><a href="#alur">Alur</a></li>
                        <li><a href="#tim">Tim</a></li>
                        <li><a href="{{ route('donasi.create') }}">Salurkan</a></li>
                        @auth
                            @if (auth()->user()->isStaff())
                                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            @endif
                            <li><a href="{{ route('donasi.saya') }}">Donasi saya</a></li>
                            @include('partials.menu_akun')
                        @else
                            <li><a class="btn o" href="{{ route('login') }}">Login</a></li>
                        @endauth
                    </ul>
                </nav>

                @include('partials.alerts')

                @yield('content')
            </div>

            <footer>
                <div class="w">
                    <div class="top">
                        <a class="logo" href="{{ route('home') }}">
                            <svg width="26" height="26" fill="#B9FF66"><use href="#st"/></svg>{{ config('upz.site.nama', 'UPZ BAZNAS PG') }}
                        </a>
                        <ul>
                            <li><a href="#tentang">Tentang</a></li>
                            <li><a href="#program">Program</a></li>
                            <li><a href="#dampak">Dampak</a></li>
                            <li><a href="#alur">Alur</a></li>
                            <li><a href="#tim">Tim</a></li>
                            <li><a href="{{ route('donasi.create') }}">Salurkan</a></li>
                        </ul>
                    </div>

                    <div class="peta">
                        <div class="petabox">
                            <iframe src="{{ config('upz.site.peta') }}" title="Lokasi {{ config('upz.site.lengkap') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>

                        <div class="info">
                            <div>
                                <span class="tg">Rekening zakat dan infaq</span>
                                <p>{{ config('upz.site.rekening.bank') }}, no. rekening <b>{{ config('upz.site.rekening.nomor') }}</b> a/n {{ config('upz.site.rekening.atas_nama') }}</p>
                                <p>Konfirmasikan zakat atau infaq melalui WhatsApp: <a href="{{ config('upz.site.wa_link') }}" target="_blank" rel="noopener">{{ config('upz.site.wa') }}</a></p>
                            </div>
                            <div>
                                <span class="tg">Kontak kami</span>
                                <p>{{ config('upz.site.alamat') }}</p>
                                <p>Telp. dan Fax: {{ config('upz.site.telp') }}</p>
                                <p><a href="mailto:{{ config('upz.site.email') }}">{{ config('upz.site.email') }}</a></p>
                            </div>
                            <div class="sosial">
                                @foreach (config('upz.site.sosial', []) as $nama => $item)
                                    <a href="{{ $item[0] }}" target="_blank" rel="noopener" aria-label="{{ $nama }}">
                                        <svg><use href="#{{ $item[1] }}"/></svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="cp">© {{ date('Y') }} {{ config('upz.site.lengkap') }}. Hak cipta dilindungi.</div>
                </div>
            </footer>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
