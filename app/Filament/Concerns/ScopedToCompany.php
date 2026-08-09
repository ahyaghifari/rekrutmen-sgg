<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait ScopedToCompany
{
    public static function user(): User
    {
        /** @var User $user */
        $user = filament()->auth()->user();

        return $user;
    }

    public static function isSuperAdmin(): bool
    {
        return static::user()->hasAnyRole(['super_admin', 'Super Admin']);
    }

    public static function companyId(): ?int
    {
        return static::user()->company_id;
    }
}
