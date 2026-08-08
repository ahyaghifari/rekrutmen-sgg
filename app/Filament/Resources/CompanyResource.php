<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers\FormSectionsRelationManager;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Perusahaan';
    protected static ?string $modelLabel = 'Perusahaan';
    protected static ?string $pluralModelLabel = 'Perusahaan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Perusahaan')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('hris_code')
                    ->label('Kode HRIS')
                    ->maxLength(10)
                    ->helperText('Kode perusahaan di sistem HRIS (contoh: 0001).')
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Tampilan')->schema([
                Forms\Components\FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->directory('companies/logos')
                    ->imagePreviewHeight('80')
                    ->nullable(),

                Forms\Components\ColorPicker::make('brand_color')
                    ->label('Warna Brand')
                    ->required()
                    ->default('#042c6c'),

                Forms\Components\FileUpload::make('hero_image_path')
                    ->label('Foto Hero (halaman depan)')
                    ->image()
                    ->directory('companies/heroes')
                    ->imagePreviewHeight('120')
                    ->helperText('Foto yang tampil di banner utama halaman depan.')
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Kontak')->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email Kontak')
                    ->email()
                    ->nullable(),

                Forms\Components\TextInput::make('phone')
                    ->label('Nomor Telepon / WA')
                    ->tel()
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Status & Mode Pengelolaan')->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Perusahaan Aktif')
                    ->helperText('Nonaktifkan untuk menyembunyikan lowongan dari halaman publik.')
                    ->default(true),

                Forms\Components\Radio::make('management_mode')
                    ->label('Mode Pengelolaan Lowongan')
                    ->options([
                        'free' => 'Pengelolaan Bebas — lowongan dibuat & dikelola langsung di sistem ini',
                        'hris' => 'Pengelolaan HRIS — lowongan dikirim otomatis dari sistem HRIS eksternal',
                    ])
                    ->default('free')
                    ->required()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(36)
                    ->width(36)
                    ->defaultImageUrl(asset('logo-sgg.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ColorColumn::make('brand_color')
                    ->label('Warna'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('management_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'hris' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'hris' ? 'HRIS' : 'Bebas'),

                Tables\Columns\TextColumn::make('hris_code')
                    ->label('Kode HRIS')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            FormSectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
