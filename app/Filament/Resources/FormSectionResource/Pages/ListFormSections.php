<?php

namespace App\Filament\Resources\FormSectionResource\Pages;

use App\Filament\Resources\FormSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormSections extends ListRecords
{
    protected static string $resource = FormSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
