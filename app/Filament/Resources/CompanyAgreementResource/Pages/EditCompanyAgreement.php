<?php

namespace App\Filament\Resources\CompanyAgreementResource\Pages;

use App\Filament\Resources\CompanyAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyAgreement extends EditRecord
{
    protected static string $resource = CompanyAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
