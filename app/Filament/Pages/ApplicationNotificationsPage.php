<?php

namespace App\Filament\Pages;

use App\Mail\ApplicantNotificationMail;
use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\JobOpening;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class ApplicationNotificationsPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;
    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Notifikasi';
    protected static ?string $title           = 'Notifikasi Pelamar';
    protected static string  $view            = 'filament.pages.application-notifications-page';
    protected static ?int    $navigationSort  = 5;

    public ?string $companyId    = null;
    public ?string $jobOpeningId = null;

    public function updatedCompanyId(): void
    {
        $this->jobOpeningId = null;
    }

    public function getCompanies(): \Illuminate\Support\Collection
    {
        return Company::orderBy('name')->get(['id', 'name']);
    }

    public function getJobOpenings(): \Illuminate\Support\Collection
    {
        if (! $this->companyId) return collect();
        return JobOpening::where('company_id', $this->companyId)->orderBy('created_at', 'desc')->get(['id', 'title']);
    }

    public function getDrafts(): \Illuminate\Support\Collection
    {
        if (! $this->jobOpeningId) return collect();

        return ApplicationNotification::with(['jobApplication.answers'])
            ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
            ->where('status', 'draft')
            ->orderByRaw("FIELD(event, 'screening_passed', 'screening_rejected')")
            ->get();
    }

    public function getSentCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
            ->where('status', 'sent')
            ->count();
    }

    public function send(string $notificationId): void
    {
        $notif = ApplicationNotification::with(['jobApplication.user'])->findOrFail($notificationId);

        $email = $notif->jobApplication->user->email;
        Mail::to($email)->send(new ApplicantNotificationMail($notif));

        $notif->update(['status' => 'sent', 'sent_at' => now()]);

        Notification::make()->title('Email terkirim')->success()->send();
    }

    public function sendAllAction(): Action
    {
        $count = $this->jobOpeningId
            ? ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->where('status', 'draft')->count()
            : 0;

        return Action::make('sendAll')
            ->label("Kirim Semua ({$count})")
            ->icon('heroicon-m-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Kirim Semua Email?')
            ->modalDescription("Semua {$count} draft email akan dikirim sekarang. Lolos seleksi berkas dikirim lebih dahulu.")
            ->modalSubmitActionLabel('Ya, Kirim Semua')
            ->modalCancelActionLabel('Batal')
            ->action(function (): void {
                $drafts = $this->getDrafts(); // ordered: passed first
                $sent   = 0;

                foreach ($drafts as $notif) {
                    $notif->load('jobApplication.user');
                    Mail::to($notif->jobApplication->user->email)->send(new ApplicantNotificationMail($notif));
                    $notif->update(['status' => 'sent', 'sent_at' => now()]);
                    $sent++;
                }

                Notification::make()->title("{$sent} email berhasil dikirim")->success()->send();
            });
    }
}
