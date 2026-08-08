<?php

namespace App\Filament\Resources\JobOpeningResource\Pages;

use App\Filament\Resources\JobOpeningResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJobOpening extends CreateRecord
{
    protected static string $resource = JobOpeningResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
