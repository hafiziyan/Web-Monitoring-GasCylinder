<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Penggunaan: ->middleware('role:admin') atau ->middleware('role:admin,operator')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Akun yang dinonaktifkan di tengah sesi langsung ditendang.
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini sudah dinonaktifkan.',
            ]);
        }

        $userRole = $user->role instanceof UserRole ? $user->role->value : $user->role;

        if (! in_array($userRole, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
