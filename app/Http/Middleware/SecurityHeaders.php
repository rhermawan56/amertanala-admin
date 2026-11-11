<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $origins = implode(' ', array_filter(config('workspace.frame_origins'), fn ($origin) => preg_match('~^https?://[a-zA-Z0-9.:-]+$~', $origin)));
        $ancestors = $request->is('chat*') ? "'self' ".$origins : "'none'";
        $response->headers->set('Content-Security-Policy', 'frame-ancestors '.$ancestors."; object-src 'none'; base-uri 'self'");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if (! $request->is('api/public/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
