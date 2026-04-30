<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->csp());
        }

        return $response;
    }

    private function csp(): string
    {
        $isProd = app()->isProduction();

        $scriptExtra = $isProd ? '' : ' http://localhost:5173 http://127.0.0.1:5173';
        $styleExtra = $isProd ? '' : ' http://localhost:5173 http://127.0.0.1:5173';
        $connectExtra = $isProd ? '' : ' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173';
        $fontExtra = $isProd ? '' : ' http://localhost:5173 http://127.0.0.1:5173';
        $imgExtra = $isProd ? '' : ' http://localhost:5173 http://127.0.0.1:5173';

        return "default-src 'self'; "
            ."img-src 'self' data: https:{$imgExtra}; "
            ."style-src 'self' 'unsafe-inline'{$styleExtra}; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval'{$scriptExtra}; "
            ."script-src-elem 'self' 'unsafe-inline'{$scriptExtra}; "
            ."font-src 'self' data: https:{$fontExtra}; "
            ."connect-src 'self'{$connectExtra}; "
            ."frame-ancestors 'self'";
    }
}
