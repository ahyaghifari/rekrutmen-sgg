<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApplicantDetailPage extends Page
{
    use ScopedToCompany;

    protected static ?string $navigationIcon         = 'heroicon-o-user';
    protected static string  $view                   = 'filament.pages.applicant-detail-page';
    protected static bool    $shouldRegisterNavigation = false;

    public ?JobApplication $application = null;

    public function mount(): void
    {
        $id = request('application');

        $this->application = $id
            ? JobApplication::with(['jobOpening.company.formSections.fields', 'answers'])->find($id)
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

    public function changeStatus(string $status): void
    {
        if (! $this->application) return;

        $allowed = ['applied', 'screening', 'shortlisted', 'interview', 'passed', 'rejected'];
        if (! in_array($status, $allowed)) return;

        $prevStatus = $this->application->status;
        $this->application->update(['status' => $status]);

        // Create draft notification when moving to shortlisted or rejected (from screening)
        $notifEvent = match (true) {
            $status === 'shortlisted'                                   => CompanyMessageTemplate::EVENT_SCREENING_PASSED,
            $status === 'rejected' && $prevStatus === 'screening'       => CompanyMessageTemplate::EVENT_SCREENING_REJECTED,
            default                                                     => null,
        };

        if ($notifEvent) {
            ApplicationNotification::createDraftFor($this->application, $notifEvent);
        }

        Notification::make()
            ->title('Status diperbarui')
            ->success()
            ->send();

        $this->redirect(request()->fullUrl());
    }
}
