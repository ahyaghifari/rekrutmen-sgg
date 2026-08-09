<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff & Admin';
    protected static ?string $modelLabel = 'Staff';
    protected static ?string $pluralModelLabel = 'Staff & Admin';
    protected static ?string $navigationGroup = 'Pengguna';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = filament()->auth()->user();

        return $user->hasAnyRole(['super_admin', 'Super Admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_staff', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Akun')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->helperText('Kosongkan jika tidak ingin mengubah password.'),

                Forms\Components\Hidden::make('is_staff')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Peran & Akses')->schema([
                Forms\Components\Select::make('roles')
                    ->label('Role')
                    ->options(
                        Role::whereIn('name', ['Super Admin', 'Admin'])
                            ->pluck('name', 'name')
                    )
                    ->multiple()
                    ->preload()
                    ->required()
                    ->helperText('Super Admin: akses penuh semua perusahaan. Admin: terbatas ke perusahaan yang dipilih.'),

                Forms\Components\Select::make('company_id')
                    ->label('Perusahaan')
                    ->options(Company::query()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->helperText('Wajib diisi untuk role Admin. Super Admin tidak perlu.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Super Admin', 'super_admin' => 'danger',
                        'Admin'                      => 'warning',
                        default                      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->default('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->options(Company::query()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        $record->syncRoles([]);
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit'   => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
