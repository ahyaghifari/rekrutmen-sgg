<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToCompany;
use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    use ScopedToCompany;

    public static function canAccess(): bool
    {
        return static::user()->hasAnyRole(['super_admin', 'Super Admin', 'Admin']);
    }
}
