<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::whereIn('role', ['admin', 'bendahara', 'ketua'])
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Administrator yang dapat menambahkan petugas.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'role' => ['required', 'in:admin,bendahara,ketua'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'nip' => ['nullable', 'string', 'max:30'],
            'password' => ['required', Password::min(6)],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'role.required' => 'Peran akun wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'],
            'role' => $validated['role'],
            'no_hp' => $validated['no_hp'],
            'nip' => $validated['nip'],
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::catat('tambah', 'users', $user->id, null, [
            'username' => $user->username,
            'role' => $user->role,
        ]);

        return back()->with('success', "Akun petugas {$user->name} ({$user->role}) berhasil ditambahkan.");
    }

    public function destroy($id)
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Administrator yang dapat menghapus akun petugas.');
        }

        if ((int)$id === (int)Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user = User::findOrFail($id);
        $snapshot = $user->toArray();
        $user->delete();

        AuditLog::catat('hapus_users', 'users', $id, $snapshot, null);

        return back()->with('success', "Akun pengguna {$user->name} berhasil dihapus.");
    }
}
