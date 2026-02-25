<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->isSecure() && !app()->environment('local')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (app()->environment('local')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self' http://127.0.0.1:5173 http://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5173; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' http://127.0.0.1:5173 http://localhost:5173; script-src 'self' 'unsafe-inline' 'unsafe-eval' http://127.0.0.1:5173 http://localhost:5173; font-src 'self' data: http://127.0.0.1:5173 http://localhost:5173; connect-src 'self' ws://127.0.0.1:5173 ws://localhost:5173 http://127.0.0.1:5173 http://localhost:5173; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
            );
        } else {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
            );
        }

        return $response;
    }
}
