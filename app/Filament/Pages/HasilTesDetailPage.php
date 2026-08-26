<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\QuizAttempt;
use Filament\Pages\Page;

class HasilTesDetailPage extends Page
{
    use ScopedToCompany;

    protected static string $view = 'filament.pages.hasil-tes-detail-page';
    protected static bool $shouldRegisterNavigation = false;

    public ?QuizAttempt $attempt = null;

    public function mount(): void
    {
        $id = request('attempt');

        $this->attempt = $id
            ? QuizAttempt::with(['quiz', 'jobApplication.jobOpening', 'answers.question'])->find($id)
            : null;

        abort_if(! $this->attempt, 404);

        abort_unless(
            static::isSuperAdmin() || $this->attempt->jobApplication->jobOpening->company_id === static::companyId(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'Hasil Tes — ' . ($this->attempt?->quiz->name ?? '');
    }
}
