<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->isStaff()) {
            return redirect()->route('home')->with('error', 'Halaman ini khusus untuk pengurus UPZ.');
        }

        return $next($request);
    }
}
