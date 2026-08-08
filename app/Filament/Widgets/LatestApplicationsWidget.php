<?php

namespace App\Filament\Widgets;

use App\Models\JobApplication;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestApplicationsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Lamaran Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['jobOpening', 'answers' => fn ($q) => $q->whereIn('field_key', ['full_name'])])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('answers_name')
                    ->label('Pelamar')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('jobOpening.title')
                    ->label('Posisi')
                    ->limit(30),

                Tables\Columns\TextColumn::make('jobOpening.company_name')
                    ->label('Perusahaan')
                    ->limit(25),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'applied'   => 'gray',
                        'screening' => 'info',
                        'interview' => 'warning',
                        'passed'    => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'applied'   => 'Lamaran Masuk',
                        'screening' => 'Screening',
                        'interview' => 'Wawancara',
                        'passed'    => 'Diterima',
                        'rejected'  => 'Tidak Dilanjutkan',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
