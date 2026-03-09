<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by(strtolower($email).'|'.$request->ip()),
            ];
        });

        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
        ]);

        RateLimiter::for('support-submissions', function (Request $request) {
            if ($request->user()) {
                return [
                    Limit::perMinutes(10, 15)->by('support:user:'.$request->user()->id),
                ];
            }

            return [
                Limit::perMinutes(10, 3)->by('support:guest:'.$request->ip()),
            ];
        });

        RateLimiter::for('profile-update', fn (Request $request) => [
            Limit::perMinute(6)->by('profile:'.$request->user()?->id),
        ]);

        RateLimiter::for('admin-actions', fn (Request $request) => [
            Limit::perMinute(30)->by('admin:'.$request->user()?->id),
        ]);

        RateLimiter::for('collaboration-actions', fn (Request $request) => [
            Limit::perMinute(20)->by('collab:'.($request->user()?->id ?? $request->ip())),
        ]);
    }
}
