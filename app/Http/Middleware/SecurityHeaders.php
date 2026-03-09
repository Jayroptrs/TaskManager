<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            $viteOrigins = collect([
                'http://127.0.0.1:5173',
                'http://localhost:5173',
            ]);

            $hotFile = public_path('hot');
            if (is_file($hotFile)) {
                $hotUrl = trim((string) @file_get_contents($hotFile));
                if ($hotUrl !== '') {
                    $viteOrigins->push(rtrim($hotUrl, '/'));
                }
            }

            $viteOrigins = $viteOrigins->filter()->unique()->values();
            $viteHttp = $viteOrigins->implode(' ');
            $viteWs = $viteOrigins
                ->map(function (string $origin) {
                    if (Str::startsWith($origin, 'https://')) {
                        return 'wss://'.Str::after($origin, 'https://');
                    }

                    return 'ws://'.Str::after($origin, 'http://');
                })
                ->unique()
                ->implode(' ');

            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self' {$viteHttp} {$viteWs}; img-src 'self' data: blob: https://www.google.com https://www.gstatic.com https://www.recaptcha.net; style-src 'self' 'unsafe-inline' {$viteHttp}; script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteHttp} https://www.google.com https://www.gstatic.com https://www.recaptcha.net; font-src 'self' data: {$viteHttp}; connect-src 'self' {$viteWs} {$viteHttp} https://www.google.com https://www.gstatic.com https://www.recaptcha.net; frame-src 'self' https://www.google.com https://www.recaptcha.net; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
            );
        } else {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; img-src 'self' data: blob: https://www.google.com https://www.gstatic.com https://www.recaptcha.net; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://www.recaptcha.net; font-src 'self' data:; connect-src 'self' https://www.google.com https://www.gstatic.com https://www.recaptcha.net; frame-src 'self' https://www.google.com https://www.recaptcha.net; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
            );
        }

        return $response;
    }
}
