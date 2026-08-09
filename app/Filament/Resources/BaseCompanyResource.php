<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base untuk resource yang datanya ber-company_id.
 * Super Admin: lihat semua perusahaan + kolom/filter/field "Perusahaan" tampil.
 * Admin: dibatasi company_id sendiri, kolom/filter/field "Perusahaan" tersembunyi.
 * company_id tetap ter-set otomatis saat create/edit karena ->default() berjalan walau hidden.
 */
abstract class BaseCompanyResource extends BaseResource
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', static::companyId());
    }

    public static function companyFormField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('company_id')
            ->relationship('company', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->label('Perusahaan')
            ->default(fn () => static::companyId())
            ->visible(fn () => static::isSuperAdmin());
    }

    public static function companyTableColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('company.name')
            ->label('Perusahaan')
            ->searchable()
            ->sortable()
            ->visible(fn () => static::isSuperAdmin());
    }

    public static function companyTableFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('company_id')
            ->relationship('company', 'name')
            ->label('Perusahaan')
            ->preload()
            ->searchable()
            ->visible(fn () => static::isSuperAdmin());
    }
}
