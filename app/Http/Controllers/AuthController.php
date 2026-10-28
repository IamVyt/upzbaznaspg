<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isStaff() ? redirect()->route('dashboard') : redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
        ]);

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            AuditLog::catat('login', 'users', Auth::id(), null, [
                'username' => Auth::user()->username,
                'role' => Auth::user()->role,
            ]);

            $tujuan = session('tujuan');
            session()->forget('tujuan');
            if ($tujuan) {
                return redirect($tujuan)->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
            }

            if (Auth::user()->isStaff()) {
                return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
            }

            return redirect()->intended(route('home'))->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
        }

        return back()->withErrors([
            'username' => 'Username atau password yang dimasukkan salah.',
        ])->onlyInput('username');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'nip' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan, silakan pilih yang lain.',
            'email.required' => 'Email wajib diisi agar akun bisa dipulihkan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'no_hp' => ! empty($validated['no_hp']) ? $validated['no_hp'] : null,
            'nip' => $validated['nip'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        Auth::login($user);

        AuditLog::catat('register', 'users', $user->id, null, [
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        // Saat verifikasi email dinonaktifkan, akun baru langsung bisa dipakai
        // dan tidak ada email verifikasi yang dikirim.
        if (! verifikasi_email_aktif()) {
            return redirect()->route('home')
                ->with('success', 'Pendaftaran berhasil. Selamat bergabung, '.$user->name.'!');
        }

        try {
            $user->sendEmailVerificationNotification();
            $status = 'success';
            $pesan = 'Pendaftaran berhasil. Kami sudah mengirim link verifikasi ke '.$user->email.'.';
        } catch (\Throwable $e) {
            report($e);
            $status = 'warning';
            $pesan = 'Pendaftaran berhasil, tetapi email verifikasi belum bisa dikirim. Gunakan tombol kirim ulang di halaman verifikasi.';
        }

        return redirect()->route('verification.notice')->with($status, $pesan);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('info', 'Anda telah berhasil keluar.');
    }
}
