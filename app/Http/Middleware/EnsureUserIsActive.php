<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menendang user yang dinonaktifkan di tengah sesi aktif.
 * Dijalankan untuk semua route web (user guest dilewatkan).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Akun ini sudah dinonaktifkan.');
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini sudah dinonaktifkan.',
            ]);
        }

        return $next($request);
    }
}
