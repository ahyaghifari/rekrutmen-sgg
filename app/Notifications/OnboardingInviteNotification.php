<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// ponytail: TIDAK ShouldQueue — tidak ada queue worker yang jalan di server ini
// (QUEUE_CONNECTION=database tanpa `queue:work`), jadi notifikasi queued cuma numpuk
// di tabel `jobs` dan tidak pernah benar-benar terkirim. Kirim sinkron seperti event lain.
class OnboardingInviteNotification extends Notification
{
    public function __construct(protected JobApplication $application)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->application->loadMissing('jobOpening.company');

        return (new MailMessage)
            ->subject('Selamat Bergabung! Undangan TTD & Onboarding — Syifa Global Group')
            ->view('emails.onboarding-invite', [
                'name'     => $notifiable->name,
                'position' => $this->application->jobOpening->title,
                'company'  => $this->application->jobOpening->company?->name ?? 'Syifa Global Group',
            ]);
    }
}
