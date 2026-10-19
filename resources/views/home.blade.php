@extends('layouts.landing')

@section('title', config('upz.site.lengkap') . ' | Layanan Zakat, Infak, dan Sedekah')

@section('content')
<header class="hero" id="tentang">
    <div>
        <h1>Menebar kebaikan untuk sesama</h1>
        <p>{{ config('upz.site.lengkap') }} membantu insan perusahaan menunaikan zakat, infak, dan sedekah secara mudah, amanah, dan tepat sasaran untuk masyarakat yang membutuhkan.</p>
        <a class="btn" href="{{ route('donasi.create') }}">Mulai berbagi</a>
    </div>
    <svg viewBox="0 0 500 440" fill="none" stroke="#191A23" stroke-width="2" role="img" aria-label="Ilustrasi hati dengan orbit">
        <g style="stroke:var(--ink)">
            <ellipse cx="250" cy="230" rx="200" ry="70" transform="rotate(-20 250 230)"/>
            <ellipse cx="250" cy="230" rx="200" ry="70" transform="rotate(20 250 230)"/>
            <ellipse cx="250" cy="230" rx="150" ry="150"/>
        </g>
        <path d="M250 330C130 250 150 150 225 160C242 163 250 178 250 178C250 178 258 163 275 160C350 150 370 250 250 330Z" fill="#B9FF66" stroke="#191A23" stroke-width="3"/>
        <circle cx="110" cy="105" r="16" fill="#191A23"/>
        <circle cx="395" cy="95" r="26" fill="#B9FF66" stroke="#191A23"/>
        <circle cx="420" cy="320" r="10" fill="#B9FF66" stroke="#191A23"/>
        <use href="#st" x="60" y="290" width="60" height="60" fill="#191A23" stroke="none"/>
        <use href="#st" x="330" y="360" width="34" height="34" fill="#191A23" stroke="none"/>
    </svg>
</header>

@php
    $ulang = (int) ceil(12 / max(1, count($mitra)));
@endphp
<div class="mq" role="group" aria-label="Mitra dan pendukung">
    <div class="mq-lintas">
        @for ($g = 0; $g < 2; $g++)
            <div class="mq-grup" {!! $g ? 'aria-hidden="true"' : '' !!}>
                @for ($k = 0; $k < $ulang; $k++)
                    @foreach ($mitra as $nama)
                        <span {!! ($g || $k) ? 'aria-hidden="true"' : '' !!}>{{ $nama }}</span>
                    @endforeach
                @endfor
            </div>
        @endfor
    </div>
</div>

<section id="program">
    <div class="head">
        <h2 class="tag">Program</h2>
        <p>Kami menyediakan beberapa program untuk membantu insan perusahaan menyalurkan zakat, infak, dan sedekah dengan lebih mudah. Program ini meliputi:</p>
    </div>
    <div class="grid2">
        @foreach ($programs as $i => $prog)
            <div class="c {{ $prog['warna'] }}">
                <h3><span>{{ $prog['nama'] }}</span></h3>
                <button class="more" type="button" data-dialog="dlg-{{ $i }}" aria-haspopup="dialog">
                    <i>↗</i>Pelajari
                </button>
                <svg><use href="#{{ ikon_program($prog['nama']) }}"/></svg>
            </div>
        @endforeach
    </div>
</section>

<section>
    <div class="cta">
        <div>
            <h2>Mari wujudkan kebaikan</h2>
            <p>Hubungi kami untuk mengetahui cara menunaikan zakat, infak, dan sedekah melalui {{ config('upz.site.lengkap') }}.</p>
            <a class="btn" href="#kontak">Konsultasi gratis</a>
        </div>
        <svg viewBox="0 0 300 220" fill="none" stroke="var(--ink)" stroke-width="1.5" stroke-linejoin="round" role="img" aria-label="Ilustrasi percakapan dan donasi">
            <path d="M60 36h120a22 22 0 0 1 22 22v58a22 22 0 0 1-22 22h-62l-34 28v-28H60a22 22 0 0 1-22-22V58a22 22 0 0 1 22-22z" fill="#191A23"/>
            <path d="M120 118C96 103 94 83 107 80c6-1 11 3 13 8 2-5 7-9 13-8 13 3 11 23-13 38z" fill="#B9FF66" stroke="#191A23"/>
            <circle cx="226" cy="150" r="36" fill="#B9FF66" stroke="#191A23"/>
            <circle cx="226" cy="150" r="27" stroke="#191A23"/>
            <use href="#st" x="214" y="138" width="24" height="24" fill="#191A23" stroke="none"/>
            <use href="#st" x="222" y="14" width="44" height="44" fill="#d9d9d9" stroke="none"/>
            <circle cx="262" cy="84" r="6" fill="#191A23" stroke="none"/>
            <path d="M22 160v8M18 164h8"/>
        </svg>
    </div>
