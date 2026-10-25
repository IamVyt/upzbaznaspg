<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if ($request->filled('email')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users')->ignore($user->id)],
            'no_hp' => ['required', 'string', 'max:30'],
            'nip' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan orang lain.',
            'email.unique' => 'Email sudah digunakan orang lain.',
            'no_hp.required' => 'Nomor WhatsApp / HP wajib diisi.',
        ]);

        $dataLama = $user->only(['name', 'username', 'email', 'no_hp', 'nip']);
        $emailBaru = ! empty($validated['email']) ? strtolower($validated['email']) : null;
        $emailBerubah = $user->email !== $emailBaru;

        $user->update([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $emailBaru,
            'no_hp' => $validated['no_hp'],
            'nip' => $validated['nip'],
        ]);

        $verifikasiTerkirim = false;

        if ($emailBerubah) {
            // Email baru harus diverifikasi ulang agar status lama tidak terbawa.
            $user->forceFill(['email_verified_at' => null])->save();

            if ($emailBaru !== null) {
                try {
                    $user->sendEmailVerificationNotification();
                    $verifikasiTerkirim = true;
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        AuditLog::catat('ubah_profil', 'users', $user->id, $dataLama, $user->only(['name', 'username', 'email', 'no_hp', 'nip']));

        if ($emailBerubah && $emailBaru !== null) {
            return back()->with(
                $verifikasiTerkirim ? 'success' : 'warning',
                $verifikasiTerkirim
                    ? 'Profil diperbarui. Kami sudah mengirim link verifikasi ke email baru Anda.'
                    : 'Profil diperbarui, tetapi link verifikasi belum bisa dikirim. Coba kirim ulang dari halaman verifikasi.'
            );
        }

        if ($emailBerubah) {
            return back()->with('warning', 'Profil diperbarui. Email dihapus, sehingga reset password via email tidak tersedia sampai Anda menambahkan dan memverifikasi email.');
        }

        return back()->with('success', 'Profil akun Anda berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak cocok.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
            'password.min' => 'Password baru minimal 6 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::catat('ganti_password', 'users', $user->id, null, ['username' => $user->username]);

        return back()->with('success', 'Password Anda berhasil diperbarui.');
    }
}
