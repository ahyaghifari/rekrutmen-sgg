<?php

namespace App\Filament\Pages;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ApplicantsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pelamar';
    protected static ?string $title           = 'Pelamar';
    protected static string  $view            = 'filament.pages.applicants-page';
    protected static ?int    $navigationSort  = 4;

    public ?string $jobOpeningId = null;

    public function updatedJobOpeningId(): void
    {
        $this->resetTable();
    }

    public function getJobOpenings(): \Illuminate\Support\Collection
    {
        return JobOpening::with('company')->orderBy('created_at', 'desc')->get();
    }

    public function getSelectedJobOpening(): ?JobOpening
    {
        return $this->jobOpeningId ? JobOpening::with('company')->find($this->jobOpeningId) : null;
    }

    public function getStats(): array
    {
        if (! $this->jobOpeningId) return [];

        $base = fn () => JobApplication::where('job_opening_id', $this->jobOpeningId);

        return [
            ['label' => 'Total Pelamar',   'value' => $base()->count(),                               'color' => '#042c6c', 'icon' => 'heroicon-o-users'],
            ['label' => 'Lamaran Masuk',   'value' => $base()->where('status', 'applied')->count(),   'color' => '#64748b', 'icon' => 'heroicon-o-inbox'],
            ['label' => 'Screening',        'value' => $base()->where('status', 'screening')->count(), 'color' => '#0284c7', 'icon' => 'heroicon-o-magnifying-glass'],
            ['label' => 'Wawancara',        'value' => $base()->where('status', 'interview')->count(), 'color' => '#b45309', 'icon' => 'heroicon-o-chat-bubble-left-right'],
            ['label' => 'Diterima',         'value' => $base()->where('status', 'passed')->count(),    'color' => '#047C54', 'icon' => 'heroicon-o-check-badge'],
            ['label' => 'Tidak Dilanjutkan','value' => $base()->where('status', 'rejected')->count(),  'color' => '#dc2626', 'icon' => 'heroicon-o-x-circle'],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => $this->jobOpeningId
                    ? route('admin.export.applications', ['job_opening_id' => $this->jobOpeningId])
                    : '#')
                ->openUrlInNewTab()
                ->visible(fn () => (bool) $this->jobOpeningId),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->whereIn('field_key', ['full_name', 'gender', 'whatsapp_number', 'education_level'])])
                    ->when(
                        $this->jobOpeningId,
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'gender')?->value ?? '—'),

                Tables\Columns\TextColumn::make('education_level')
                    ->label('Pendidikan')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'education_level')?->value ?? '—'),

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
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.pages.applicant-detail-page') . '?application=' . $record->id),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih lowongan terlebih dahulu')
            ->emptyStateDescription('Pilih lowongan di atas untuk melihat daftar pelamar.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
