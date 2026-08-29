<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\McuInvitee;
use Filament\Pages\Page;

class HasilMcuDetailPage extends Page
{
    use ScopedToCompany;

    protected static string $view = 'filament.pages.hasil-mcu-detail-page';
    protected static bool $shouldRegisterNavigation = false;

    public ?McuInvitee $invitee = null;

    public function mount(): void
    {
        $id = request('invitee');

        $this->invitee = $id
            ? McuInvitee::with(['nurseAssessment', 'labResult', 'radiologyResult', 'doctorExamination', 'jobApplication.jobOpening', 'jobApplication.answers'])->find($id)
            : null;

        abort_if(! $this->invitee, 404);

        abort_unless(
            static::isSuperAdmin() || $this->invitee->jobApplication->jobOpening->company_id === static::companyId(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'Hasil MCU';
    }
}
