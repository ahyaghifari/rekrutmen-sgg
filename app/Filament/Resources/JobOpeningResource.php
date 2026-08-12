<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobOpeningResource\Pages;
use App\Models\Company;
use App\Models\JobOpening;
use App\Models\Quiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobOpeningResource extends BaseResource
{
    protected static ?string $model = JobOpening::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Lowongan';
    protected static ?string $modelLabel = 'Lowongan';
    protected static ?string $pluralModelLabel = 'Lowongan';
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
            Forms\Components\Section::make('Informasi Lowongan')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Perusahaan')
                    ->options(fn () => static::isSuperAdmin()
                        ? Company::query()->pluck('name', 'id')
                        : Company::query()->where('id', static::companyId())->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->default(fn () => static::isSuperAdmin() ? null : static::companyId())
                    ->disabled(fn () => !static::isSuperAdmin())
                    ->dehydrated()
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
                    ->label('Kuota Posisi')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Forms\Components\TextInput::make('max_applicants')
                    ->label('Maks. Pelamar')
                    ->numeric()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Batas jumlah lamaran yang diterima. Kosongkan = tidak terbatas.'),

                Forms\Components\DatePicker::make('applications_deadline')
                    ->label('Batas Lamaran')
                    ->nullable()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->helperText('Lamaran tidak diterima setelah tanggal ini.'),

                Forms\Components\TextInput::make('hris_job_opening_id')
                    ->label('ID Lowongan HRIS')
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Terisi otomatis dari HRIS. Jangan ubah manual.')
                    ->disabled(fn ($record) => $record?->hris_job_opening_id !== null),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Tes Online')->schema([
                Forms\Components\Select::make('quiz_id')
                    ->label('Kuis')
                    ->options(Quiz::query()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->helperText('Pilih kuis yang dipakai untuk lowongan ini. Kelola bank soal di menu Kuis.'),

                Forms\Components\Toggle::make('requires_psychotest')
                    ->label('Wajib Psikotes Offline')
                    ->helperText('Tandai kalau posisi ini juga mewajibkan psikotes offline tambahan (di luar tes online sistem).'),
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

                Tables\Columns\TextColumn::make('max_applicants')
                    ->label('Maks. Pelamar')
                    ->default('∞')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('applications_deadline')
                    ->label('Batas Lamaran')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('hris_job_opening_id')
                    ->label('HRIS')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hris_job_opening_id !== null)
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(isToggledHiddenByDefault: true),

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
