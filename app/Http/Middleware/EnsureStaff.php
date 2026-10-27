<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (! $user->isStaff()) {
            return redirect()->route('home')->with('error', 'Halaman ini khusus untuk pengurus UPZ.');
        }

        // Akun petugas yang punya email wajib memverifikasi email sebelum
        // bisa membuka area back-office. Akun lama tanpa email tetap diizinkan.
        if ($user->email && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Verifikasi email Anda terlebih dahulu sebelum membuka area pengurus.');
        }

        return $next($request);
    }
}
