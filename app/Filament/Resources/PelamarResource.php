<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelamarResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PelamarResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pelamar';
    protected static ?string $modelLabel = 'Pelamar';
    protected static ?string $pluralModelLabel = 'Pelamar';
    protected static ?string $navigationGroup = 'Pengguna';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = filament()->auth()->user();

        return $user->hasAnyRole(['super_admin', 'Super Admin', 'Admin']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_staff', false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
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

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Terverifikasi')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => filled($record->email_verified_at)),

                Tables\Columns\TextColumn::make('jobApplications_count')
                    ->label('Lamaran')
                    ->counts('jobApplications')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Daftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reset_verification')
                    ->label('Reset Verifikasi Email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Pelamar harus memverifikasi ulang emailnya sebelum bisa login kembali.')
                    ->visible(fn (User $record) => filled($record->email_verified_at))
                    ->action(fn (User $record) => $record->forceFill(['email_verified_at' => null])->save()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelamar::route('/'),
            'view'  => Pages\ViewPelamar::route('/{record}'),
        ];
    }
}
