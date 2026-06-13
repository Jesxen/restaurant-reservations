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
        $this->configureRateLimiters();
    }

    /**
     * Named rate limiters for sensitive public endpoints. All return JSON 429
     * because the exception handler renders JSON for api/* requests.
     */
    private function configureRateLimiters(): void
    {
        // Auth: throttle per IP + email to slow credential stuffing without
        // letting one attacker lock out a whole shared NAT.
        RateLimiter::for('auth', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(6)->by($request->ip().'|'.mb_strtolower($email));
        });

        // Contact form.
        RateLimiter::for('contacto', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Public reservation creation.
        RateLimiter::for('reservas', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // General fallback limiter for the api group.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
