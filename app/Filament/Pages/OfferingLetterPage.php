<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobOpeningOfferingCompensationRow;
use App\Models\JobOpeningStageLog;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;
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

    /** Gaya surat SGG (tabel) vs RS (list) buat lowongan yang lagi dipilih — lihat JobOpeningResource::isSggMode(). */
    private function isSggModeForCurrentOpening(): bool
    {
        $company = JobOpening::find($this->jobOpeningId)?->company;

        return $company?->offering_letter_template !== \App\Enums\OfferingLetterTemplate::Rs;
    }

    /** Atur komponen & nominal kompensasi lowongan ini langsung dari tab Offering Letter — sama data dengan Repeater di form Lowongan. */
    public function aturKompensasiAction(): Action
    {
        return Action::make('aturKompensasi')
            ->label('Atur Kompensasi')
            ->icon('heroicon-m-banknotes')
            ->color('gray')
            ->visible(fn () => (bool) $this->jobOpeningId)
            ->form([
                Repeater::make('rows')
                    ->label('')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('label')->label('Komponen')->placeholder('Mis. Gaji Pokok')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('amount_orientasi')->label('Nominal — Orientasi')->placeholder('Angka polos, mis. 3000000')->maxLength(255),
                        TextInput::make('amount_kontrak')->label('Nominal — Kontrak')->placeholder('Angka polos, mis. 3500000')->maxLength(255),
                    ])
                    ->columns(2)
                    ->visible(fn () => $this->isSggModeForCurrentOpening())
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Komponen baru')
                    ->addActionLabel('Tambah komponen')
                    ->defaultItems(0),

                Repeater::make('mitraRows')
                    ->label('Komponen Mitra (Orientasi)')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('label')->label('Komponen')->placeholder('Mis. Gaji Pokok')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('amount_orientasi')->label('Nominal')->placeholder('Bebas teks atau kosongkan')->maxLength(255)->columnSpanFull(),
                    ])
                    ->visible(fn () => ! $this->isSggModeForCurrentOpening())
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Komponen baru')
                    ->addActionLabel('Tambah komponen Mitra')
                    ->defaultItems(0),

                Repeater::make('kontrakRows')
                    ->label('Komponen Kontrak')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('label')->label('Komponen')->placeholder('Mis. Gaji Yang Didapatkan')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('amount_kontrak')->label('Nominal')->placeholder('Bebas teks atau kosongkan')->maxLength(255)->columnSpanFull(),
                    ])
                    ->visible(fn () => ! $this->isSggModeForCurrentOpening())
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Komponen baru')
                    ->addActionLabel('Tambah komponen Kontrak')
                    ->defaultItems(0),
            ])
            ->fillForm(function () {
                $rows = JobOpening::find($this->jobOpeningId)?->offeringCompensationRows ?? collect();

                return [
                    'rows' => $rows->whereNull('kind')
                        ->map(fn ($r) => [
                            'id' => $r->id, 'label' => $r->label,
                            'amount_orientasi' => $r->amount_orientasi, 'amount_kontrak' => $r->amount_kontrak,
                        ])->values()->all(),
                    'mitraRows' => $rows->where('kind', 'mitra')
                        ->map(fn ($r) => ['id' => $r->id, 'label' => $r->label, 'amount_orientasi' => $r->amount_orientasi])
                        ->values()->all(),
                    'kontrakRows' => $rows->where('kind', 'kontrak')
                        ->map(fn ($r) => ['id' => $r->id, 'label' => $r->label, 'amount_kontrak' => $r->amount_kontrak])
                        ->values()->all(),
                ];
            })
            ->modalHeading('Atur Kompensasi Offering Letter — Lowongan Ini')
            ->modalSubmitActionLabel('Simpan')
            ->action(function (array $data): void {
                if (! $this->jobOpeningId) {
                    return;
                }

                if ($this->isSggModeForCurrentOpening()) {
                    $rows = $data['rows'] ?? [];
                    $submittedIds = collect($rows)->pluck('id')->filter()->all();

                    JobOpeningOfferingCompensationRow::where('job_opening_id', $this->jobOpeningId)
                        ->whereNull('kind')
                        ->whereNotIn('id', $submittedIds)
                        ->delete();

                    foreach ($rows as $i => $row) {
                        $values = [
                            'job_opening_id' => $this->jobOpeningId,
                            'label' => $row['label'],
                            'kind' => null,
                            'amount_orientasi' => $row['amount_orientasi'],
                            'amount_kontrak' => $row['amount_kontrak'],
                            'sort_order' => $i,
                        ];

                        if (! empty($row['id'])) {
                            JobOpeningOfferingCompensationRow::where('id', $row['id'])->update($values);
                        } else {
                            JobOpeningOfferingCompensationRow::create($values);
                        }
                    }
                } else {
                    $mitra = $data['mitraRows'] ?? [];
                    $kontrak = $data['kontrakRows'] ?? [];
                    $submittedIds = collect($mitra)->pluck('id')->filter()
                        ->merge(collect($kontrak)->pluck('id')->filter())->all();

                    JobOpeningOfferingCompensationRow::where('job_opening_id', $this->jobOpeningId)
                        ->whereIn('kind', ['mitra', 'kontrak'])
                        ->whereNotIn('id', $submittedIds)
                        ->delete();

                    foreach ($mitra as $i => $row) {
                        $values = [
                            'job_opening_id' => $this->jobOpeningId, 'label' => $row['label'], 'kind' => 'mitra',
                            'amount_orientasi' => $row['amount_orientasi'], 'amount_kontrak' => null,
                            'sort_order' => $i,
                        ];

                        if (! empty($row['id'])) {
                            JobOpeningOfferingCompensationRow::where('id', $row['id'])->update($values);
                        } else {
                            JobOpeningOfferingCompensationRow::create($values);
                        }
                    }

                    foreach ($kontrak as $i => $row) {
                        $values = [
                            'job_opening_id' => $this->jobOpeningId, 'label' => $row['label'], 'kind' => 'kontrak',
                            'amount_orientasi' => null, 'amount_kontrak' => $row['amount_kontrak'],
                            'sort_order' => $i,
                        ];

                        if (! empty($row['id'])) {
                            JobOpeningOfferingCompensationRow::where('id', $row['id'])->update($values);
                        } else {
                            JobOpeningOfferingCompensationRow::create($values);
                        }
                    }
                }

                Notification::make()->title('Kompensasi offering letter lowongan ini tersimpan')->success()->send();
            });
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

    /** Tampil selama kandidat belum konfirmasi data (nama/TTL/alamat) — surat baru bisa digenerate otomatis setelah itu. */
    public function menungguDataKandidatAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('menungguDataKandidat')
            ->label('Menunggu Data Kandidat')
            ->icon('heroicon-m-clock')
            ->color('gray')
            ->disabled()
            ->visible(fn ($record) => ! $record->offering_data_confirmed_at)
            ->tooltip('Kandidat belum mengisi konfirmasi nama/tempat-tanggal lahir/alamat di dashboardnya — begitu diisi, surat langsung bisa diunduh sendiri (diolah saat itu juga), tidak perlu aksi apa pun dari sini.');
    }

    /**
     * Lihat surat (admin). Kalau recruiter pernah unggah manual, itu yang dibuka (override);
     * kalau tidak, dirender LANGSUNG dari data kandidat + config perusahaan/lowongan saat ini
     * (OfferingLetterController::download(), tidak disimpan) — tidak ada tombol "generate"
     * terpisah, selalu otomatis & selalu versi terbaru begitu data kandidat sudah dikonfirmasi.
     */
    public function unduhSuratMasukAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('unduhSuratMasuk')
            ->label('Lihat Surat')
            ->icon('heroicon-m-eye')
            ->color('gray')
            ->visible(fn ($record) => (bool) $record->offering_letter_path || (bool) $record->offering_data_confirmed_at)
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

    /** Lihat riwayat negosiasi kandidat & balas. Balasan sekadar teks — surat revisi tetap lewat "Ganti Surat". */
    public function negosiasiAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('negosiasi')
            ->label(fn ($record) => 'Negosiasi (' . $record->offeringNegotiations->count() . ')')
            ->icon('heroicon-m-chat-bubble-left-right')
            ->color(fn ($record) => $record->offeringNegotiations->where('sender', 'applicant')->isNotEmpty() ? 'warning' : 'gray')
            ->visible(fn ($record) => $record->offeringNegotiations->isNotEmpty())
            ->modalHeading('Negosiasi Offering Letter')
            ->modalSubmitActionLabel('Kirim Balasan')
            ->form(fn ($record) => [
                Placeholder::make('riwayat')
                    ->label('Riwayat')
                    ->content(new HtmlString(collect($record->offeringNegotiations)->map(fn ($n) =>
                        '<div style="margin-bottom:8px;padding:8px 10px;border-radius:8px;background:' .
                        ($n->sender === 'applicant' ? '#f1f5f9' : '#eef2ff') . '">' .
                        '<strong>' . ($n->sender === 'applicant' ? 'Kandidat' : 'Rekruter') . '</strong> ' .
                        '<span style="color:#94a3b8">· ' . $n->created_at->format('d M Y, H:i') . '</span><br>' .
                        nl2br(e($n->message)) . '</div>'
                    )->implode(''))),
                Textarea::make('message')->label('Balasan')->rows(3)->required()->maxLength(2000),
            ])
            ->action(function (array $data, $record): void {
                $record->offeringNegotiations()->create([
                    'sender'  => 'recruiter',
                    'message' => $data['message'],
                ]);
                Notification::make()->title('Balasan negosiasi terkirim')->success()->send();
            });
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
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'offeringNegotiations'])
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
                $this->menungguDataKandidatAction(),
                $this->uploadSuratAction(),
                $this->unduhSuratMasukAction(),
                $this->negosiasiAction(),
                $this->unduhSuratSignedAction(),
                $this->lanjutkanAction(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang lulus wawancara akan muncul di sini untuk diberikan offering letter.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
