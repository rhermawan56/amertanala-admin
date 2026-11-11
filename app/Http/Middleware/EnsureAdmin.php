<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin, 403);
        $started = (int) $request->session()->get('admin_started_at', time());
        $seen = (int) $request->session()->get('admin_last_seen_at', time());
        if (time() - $started >= 28800 || time() - $seen >= 900) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('status', 'Sesi berakhir. Silakan masuk kembali.');
        }
        $request->session()->put('admin_started_at', $started);
        if (! $request->is('inbox/*/messages', 'inbox/*/read') && ! $request->headers->has('X-Inertia-Partial-Data')) {
            $request->session()->put('admin_last_seen_at', time());
        }

        return $next($request);
    }
}
