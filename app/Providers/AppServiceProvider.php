<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(fn () => Password::min(8)->letters()->numbers()->symbols());

        // Catat email verifikasi pelamar yang BERHASIL diserahkan ke SMTP (pasangan log gagal di VerifyEmailNotification::failed).
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Notifications\Events\NotificationSent::class,
            fn ($e) => $e->notification instanceof \App\Notifications\VerifyEmailNotification
                && \Illuminate\Support\Facades\Log::info('Email verifikasi pelamar terkirim.', ['email' => $e->notifiable->email])
        );

        // Mailer `resilient`: SMTP utama, otomatis ke SMTP cadangan (dengan From cadangan) kalau utama gagal. Lihat config/mail.php.
        \Illuminate\Support\Facades\Mail::extend('failover_from', function (array $config) {
            $manager = app('mail.manager');
            $make = fn (string $name) => $manager->createSymfonyTransport(config("mail.mailers.{$name}"));

            return new \App\Mail\Transport\FailoverFromTransport(
                $make($config['primary']),
                $make($config['backup']),
                $config['backup_from']['address'] ?? config('mail.from.address'),
                $config['backup_from']['name'] ?? config('mail.from.name'),
                (int) ($config['skip_minutes'] ?? 5),
            );
        });

        RateLimiter::for('api', fn (Request $request) =>
            Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );
    }
}
