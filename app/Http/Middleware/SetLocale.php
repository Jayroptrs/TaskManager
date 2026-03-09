<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['nl', 'en']);
        $configuredFallback = (string) config('app.locale', 'nl');
        $fallback = in_array($configuredFallback, $supported, true)
            ? $configuredFallback
            : ($supported[0] ?? 'en');

        // Map legacy or variant locale values to supported app locales.
        $aliases = [
            'gb' => 'en',
            'en-gb' => 'en',
            'en_us' => 'en',
            'nl-nl' => 'nl',
        ];

        $sessionLocale = strtolower((string) $request->session()->get('locale', $fallback));
        $locale = $aliases[$sessionLocale] ?? $sessionLocale;

        if (! in_array($locale, $supported, true)) {
            $locale = $fallback;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
