<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\CompanyMessageTemplate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => \App\Filament\Resources\BaseResource::isSuperAdmin()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $companyId = $this->record->id;

        $rejected = CompanyMessageTemplate::forCompanyEvent($companyId, CompanyMessageTemplate::EVENT_SCREENING_REJECTED);
        $passed   = CompanyMessageTemplate::forCompanyEvent($companyId, CompanyMessageTemplate::EVENT_SCREENING_PASSED);

        $data['template_rejected_subject'] = $rejected?->subject ?? '';
        $data['template_rejected_body']    = $rejected?->body ?? '';
        $data['template_passed_subject']   = $passed?->subject ?? '';
        $data['template_passed_body']      = $passed?->body ?? '';

        return $data;
    }

    protected function afterSave(): void
    {
        $companyId = $this->record->id;
        $state     = $this->form->getState();

        $this->upsertTemplate($companyId, CompanyMessageTemplate::EVENT_SCREENING_REJECTED,
            $state['template_rejected_subject'] ?? '',
            $state['template_rejected_body'] ?? '');

        $this->upsertTemplate($companyId, CompanyMessageTemplate::EVENT_SCREENING_PASSED,
            $state['template_passed_subject'] ?? '',
            $state['template_passed_body'] ?? '');
    }

    private function upsertTemplate(int $companyId, string $event, string $subject, string $body): void
    {
        if (! $subject && ! $body) return;

        CompanyMessageTemplate::updateOrCreate(
            ['company_id' => $companyId, 'event' => $event],
            ['subject' => $subject, 'body' => $body]
        );
    }
}