</section>

<section id="dampak">
    <div class="head">
        <h2 class="tag">Dampak</h2>
        <p>Contoh penyaluran dana zakat, infak, dan sedekah yang dikelola secara transparan.</p>
    </div>
    <div class="cs">
        @foreach ($dampak as $isi)
            <div>{{ $isi }}<br><a href="#program">Lihat program ↗</a></div>
        @endforeach
    </div>
</section>

<section id="tim">
    <div class="head">
        <h2 class="tag">Tim</h2>
        <p>Pengurus UPZ yang mengelola dan menyalurkan amanah donatur.</p>
    </div>
    <div class="team">
        @foreach ($tim as $t)
            <div class="c">
                <div class="p">
                    <svg><use href="#av"/></svg>
                    <div><b>{{ $t['nama'] }}</b><small>{{ $t['jabatan'] }}</small></div>
                </div>
                <hr>
                <p>{{ $t['desc'] }}</p>
            </div>
        @endforeach
    </div>
</section>

<section id="alur">
    <div class="head">
        <h2 class="tag">Alur kerja</h2>
        <p>Langkah demi langkah dari donasi masuk sampai bantuan sampai ke penerima.</p>
    </div>
    @foreach ($alur as $i => $al)
        <details {!! $i === 0 ? 'class="on" open' : '' !!}>
            <summary><b>{{ sprintf('%02d', $i + 1) }}</b>{{ $al['judul'] }}</summary>
            <p>{{ $al['isi'] }}</p>
        </details>
    @endforeach
</section>

<section id="donatur">
    <div class="head">
        <h2 class="tag">Donatur terbaru</h2>
        <p>Donatur yang bersedia ditampilkan. Nominal donasi dijaga privasinya.</p>
    </div>

    @if ($donaturTerbaru->isNotEmpty())
        <div class="daftar-donatur">
            <ul>
                @foreach ($donaturTerbaru as $d)
                    <li>
                        <i class="avatar-d" aria-hidden="true">{{ mb_strtoupper(mb_substr($d->nama_tampil, 0, 1)) }}</i>
                        <div class="isi-d">
                            <b>{{ $d->nama_tampil }}</b>
                            <span>{{ $d->program_nama }}</span>
                            @if ($d->tampil_doa && !empty($d->catatan))
                                <em>“{{ $d->catatan }}”</em>
                            @endif
                        </div>
                        <time>{{ $d->diproses_at ? $d->diproses_at->diffForHumans() : '-' }}</time>
                    </li>
                @endforeach
            </ul>
            <div class="aksi-d">
                <a class="btn" href="{{ route('donasi.create') }}">Ikut berdonasi</a>
            </div>
        </div>
    @else
        <div class="kosong-donatur">
            <p><b>Belum ada donatur yang tampil.</b> Jadilah yang pertama menebar kebaikan.</p>
            <a class="btn" href="{{ route('donasi.create') }}">Salurkan sekarang</a>
        </div>
    @endif
</section>

<section id="kontak">
    <div class="head">
        <h2 class="tag">Kontak</h2>
        <p>Hubungi kami untuk bertanya atau menyalurkan donasi.</p>
    </div>
    <div class="ct">
        <div>
            <div class="rad">
                <label><input type="radio" name="t" checked>Bertanya</label>
                <label><input type="radio" name="t">Info donasi</label>
            </div>
            <label for="n">Nama</label>
            <input id="n" type="text" placeholder="Nama lengkap">
            <label for="e">Email</label>
            <input id="e" type="email" placeholder="Email">
            <label for="m">Pesan</label>
            <textarea id="m" rows="4" placeholder="Tulis pesan Anda"></textarea>
            <button class="btn" type="button">Kirim pesan</button>
        </div>
        <img class="mas-kontak" src="{{ asset('img/maskot.svg') }}" alt="Maskot UPZ BAZNAS" width="340" height="342" loading="lazy" decoding="async">
    </div>
</section>

@foreach ($programs as $i => $prog)
    <dialog class="dlg" id="dlg-{{ $i }}" aria-labelledby="dt-{{ $i }}">
        <div class="dbox">
            <button class="x" type="button" data-close aria-label="Tutup">×</button>
            <h3 id="dt-{{ $i }}"><span>{{ $prog['nama'] }}</span></h3>
            <p>{{ $prog['ringkas'] }}</p>
            <ul>
                @foreach ($prog['poin'] as $p)
                    <li>{{ $p }}</li>
                @endforeach
            </ul>
            <a class="btn" href="{{ route('donasi.create', ['program' => $prog['slug']]) }}">Salurkan sekarang</a>
        </div>
    </dialog>
@endforeach

@endsection
