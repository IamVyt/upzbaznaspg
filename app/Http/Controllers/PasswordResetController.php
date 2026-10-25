<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        // Hanya email yang sudah diverifikasi yang boleh menerima link reset.
        if (! $user || ! $user->hasVerifiedEmail()) {
            return back()->with(
                'success',
                'Jika email tersebut terdaftar dan sudah diverifikasi, kami sudah mengirim link reset password. Periksa inbox dan folder spam Anda.'
            );
        }

        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'danger',
                'Link reset belum bisa dikirim saat ini. Hubungi pengurus UPZ untuk dibantu.'
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->with('warning', __($status));
        }

        // Pesan dibuat generik agar tidak membocorkan apakah sebuah email terdaftar.
        return back()->with(
            'success',
            'Jika email tersebut terdaftar dan sudah diverifikasi, kami sudah mengirim link reset password. Periksa inbox dan folder spam Anda.'
        );
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:100'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ], [
            'token.required' => 'Token reset password tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Link reset password tidak valid atau sudah kedaluwarsa.']);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLog::catat('reset_password', 'users', $user->id, null, [
                    'username' => $user->username,
                    'email' => $user->email,
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'Password berhasil direset. Silakan masuk dengan password baru Anda.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
