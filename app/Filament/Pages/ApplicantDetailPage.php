<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApplicantDetailPage extends Page implements HasActions
{
    use ScopedToCompany, InteractsWithActions;

    protected static ?string $navigationIcon         = 'heroicon-o-user';
    protected static string  $view                   = 'filament.pages.applicant-detail-page';
    protected static bool    $shouldRegisterNavigation = false;

    public ?JobApplication $application = null;

    public function mount(): void
    {
        $id = request('application');

        $this->application = $id
            ? JobApplication::with([
                'jobOpening.company.formSections.fields',
                'answers',
                'candidate.familyMembers',
                'candidate.educations',
                'candidate.trainings',
                'candidate.organizations',
                'candidate.workExperiences',
                'candidate.references',
                'candidate.additionalInfo',
                'candidate.selfAssessment',
                'candidate.documents',
                'candidate.agreements',
            ])->find($id)
            : null;

        abort_if(! $this->application, 404);

        abort_unless(
            static::isSuperAdmin() || $this->application->jobOpening->company_id === static::companyId(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'Detail Pelamar — ' . ($this->application?->answersMap()['full_name'] ?? '');
    }

    public function changeStatus(string $status, ?string $reason = null): void
    {
        if (! $this->application) return;

        $allowed = ['applied', 'screening', 'shortlisted', 'interview', 'passed', 'rejected'];
        if (! in_array($status, $allowed)) return;

        $prevStatus = $this->application->status;
        $this->application->update(['status' => $status]);

        // "Tidak Dilanjutkan" manual (di luar alur normal) — catat alasannya di log status yang baru dibuat.
        if ($status === 'rejected' && $reason && trim($reason) !== '') {
            $this->application->statusLogs()->latest('changed_at')->first()?->update(['notes' => trim($reason)]);
        }

        // Create draft notification when moving to shortlisted or rejected.
        $notifEvent = match (true) {
            $status === 'shortlisted'                                   => CompanyMessageTemplate::EVENT_SCREENING_PASSED,
            $status === 'rejected' && $prevStatus === 'screening'       => CompanyMessageTemplate::EVENT_SCREENING_REJECTED,
            // "Tidak Dilanjutkan" manual di tahap lain (tes/wawancara/mcu/onboarding) — belum ada event khusus per tahap di sini.
            $status === 'rejected'                                      => CompanyMessageTemplate::EVENT_MANUAL_REJECTED,
            default                                                     => null,
        };

        if ($notifEvent) {
            ApplicationNotification::createDraftFor($this->application, $notifEvent, ['notes' => $reason]);
        }

        Notification::make()
            ->title('Status diperbarui')
            ->success()
            ->send();

        $this->redirect(request()->fullUrl());
    }

    public function tidakDilanjutkanAction(): Action
    {
        return Action::make('tidakDilanjutkan')
            ->label('Tidak Dilanjutkan')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->visible(fn () => $this->application && ! in_array($this->application->status, ['rejected', 'passed']))
            ->modalHeading('Tandai Tidak Dilanjutkan')
            ->modalDescription('Dipakai di luar alur normal — misalnya kandidat mengundurkan diri di tengah proses, bukan karena gagal di salah satu tahap.')
            ->modalSubmitActionLabel('Tidak Dilanjutkan')
            ->modalCancelActionLabel('Batal')
            ->form([
                Textarea::make('reason')
                    ->label('Alasan Tidak Dilanjutkan')
                    ->placeholder('Mis. Kandidat mengundurkan diri karena sudah diterima di tempat lain.')
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->changeStatus('rejected', $data['reason']);
            });
    }

    public function kirimNotifTidakDilanjutkanAction(): Action
    {
        return Action::make('kirimNotifTidakDilanjutkan')
            ->label('Kirim Notif Tidak Dilanjutkan')
            ->icon('heroicon-m-envelope')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Kirim Pemberitahuan Tidak Dilanjutkan?')
            ->modalDescription('Email tidak dilanjutkan akan dikirim ke pelamar ini. Tindakan ini tidak bisa dibatalkan.')
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->action(function (): void {
                $notif = ApplicationNotification::where('job_application_id', $this->application->id)
                    ->where('status', 'draft')
                    ->where('event', CompanyMessageTemplate::EVENT_MANUAL_REJECTED)
                    ->first();

                if ($notif) {
                    $notif->update(['status' => 'queued']);
                }

                Notification::make()->title('Email tidak dilanjutkan terkirim.')->success()->send();
            });
    }
}
