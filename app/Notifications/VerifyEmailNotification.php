<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Email Anda — Syifa Global Group')
            ->view('emails.verify-email', [
                'name' => $notifiable->name,
                'url' => $this->verificationUrl($notifiable),
            ]);
    }
}
