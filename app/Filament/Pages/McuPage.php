<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\McuSchedule;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use App\Mail\ApplicantNotificationMail;
use App\Models\ApplicationNotification;
use App\Models\CompanyMessageTemplate;
use App\Models\JobOpeningStageLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class McuPage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'MCU';
    protected static ?string $title           = 'Medical Check Up';
    protected static ?string $navigationGroup = 'Kandidat';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.pages.mcu-page';

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
        return JobOpening::where('id', $id)->where('company_id', static::companyId())
            ->whereHas('company', fn ($q) => $q->where('requires_mcu', true))->exists();
    }

    /** Hanya perusahaan yang mewajibkan MCU yang tampil di sini. */
    public function getCompanies(): \Illuminate\Support\Collection
    {
        $query = Company::where('requires_mcu', true);
        if (! static::isSuperAdmin()) {
            $query->where('id', static::companyId());
        }
        return $query->orderBy('name')->get(['id', 'name']);
    }

    public function getJobOpenings(): \Illuminate\Support\Collection
    {
        if (! $this->companyId) return collect();
        if (! static::isSuperAdmin() && $this->companyId !== (string) static::companyId()) return collect();
        return JobOpening::where('company_id', $this->companyId)->orderBy('created_at', 'desc')->get(['id', 'title']);
    }

    public function getReadyCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)->where('status', 'mcu')
            ->whereDoesntHave('mcuInvitee')->count();
    }

    public function getMcuDraft(): ?McuSchedule
    {
        return $this->jobOpeningId ? McuSchedule::draftForOpening($this->jobOpeningId) : null;
    }

    public function jadwalMcuAction(): Action
    {
        $ready = $this->jobOpeningId
            ? JobApplication::with(['answers' => fn ($q) => $q->where('field_key', 'full_name')])
                ->where('job_opening_id', $this->jobOpeningId)->where('status', 'mcu')
                ->whereDoesntHave('mcuInvitee')
                ->get()
            : collect();

        $options = $ready->mapWithKeys(fn ($app) => [
            $app->id => $app->answers->firstWhere('field_key', 'full_name')?->value ?? '(tanpa nama)',
        ])->all();

        $draft        = $this->getMcuDraft();
        $draftInvited = $draft ? $draft->invitees()->pluck('job_application_id')->toArray() : [];

        return Action::make('jadwalMcu')
            ->label('Jadwalkan MCU (' . count($options) . ' kandidat)')
            ->icon('heroicon-m-calendar-days')
            ->color('info')
            ->fillForm([
                'mcu_date' => $draft?->mcu_date?->format('Y-m-d'),
                'mcu_time' => $draft?->mcu_time,
                'location' => $draft?->location,
                'notes'    => $draft?->notes,
                'invited_ids' => $draftInvited,
            ])
            ->form([
                DatePicker::make('mcu_date')->label('Tanggal MCU')->required()->native(false)->displayFormat('d/m/Y'),
                TimePicker::make('mcu_time')->label('Waktu')->seconds(false),
                TextInput::make('location')->label('Lokasi Klinik/RS'),
                Textarea::make('notes')->label('Catatan untuk Kandidat')->rows(2),
                CheckboxList::make('invited_ids')
                    ->label('Pilih Kandidat')
                    ->options($options)->searchable()->bulkToggleable()->columns(1),
            ])
            ->modalHeading('Jadwalkan MCU')
            ->modalSubmitActionLabel('Simpan Draft Jadwal')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('lg')
            ->action(function (array $data): void {
                if (! $this->jobOpeningId) return;
                McuSchedule::saveDraft($this->jobOpeningId, $data, $data['invited_ids'] ?? []);
                \Filament\Notifications\Notification::make()->title('Draft jadwal MCU tersimpan.')->warning()->send();
            });
    }

    public function publishMcuAction(): Action
    {
        $draft    = $this->getMcuDraft();
        $invitees = $draft ? $draft->invitees()->with(['jobApplication.answers'])->get() : collect();

        $namesHtml = $invitees->map(fn ($inv) =>
            $inv->jobApplication->answers->firstWhere('field_key', 'full_name')?->value ?? '—'
        )->map(fn ($name) =>
            '<li style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;">'
            . '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3b82f6;flex-shrink:0;"></span>'
            . e($name) . '</li>'
        )->join('');

        $dateStr = $draft?->mcu_date?->translatedFormat('d F Y') ?? '-';
        $timeStr = $draft?->mcu_time ? substr($draft->mcu_time, 0, 5) : '-';

        return Action::make('publishMcu')
            ->label('Terapkan Jadwal (' . $invitees->count() . ')')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->modalHeading('Terapkan Jadwal MCU?')
            ->modalDescription("Tanggal: {$dateStr}  |  Waktu: {$timeStr}" . ($draft?->location ? "  |  {$draft->location}" : ''))
            ->form([
                Placeholder::make('diundang')
                    ->label("{$invitees->count()} Kandidat Diundang")
                    ->content(new HtmlString(
                        '<ul style="border:1px solid #e5e7eb;border-radius:8px;max-height:200px;overflow-y:auto;margin:0;padding:0;list-style:none;">'
                        . ($namesHtml ?: '<li style="padding:8px 12px;font-size:13px;color:#9ca3af;">Tidak ada</li>')
                        . '</ul>'
                    )),
            ])
            ->modalSubmitActionLabel('Terapkan')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('lg')
            ->action(function (): void {
                $draft = $this->getMcuDraft();
                if (! $draft) return;
                $draft->apply();
                $this->resetTable();
                \Filament\Notifications\Notification::make()->title('Jadwal MCU diterapkan. Undangan terkirim.')->success()->send();
            });
    }

    public function getPassedMcuCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->whereIn('status', ['onboarding', 'passed'])->count();
    }

    public function getPendingMcuCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'mcu')
            ->whereDoesntHave('mcuInvitee', fn ($q) => $q->where('status_mcu', '!=', 'pending'))
            ->count();
    }

    public function tandaiTidakLulusMcuAction(): Action
    {
        $pending = $this->jobOpeningId
            ? JobApplication::with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'mcuInvitee'])
                ->where('job_opening_id', $this->jobOpeningId)
                ->where('status', 'mcu')
                ->whereDoesntHave('mcuInvitee', fn ($q) => $q->where('status_mcu', '!=', 'pending'))
                ->get()
            : collect();

        $options = $pending->mapWithKeys(fn ($app) => [
            $app->id => $app->answers->firstWhere('field_key', 'full_name')?->value ?? '(tanpa nama)',
        ])->all();

        $count = count($options);

        return Action::make('tandaiTidakLulusMcu')
            ->label("Tandai & Beritahu Tidak Lolos MCU ({$count})")
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->form([
                CheckboxList::make('selected_ids')
                    ->label('Pilih kandidat yang TIDAK LOLOS MCU')
                    ->helperText('Kandidat dipilih akan diubah statusnya ke Tidak Dilanjutkan dan langsung dikirim email.')
                    ->options($options)->searchable()->bulkToggleable()->columns(1),
            ])
            ->modalHeading('Tandai Tidak Lolos MCU & Kirim Pemberitahuan')
            ->modalSubmitActionLabel('Tandai & Kirim Email')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('lg')
            ->action(function (array $data): void {
                if (! $this->jobOpeningId) return;
                $selectedIds = $data['selected_ids'] ?? [];
                if (empty($selectedIds)) return;

                $apps = JobApplication::with(['user', 'jobOpening'])
                    ->whereIn('id', $selectedIds)
                    ->where('job_opening_id', $this->jobOpeningId)
                    ->where('status', 'mcu')->get();

                $count = 0;
                foreach ($apps as $app) {
                    $app->update(['status' => 'rejected']);
                    $notif = ApplicationNotification::createDraftFor($app, CompanyMessageTemplate::EVENT_MCU_REJECTED);
                    if ($notif && $app->user) {
                        Mail::to($app->user->email)->send(new ApplicantNotificationMail($notif));
                        $notif->update(['status' => 'sent', 'sent_at' => now()]);
                    }
                    $count++;
                }

                JobOpeningStageLog::record($this->jobOpeningId, 'mcu', 'rejected', $count);
                $this->resetTable();

                \Filament\Notifications\Notification::make()
                    ->title("{$count} kandidat ditandai tidak lolos MCU dan email terkirim.")
                    ->success()->send();
            });
    }

    public function kirimNotifPenolakanMcuAction(): Action
    {
        $pendingCount = $this->jobOpeningId
            ? ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->where('status', 'draft')->where('event', CompanyMessageTemplate::EVENT_MCU_REJECTED)->count()
            : 0;

        return Action::make('kirimNotifPenolakanMcu')
            ->label("Kirim Notif Penolakan MCU ({$pendingCount})")
            ->icon('heroicon-m-envelope')
            ->color('danger')
            ->visible(fn () => $pendingCount > 0)
            ->requiresConfirmation()
            ->modalHeading('Kirim Pemberitahuan Tidak Lolos MCU?')
            ->modalDescription("Email akan dikirim ke {$pendingCount} kandidat. Tidak bisa dibatalkan.")
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->action(function (): void {
                if (! $this->jobOpeningId) return;
                $drafts = ApplicationNotification::with(['jobApplication.user'])
                    ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                    ->where('status', 'draft')->where('event', CompanyMessageTemplate::EVENT_MCU_REJECTED)->get();

                $count = 0;
                foreach ($drafts as $notif) {
                    Mail::to($notif->jobApplication->user->email)->send(new ApplicantNotificationMail($notif));
                    $notif->update(['status' => 'sent', 'sent_at' => now()]);
                    $count++;
                }
                \Filament\Notifications\Notification::make()->title("Email terkirim ke {$count} kandidat.")->success()->send();
            });
    }

    /** Keputusan final fit/unfit oleh HRD — gate: pemeriksaan dokter harus sudah diisi. Rekomendasi dokter cuma saran, bukan penentu otomatis. */
    public function putuskanHasilMcuAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('putuskanHasilMcu')
            ->label('Putuskan Hasil MCU')
            ->icon('heroicon-m-clipboard-document-check')
            ->color('gray')
            ->form(fn ($record) => [
                Placeholder::make('rekomendasi_dokter')
                    ->label('Rekomendasi Dokter')
                    ->content($record->mcuInvitee?->doctorExamination?->rekomendasi ?: '—'),
                Radio::make('hasil')->label('Keputusan MCU')->options([
                    'fit'   => 'Fit (Lulus)',
                    'unfit' => 'Unfit (Tidak Lulus)',
                ])->required(),
                Textarea::make('catatan_mcu')
                    ->label('Catatan Keputusan')
                    ->rows(3),
            ])
            ->action(function (array $data, $record): void {
                $invitee = $record->mcuInvitee;
                if (! $invitee) return;

                $invitee->update([
                    'status_mcu'  => $data['hasil'],
                    'catatan_mcu' => $data['catatan_mcu'] ?? null,
                ]);

                $nextStatus = $data['hasil'] === 'fit' ? 'onboarding' : 'rejected';
                $record->update(['status' => $nextStatus]);
                JobOpeningStageLog::record($record->job_opening_id, 'mcu', $nextStatus, 1);

                if ($nextStatus === 'rejected') {
                    ApplicationNotification::createDraftFor($record, CompanyMessageTemplate::EVENT_MCU_REJECTED);
                    // draft ditahan — admin kirim manual via banner
                }

                \Filament\Notifications\Notification::make()->title('Keputusan MCU tersimpan')->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'mcuInvitee.nurseAssessment', 'mcuInvitee.labResult', 'mcuInvitee.radiologyResult', 'mcuInvitee.doctorExamination'])
                    ->when(
                        $this->jobOpeningId && $this->isJobOpeningAllowed($this->jobOpeningId),
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId)->where('status', 'mcu'),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—'),

                Tables\Columns\IconColumn::make('nurse_done')->label('Perawat')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->nurseAssessment)->boolean(),
                Tables\Columns\IconColumn::make('lab_done')->label('Lab')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->labResult)->boolean(),
                Tables\Columns\IconColumn::make('radiology_done')->label('Radiologi')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->radiologyResult)->boolean(),
                Tables\Columns\IconColumn::make('doctor_done')->label('Dokter')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->doctorExamination)->boolean(),

                Tables\Columns\TextColumn::make('status_mcu')
                    ->label('Keputusan MCU')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->mcuInvitee?->status_mcu)
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'fit'   => 'Fit',
                        'unfit' => 'Unfit',
                        default => 'Belum diputuskan',
                    })
                    ->color(fn ($state) => match ($state) {
                        'fit'   => 'success',
                        'unfit' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('catatan_mcu')
                    ->label('Catatan Keputusan')
                    ->getStateUsing(fn ($record) => $record->mcuInvitee?->catatan_mcu ?? '—')
                    ->limit(60)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.pages.applicant-detail-page') . '?application=' . $record->id),
                Tables\Actions\Action::make('lihatHasilMcu')
                    ->label('Lihat Hasil MCU')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('gray')
                    ->visible(fn ($record) => $record->mcuInvitee?->hasAnyMcuResult())
                    ->url(fn ($record) => route('filament.admin.pages.hasil-mcu-detail-page') . '?invitee=' . $record->mcuInvitee->id),
                $this->putuskanHasilMcuAction()->visible(fn ($record) => $record->mcuInvitee
                    && $record->mcuInvitee->status_mcu === 'pending'
                    && $record->mcuInvitee->doctorExamDone()),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang lulus wawancara dan mewajibkan MCU akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-heart');
    }
}
