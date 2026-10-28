@auth
    @php
        $pengguna = auth()->user();
    @endphp
    @if (verifikasi_email_aktif() && ! $pengguna->hasVerifiedEmail() && ! request()->routeIs('verification.notice'))
        <div class="alert warning" role="status">
            @if ($pengguna->email)
                <strong>Email belum diverifikasi.</strong>
                Kami sudah mengirim link verifikasi ke {{ $pengguna->email }}. Periksa inbox atau folder spam, lalu
                <form action="{{ route('verification.send') }}" method="POST" style="display: inline; margin: 0;">
                    @csrf
                    <button type="submit" style="background: none; border: 0; padding: 0; font: inherit; color: inherit; text-decoration: underline; cursor: pointer;">kirim ulang link verifikasi</button>
                </form>.
            @else
                <strong>Email belum diisi.</strong>
                Tambahkan email di <a href="{{ route('profile.edit') }}">pengaturan akun</a> agar Anda bisa memakai fitur lupa password.
            @endif
        </div>
    @endif
@endauth
