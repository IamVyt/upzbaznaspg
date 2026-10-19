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

        $user->update([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'],
            'no_hp' => $validated['no_hp'],
            'nip' => $validated['nip'],
        ]);

        AuditLog::catat('ubah_profil', 'users', $user->id, $dataLama, $user->only(['name', 'username', 'email', 'no_hp', 'nip']));

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
