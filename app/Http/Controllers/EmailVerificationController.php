<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        if (! verifikasi_email_aktif()) {
            return redirect()->route($this->tujuan($request->user()))
                ->with('info', 'Verifikasi email sedang dinonaktifkan, jadi akun Anda bisa langsung dipakai.');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($this->tujuan($request->user()))
                ->with('info', 'Email Anda sudah terverifikasi.');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $user = $request->user();
        $request->fulfill();

        AuditLog::catat('verifikasi_email', 'users', $user->id, null, [
            'email' => $user->email,
        ]);

        $tujuan = session('tujuan');
        session()->forget('tujuan');

        if ($tujuan) {
            return redirect($tujuan)
                ->with('success', 'Email berhasil diverifikasi. Sekarang Anda bisa memakai fitur lupa password.');
        }

        return redirect()->route($this->tujuan($user))
            ->with('success', 'Email berhasil diverifikasi. Sekarang Anda bisa memakai fitur lupa password.');
    }

    public function send(Request $request)
    {
        if (! verifikasi_email_aktif()) {
            return back()->with('info', 'Verifikasi email sedang dinonaktifkan, jadi tidak ada link yang perlu dikirim.');
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'Email Anda sudah terverifikasi.');
        }

        if (! $user->email) {
            return back()->with('warning', 'Tambahkan email di pengaturan akun terlebih dahulu.');
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('danger', 'Link verifikasi belum bisa dikirim saat ini. Coba lagi beberapa saat lagi.');
        }

        return back()->with('success', 'Link verifikasi baru sudah dikirim ke '.$user->email.'. Periksa inbox dan folder spam Anda.');
    }

    private function tujuan($user): string
    {
        return $user->isStaff() ? 'dashboard' : 'home';
    }
}
