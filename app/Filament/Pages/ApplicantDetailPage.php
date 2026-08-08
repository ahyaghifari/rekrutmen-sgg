<?php

namespace App\Filament\Pages;

use App\Models\JobApplication;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApplicantDetailPage extends Page
{
    protected static ?string $navigationIcon         = 'heroicon-o-user';
    protected static string  $view                   = 'filament.pages.applicant-detail-page';
    protected static bool    $shouldRegisterNavigation = false;

    public JobApplication $application;

    public function mount(): void
    {
        $id = request('application');
        abort_if(! $id, 404);
        $this->application = JobApplication::with([
            'jobOpening.company.formSections.fields',
            'answers',
        ])->findOrFail($id);
    }

    public function getTitle(): string
    {
        return 'Detail Pelamar — ' . ($this->application->answersMap()['full_name'] ?? '');
    }

    public function changeStatus(string $status): void
    {
        $allowed = ['applied', 'screening', 'interview', 'passed', 'rejected'];
        if (! in_array($status, $allowed)) return;

        $this->application->update(['status' => $status]);
        $this->application->refresh();

        Notification::make()
            ->title('Status diperbarui')
            ->success()
            ->send();
    }
}
