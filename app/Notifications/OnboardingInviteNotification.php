<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
