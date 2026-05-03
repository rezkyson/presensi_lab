<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('login', function (Request $request) {
            $identifier = strtolower((string) $request->input('identifier'));

            return [
                Limit::perMinute(60)->by($request->ip()),
                Limit::perMinute(10)->by($identifier.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('qr-verification', function (Request $request) {
            $userKey = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(30)->by('qr:'.$userKey);
        });

        RateLimiter::for('face-verification', function (Request $request) {
            $userKey = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(20)->by('face:'.$userKey);
        });
    }
}
