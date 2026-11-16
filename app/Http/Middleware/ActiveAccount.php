<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $fresh = $user->fresh();
            if (! $fresh || ! $fresh->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Akun dinonaktifkan. Hubungi administrator.']);
            }
            Auth::setUser($fresh);
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
