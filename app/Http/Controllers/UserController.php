<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            return redirect()->route('dashboard')->with('danger', 'Halaman manajemen pengguna hanya dapat diakses oleh Administrator.');
        }

        $cari = trim((string) $request->query('cari', ''));

        $query = User::query()->orderBy('id', 'desc');

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('name', 'like', "%{$cari}%")
                    ->orWhere('username', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        $total = (clone $query)->count();
        $baris = $query->paginate(10)->withQueryString();

        $peran = [
            'admin' => 'Admin',
            'bendahara' => 'Bendahara',
            'ketua' => 'Ketua',
            'user' => 'User (donatur)',
        ];

        $bisaUbah = Auth::user()->isAdmin();

        return view('users.index', compact('baris', 'total', 'cari', 'peran', 'bisaUbah'));
    }

    public function proses(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            return back()->with('danger', 'Anda tidak memiliki izin untuk mengubah data ini.');
        }

        $aksi = (string) $request->input('aksi', 'tambah');
        $id = (int) $request->input('id', 0);

        if ($aksi === 'hapus') {
            if ($id === (int) Auth::id()) {
                return back()->with('danger', 'Akun yang sedang Anda pakai tidak bisa dihapus.');
            }

            $user = User::find($id);
            if (! $user) {
                return back()->with('danger', 'Data tidak ditemukan.');
            }

            $snapshot = $user->only(['name', 'username', 'role']);
            $user->delete();

            AuditLog::catat('hapus_users', 'users', $id, $snapshot, null);

            return back()->with('success', 'Data berhasil dihapus.');
        }

        if (in_array($aksi, ['tambah', 'ubah'], true)) {
            $name = trim((string) $request->input('name'));
            $username = trim((string) $request->input('username'));
            $email = strtolower(trim((string) $request->input('email')));
            $password = (string) $request->input('password');
            $role = (string) $request->input('role');

            $peranList = ['admin', 'bendahara', 'ketua', 'user'];

            if ($name === '') {
                return back()->with('danger', 'Nama lengkap wajib diisi.');
            }
            if ($username === '') {
                return back()->with('danger', 'Username wajib diisi.');
            }
            if (! in_array($role, $peranList, true)) {
                return back()->with('danger', 'Peran tidak valid.');
            }

            // Validasi username unik
            $userAda = User::whereRaw('LOWER(username) = ?', [strtolower($username)])
                ->when($aksi === 'ubah', fn ($q) => $q->where('id', '<>', $id))
                ->first();
            if ($userAda) {
                return back()->with('danger', 'Data dengan username tersebut sudah ada.');
            }

            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->with('danger', 'Format email tidak valid.');
            }

            if ($email !== '') {
                $emailAda = User::whereRaw('LOWER(email) = ?', [$email])
                    ->when($aksi === 'ubah', fn ($q) => $q->where('id', '<>', $id))
                    ->first();
                if ($emailAda) {
                    return back()->with('danger', 'Email tersebut sudah digunakan akun lain.');
                }
            }

            $email = $email !== '' ? $email : null;

            if ($aksi === 'tambah') {
                if (strlen($password) < 8) {
                    return back()->with('danger', 'Password minimal 8 karakter.');
                }

                $user = User::create([
                    'name' => $name,
                    'username' => strtolower($username),
                    'email' => $email,
                    'role' => $role,
                    'password' => Hash::make($password),
                ]);

                $catatanEmail = '';

                if ($email !== null) {
                    try {
                        $user->sendEmailVerificationNotification();
                        $catatanEmail = ' Link verifikasi sudah dikirim ke email tersebut.';
                    } catch (\Throwable $e) {
                        report($e);
                        $catatanEmail = ' Namun link verifikasi belum bisa dikirim; minta pengguna mengirim ulang dari halaman verifikasi.';
                    }
                }

                AuditLog::catat('tambah_users', 'users', $user->id, null, [
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'role' => $role,
                ]);

                return back()->with('success', 'Data baru berhasil disimpan.'.$catatanEmail);
            } else {
                $user = User::find($id);
                if (! $user) {
                    return back()->with('danger', 'Data tidak ditemukan.');
                }

                $dataLama = $user->only(['name', 'username', 'email', 'role']);
                $emailLama = $user->email;
                $updateData = [
                    'name' => $name,
                    'username' => strtolower($username),
                    'email' => $email,
                    'role' => $role,
                ];

                if ($password !== '') {
                    if (strlen($password) < 8) {
                        return back()->with('danger', 'Password minimal 8 karakter.');
                    }
                    $updateData['password'] = Hash::make($password);
                }

                $user->update($updateData);

                $catatanEmail = '';

                if ($emailLama !== $email) {
                    // Email baru harus diverifikasi ulang sebelum bisa dipakai reset password.
                    $user->forceFill(['email_verified_at' => null])->save();

                    if ($email !== null) {
                        try {
                            $user->sendEmailVerificationNotification();
                            $catatanEmail = ' Link verifikasi sudah dikirim ke email baru.';
                        } catch (\Throwable $e) {
                            report($e);
                            $catatanEmail = ' Namun link verifikasi belum bisa dikirim; minta pengguna mengirim ulang dari halaman verifikasi.';
                        }
                    }
                }

                AuditLog::catat('ubah_users', 'users', $user->id, $dataLama, [
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'role' => $role,
                ]);

                return back()->with('success', 'Perubahan berhasil disimpan.'.$catatanEmail);
            }
        }

        return back();
    }

    public function store(Request $request)
    {
        return $this->proses($request);
    }

    public function destroy($id)
    {
        return $this->proses(new Request(['aksi' => 'hapus', 'id' => $id]));
    }
}
