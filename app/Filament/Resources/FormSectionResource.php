<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormSectionResource\Pages;
use App\Filament\Resources\FormSectionResource\RelationManagers\FieldsRelationManager;
use App\Models\Company;
use App\Models\FormSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FormSectionResource extends BaseResource
{
    protected static ?string $model = FormSection::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Form Lamaran';
    protected static ?string $modelLabel = 'Bagian Form';
    protected static ?string $pluralModelLabel = 'Form Lamaran';
    protected static ?string $navigationGroup = 'Perusahaan & Lowongan';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', static::companyId());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Perusahaan')
                    ->options(fn () => static::isSuperAdmin()
                        ? Company::orderBy('name')->pluck('name', 'id')
                        : Company::where('id', static::companyId())->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->default(fn () => static::isSuperAdmin() ? null : static::companyId())
                    ->hidden(fn () => ! static::isSuperAdmin())
                    ->dehydrated(),

                Forms\Components\TextInput::make('title')
                    ->label('Judul Bagian')
                    ->required()
                    ->maxLength(255),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Bagian')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fields_count')
                    ->label('Jumlah Field')
                    ->counts('fields')
                    ->badge(),
            ])
            ->defaultSort('company_id')
            ->filters(static::isSuperAdmin() ? [
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->options(Company::orderBy('name')->pluck('name', 'id')),
            ] : [])
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelationManagers(): array
    {
        return [
            FieldsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFormSections::route('/'),
            'create' => Pages\CreateFormSection::route('/create'),
            'edit'   => Pages\EditFormSection::route('/{record}/edit'),
        ];
    }
}
