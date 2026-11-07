<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
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
        VerifyEmail::createUrlUsing(function ($notifiable): string {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => $notifiable->verificationHash(),
                ]
            );
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Aktivasi Akun '.config('app.name'))
                ->view([
                    'html' => 'emails.auth.verify',
                    'text' => 'emails.auth.verify-text',
                ], [
                    'name' => $notifiable->name,
                    'email' => $notifiable->email,
                    'url' => $url,
                    'expiresInMinutes' => config('auth.verification.expire', 60),
                ]);
        });
    }
}
