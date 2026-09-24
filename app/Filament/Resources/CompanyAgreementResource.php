<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyAgreementResource\Pages;
use App\Models\Company;
use App\Models\CompanyAgreement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyAgreementResource extends BaseResource
{
    protected static ?string $model = CompanyAgreement::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Persetujuan Onboarding';
    protected static ?string $modelLabel = 'Persetujuan Onboarding';
    protected static ?string $pluralModelLabel = 'Persetujuan Onboarding';
    protected static ?string $navigationGroup = 'Master';
    protected static ?int $navigationSort = 3;

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
                    ->label('Judul')
                    ->helperText('Jadi persetujuan wajib yang dicentang kandidat di tahap TTD & Onboarding. Bisa ditimpa versi per-jabatan kalau judulnya sama persis.')
                    ->required()
                    ->maxLength(255),

                Forms\Components\RichEditor::make('content')
                    ->label('Naskah')
                    ->required()
                    ->columnSpanFull()
                    // Diganti di JobOpening::effectiveAgreements() saat naskah dirakit per kandidat.
                    ->helperText('Bisa pakai token yang terisi otomatis per kandidat: {jabatan} (nama lowongan) dan {divisi}. Tulis persis dengan kurung kurawal.'),
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
                    ->searchable()
                    ->visible(fn () => static::isSuperAdmin()),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanyAgreements::route('/'),
            'create' => Pages\CreateCompanyAgreement::route('/create'),
            'edit'   => Pages\EditCompanyAgreement::route('/{record}/edit'),
        ];
    }
}
