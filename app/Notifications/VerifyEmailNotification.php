<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /** Alamat tujuan — disimpan hanya supaya bisa dicatat di log kalau pengiriman gagal. */
    public function __construct(public ?string $email = null) {}

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Email — Karir Syifa Global Group')
            ->view('emails.verify-email', [
                'name' => $notifiable->name,
                'url' => $this->verificationUrl($notifiable),
            ]);
    }

    /** Dipanggil queue setelah semua percobaan kirim gagal: catat ke log supaya admin bisa melacak. */
    public function failed(\Throwable $e): void
    {
        Log::error('Email verifikasi pelamar GAGAL terkirim.', [
            'email' => $this->email,
            'error' => $e->getMessage(),
        ]);
    }
}
