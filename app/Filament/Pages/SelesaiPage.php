<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class SelesaiPage extends Page implements HasTable
{
    use InteractsWithTable, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Selesai';
    protected static ?string $title           = 'Selesai — Diterima Jadi Karyawan';
    protected static ?string $navigationGroup = 'Kandidat';
    protected static ?int    $navigationSort  = 7;
    protected static string  $view            = 'filament.pages.selesai-page';

    public ?string $companyId    = null;
    public ?string $jobOpeningId = null;

    public function mount(): void
    {
        if (! static::isSuperAdmin()) {
            $this->companyId = (string) static::companyId();
        }
    }

    public function updatedCompanyId(): void
    {
        if (! static::isSuperAdmin() && $this->companyId !== (string) static::companyId()) {
            $this->companyId = (string) static::companyId();
        }
        $this->jobOpeningId = null;
        $this->resetTable();
    }

    public function updatedJobOpeningId(): void
    {
        if ($this->jobOpeningId && ! $this->isJobOpeningAllowed($this->jobOpeningId)) {
            $this->jobOpeningId = null;
        }
        $this->resetTable();
    }

    private function isJobOpeningAllowed(?string $id): bool
    {
        if (! $id) return false;
        if (static::isSuperAdmin()) return true;
        return JobOpening::where('id', $id)->where('company_id', static::companyId())->exists();
    }

    public function getCompanies(): \Illuminate\Support\Collection
    {
        if (! static::isSuperAdmin()) {
            return Company::where('id', static::companyId())->get(['id', 'name']);
        }
        return Company::orderBy('name')->get(['id', 'name']);
    }

    public function getJobOpenings(): \Illuminate\Support\Collection
    {
        if (! $this->companyId) return collect();
        if (! static::isSuperAdmin() && $this->companyId !== (string) static::companyId()) return collect();
        return JobOpening::where('company_id', $this->companyId)->orderBy('created_at', 'desc')->get(['id', 'title']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'candidate', 'jobOpening'])
                    ->when(
                        $this->jobOpeningId && $this->isJobOpeningAllowed($this->jobOpeningId),
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId)->where('status', 'passed'),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—'),

                Tables\Columns\TextColumn::make('jobOpening.title')
                    ->label('Lowongan')
                    ->searchable(),

                Tables\Columns\IconColumn::make('data_diri_lengkap')
                    ->label('Data Diri Kandidat')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->candidate?->nik)),

                Tables\Columns\TextColumn::make('updated_at')->label('Tanggal Diterima')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.pages.applicant-detail-page') . '?application=' . $record->id),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang sudah resmi diterima jadi karyawan akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
