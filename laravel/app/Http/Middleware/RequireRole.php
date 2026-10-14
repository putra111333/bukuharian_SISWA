<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, $roles, true), 403);

        if ($user->role !== 'super_admin' && $user->school_id && $user->school()->value('status') !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akses sekolah ini sedang dinonaktifkan. Hubungi yayasan.',
            ]);
        }

        return $next($request);
    }
}
