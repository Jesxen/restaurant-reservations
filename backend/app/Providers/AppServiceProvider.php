<?php

namespace App\Providers;

use App\Services\SmsService;
use App\Services\StripeService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the optional third-party integrations from config so they can be
        // swapped for fakes in tests and degrade gracefully when unconfigured.
        $this->app->singleton(StripeService::class, function ($app) {
            $config = $app['config']->get('services.stripe');

            return new StripeService(
                secret: $config['secret'] ?? null,
                webhookSecret: $config['webhook_secret'] ?? null,
                currency: $config['currency'] ?? 'eur',
            );
        });

        $this->app->singleton(SmsService::class, function ($app) {
            $config = $app['config']->get('services.twilio');

            return new SmsService(
                sid: $config['sid'] ?? null,
                token: $config['auth_token'] ?? null,
                from: $config['from'] ?? null,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->configureNotificationUrls();
    }

    /**
     * Point password-reset and email-verification links at the SPA frontend
     * (FRONTEND_URL) rather than backend routes, since those pages live there.
     */
    private function configureNotificationUrls(): void
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        // Password reset → frontend page that POSTs back to /api/reset-password.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) use ($frontend) {
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return "{$frontend}/restablecer-password?token={$token}&email={$email}";
        });

        // Email verification → keep the signed backend URL, but wrap it so the
        // frontend can present a friendly page that opens it.
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $signed = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return $signed;
        });
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
