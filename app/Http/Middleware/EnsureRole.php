<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role;

        if (! in_array($userRole, $roles, true)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses untuk tindakan ini.');
        }

        return $next($request);
    }
}
