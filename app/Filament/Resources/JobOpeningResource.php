<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobOpeningResource\Pages;
use App\Models\Company;
use App\Models\JobOpening;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobOpeningResource extends Resource
{
    protected static ?string $model = JobOpening::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Lowongan';
    protected static ?string $modelLabel = 'Lowongan';
    protected static ?string $pluralModelLabel = 'Lowongan';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Lowongan')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Perusahaan')
                    ->options(Company::query()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                        $set('company_name', Company::find($state)?->name)
                    ),

                Forms\Components\TextInput::make('company_name')
                    ->label('Nama Perusahaan (manual)')
                    ->helperText('Otomatis terisi dari perusahaan di atas, atau isi manual.')
                    ->nullable(),

                Forms\Components\TextInput::make('title')
                    ->label('Posisi / Jabatan')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('quota')
                    ->label('Kuota')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Detail')->schema([
                Forms\Components\RichEditor::make('description')
                    ->label('Deskripsi Jabatan')
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('requirements')
                    ->label('Kualifikasi & Persyaratan')
                    ->nullable()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('company.logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(32)
                    ->width(32)
                    ->defaultImageUrl(asset('logo-sgg.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_display')
                    ->label('Perusahaan')
                    ->getStateUsing(fn ($record) => $record->company?->name ?? $record->company_name)
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('company', fn ($q) => $q->where('name', 'like', "%$search%"))
                              ->orWhere('company_name', 'like', "%$search%")
                    ),

                Tables\Columns\TextColumn::make('quota')
                    ->label('Kuota')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->company?->management_mode !== 'hris'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->company?->management_mode !== 'hris'),
                Tables\Actions\ViewAction::make()
                    ->visible(fn ($record) => $record->company?->management_mode === 'hris'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobOpenings::route('/'),
            'create' => Pages\CreateJobOpening::route('/create'),
            'edit'   => Pages\EditJobOpening::route('/{record}/edit'),
            'view'   => Pages\ViewJobOpening::route('/{record}'),
        ];
    }
}
