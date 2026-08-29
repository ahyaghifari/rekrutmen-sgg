<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobOpeningStageLog;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfferingLetterPage extends Page implements HasTable
{
    use InteractsWithTable, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Offering Letter';
    protected static ?string $title           = 'Offering Letter';
    protected static ?string $navigationGroup = 'Kandidat';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.offering-letter-page';

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

    /** Recruiter upload surat penawaran (dibuat manual di luar sistem — Word/PDF, format bebas per jabatan/kandidat). */
    public function uploadSuratAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('uploadSurat')
            ->label(fn ($record) => $record->offering_letter_path ? 'Ganti Surat' : 'Unggah Surat Penawaran')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('gray')
            ->form([
                FileUpload::make('file')
                    ->label('File Surat Penawaran')
                    ->disk('local')
                    ->directory(fn ($record) => "offering-letters/{$record->id}/recruiter")
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->required(),
            ])
            ->action(function (array $data, $record): void {
                $record->update([
                    'offering_letter_path'         => $data['file'],
                    'offering_letter_uploaded_at'  => now(),
                ]);

                ApplicationNotification::createAndSend($record, CompanyMessageTemplate::EVENT_OFFERING_LETTER_READY);

                Notification::make()->title('Surat penawaran diunggah — email dikirim, kandidat sudah bisa unduh dari portalnya')->success()->send();
            });
    }

    public function unduhSuratMasukAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('unduhSuratMasuk')
            ->label('Lihat Surat')
            ->icon('heroicon-m-eye')
            ->color('gray')
            ->visible(fn ($record) => (bool) $record->offering_letter_path)
            ->url(fn ($record) => route('admin.offering-letter.download', ['application' => $record->id, 'type' => 'recruiter']))
            ->openUrlInNewTab();
    }

    public function unduhSuratSignedAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('unduhSuratSigned')
            ->label('Lihat Surat Bertanda Tangan')
            ->icon('heroicon-m-document-check')
            ->color('success')
            ->visible(fn ($record) => (bool) $record->offering_letter_signed_path)
            ->url(fn ($record) => route('admin.offering-letter.download', ['application' => $record->id, 'type' => 'signed']))
            ->openUrlInNewTab();
    }

    public function lanjutkanAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('lanjutkanSetelahOffering')
            ->label(fn ($record) => ($record->jobOpening->company?->requires_mcu ?? true) ? 'Lanjut ke MCU' : 'Lanjut ke Onboarding')
            ->icon('heroicon-m-arrow-right-circle')
            ->color('success')
            ->visible(fn ($record) => $record->offering_letter_signed_path !== null)
            ->requiresConfirmation()
            ->modalDescription('Pastikan surat penawaran yang diunggah kandidat sudah benar sebelum melanjutkan.')
            ->action(function ($record) {
                $nextStatus = ($record->jobOpening->company?->requires_mcu ?? true) ? 'mcu' : 'onboarding';
                $record->update(['status' => $nextStatus]);
                JobOpeningStageLog::record($record->job_opening_id, 'offering', $nextStatus, 1);

                Notification::make()->title('Kandidat dilanjutkan ke tahap berikutnya')->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name')])
                    ->when(
                        $this->jobOpeningId && $this->isJobOpeningAllowed($this->jobOpeningId),
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId)->where('status', 'offering'),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—'),

                Tables\Columns\IconColumn::make('surat_diunggah')
                    ->label('Surat Diunggah')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->offering_letter_path !== null),

                Tables\Columns\IconColumn::make('surat_signed')
                    ->label('Sudah TTD & Diunggah Kandidat')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->offering_letter_signed_path !== null),

                Tables\Columns\TextColumn::make('offering_letter_signed_at')
                    ->label('Diunggah Kandidat Pada')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—'),
            ])
            ->actions([
                $this->uploadSuratAction(),
                $this->unduhSuratMasukAction(),
                $this->unduhSuratSignedAction(),
                $this->lanjutkanAction(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang lulus wawancara akan muncul di sini untuk diberikan offering letter.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
