<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\Company;
use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use App\Models\JobOpening;
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

class WawancaraPage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Wawancara';
    protected static ?string $title           = 'Wawancara';
    protected static ?string $navigationGroup = 'Kandidat';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.pages.wawancara-page';

    public ?string $companyId    = null;
    public ?string $jobOpeningId = null;
    public ?string $statusFilter = null;

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

    public function updatedStatusFilter(): void
    {
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

    /** Pelamar lulus tes online, siap diundang wawancara. */
    public function getReadyCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'shortlisted')->where('quiz_result', 'lulus')->count();
    }

    public function getInterviewDraft(): ?InterviewSchedule
    {
        return $this->jobOpeningId ? InterviewSchedule::draftForOpening($this->jobOpeningId) : null;
    }

    public function jadwalWawancaraAction(): Action
    {
        $ready = $this->jobOpeningId
            ? JobApplication::with(['answers' => fn ($q) => $q->where('field_key', 'full_name')])
                ->where('job_opening_id', $this->jobOpeningId)
                ->where('status', 'shortlisted')->where('quiz_result', 'lulus')
                ->get()
            : collect();

        $options = $ready->mapWithKeys(fn ($app) => [
            $app->id => $app->answers->firstWhere('field_key', 'full_name')?->value ?? '(tanpa nama)',
        ])->all();

        $draft        = $this->getInterviewDraft();
        $draftInvited = $draft ? $draft->invitees()->pluck('job_application_id')->toArray() : [];

        return Action::make('jadwalWawancara')
            ->label('Jadwalkan Wawancara (' . count($options) . ' pelamar)')
            ->icon('heroicon-m-calendar-days')
            ->color('info')
            ->fillForm([
                'interview_date' => $draft?->interview_date?->format('Y-m-d'),
                'interview_time' => $draft?->interview_time,
                'location'       => $draft?->location,
                'notes'          => $draft?->notes,
                'invited_ids'    => $draftInvited,
            ])
            ->form([
                DatePicker::make('interview_date')->label('Tanggal Wawancara')->required()->native(false)->displayFormat('d/m/Y'),
                TimePicker::make('interview_time')->label('Waktu')->seconds(false),
                TextInput::make('location')->label('Lokasi / Link Meeting')->placeholder('Contoh: Zoom https://... atau Jl. Merdeka No. 1'),
                Textarea::make('notes')->label('Catatan untuk Pelamar')->rows(2),
                CheckboxList::make('invited_ids')
                    ->label('Pilih Pelamar yang Diundang')
                    ->helperText('Draft ini belum mengubah status pelamar.')
                    ->options($options)->searchable()->bulkToggleable()->columns(1),
            ])
            ->modalHeading('Jadwalkan Wawancara')
            ->modalSubmitActionLabel('Simpan Draft Jadwal')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('lg')
            ->action(function (array $data): void {
                if (! $this->jobOpeningId) return;
                InterviewSchedule::saveDraft($this->jobOpeningId, $data, $data['invited_ids'] ?? []);
                \Filament\Notifications\Notification::make()
                    ->title('Draft jadwal wawancara tersimpan. Status pelamar belum berubah.')
                    ->warning()->send();
            });
    }

    public function publishInterviewAction(): Action
    {
        $draft    = $this->getInterviewDraft();
        $invitees = $draft ? $draft->invitees()->with(['jobApplication.answers'])->get() : collect();

        $namesHtml = $invitees->map(fn ($inv) =>
            $inv->jobApplication->answers->firstWhere('field_key', 'full_name')?->value ?? '—'
        )->map(fn ($name) =>
            '<li style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;">'
            . '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3b82f6;flex-shrink:0;"></span>'
            . e($name) . '</li>'
        )->join('');

        $dateStr = $draft?->interview_date?->translatedFormat('d F Y') ?? '-';
        $timeStr = $draft?->interview_time ? substr($draft->interview_time, 0, 5) : '-';

        return Action::make('publishInterview')
            ->label('Terapkan Jadwal (' . $invitees->count() . ')')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->modalHeading('Terapkan Jadwal Wawancara?')
            ->modalDescription("Tanggal: {$dateStr}  |  Waktu: {$timeStr}" . ($draft?->location ? "  |  {$draft->location}" : ''))
            ->form([
                Placeholder::make('diundang')
                    ->label("{$invitees->count()} Pelamar Diundang")
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
                $draft = $this->getInterviewDraft();
                if (! $draft) return;
                $count = $draft->invitees()->count();
                $draft->apply();
                if ($this->jobOpeningId) {
                    \App\Models\JobOpeningStageLog::record($this->jobOpeningId, 'shortlisted', 'interview', $count);
                }
                $this->resetTable();
                \Filament\Notifications\Notification::make()
                    ->title('Jadwal diterapkan. Undangan wawancara terkirim.')
                    ->success()->send();
            });
    }

    public function getPassedInterviewCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->whereIn('status', ['offering', 'mcu', 'onboarding', 'passed'])->count();
    }

    public function getPendingInterviewCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'interview')->whereNull('interview_result')->count();
    }

    public function tandaiTidakLulusWawancaraAction(): Action
    {
        $pending = $this->jobOpeningId
            ? JobApplication::with(['answers' => fn ($q) => $q->where('field_key', 'full_name')])
                ->where('job_opening_id', $this->jobOpeningId)
                ->where('status', 'interview')->whereNull('interview_result')
                ->get()
            : collect();

        $options = $pending->mapWithKeys(fn ($app) => [
            $app->id => $app->answers->firstWhere('field_key', 'full_name')?->value ?? '(tanpa nama)',
        ])->all();

        $count = count($options);

        return Action::make('tandaiTidakLulusWawancara')
            ->label("Tandai & Beritahu Tidak Lolos ({$count})")
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->form([
                CheckboxList::make('selected_ids')
                    ->label('Pilih kandidat yang TIDAK LOLOS wawancara')
                    ->helperText('Kandidat dipilih akan diubah statusnya ke Tidak Dilanjutkan dan langsung dikirim email.')
                    ->options($options)->searchable()->bulkToggleable()->columns(1),
            ])
            ->modalHeading('Tandai Tidak Lolos Wawancara & Kirim Pemberitahuan')
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
                    ->where('status', 'interview')->get();

                $count = 0;
                foreach ($apps as $app) {
                    $app->update(['interview_result' => 'tidak_lulus', 'status' => 'rejected']);
                    $notif = ApplicationNotification::createDraftFor($app, CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED);
                    if ($notif && $app->user) {
                        $notif->update(['status' => 'queued']);
                    }
                    $count++;
                }

                JobOpeningStageLog::record($this->jobOpeningId, 'interview', 'rejected', $count);
                $this->resetTable();

                \Filament\Notifications\Notification::make()
                    ->title("{$count} kandidat ditandai tidak lolos wawancara dan email terkirim.")
                    ->success()->send();
            });
    }

    /** Catat hasil wawancara: lulus lanjut MCU (kalau perusahaan mewajibkan) atau langsung onboarding; tidak lulus → rejected. */
    public function kirimNotifPenolakanWawancaraAction(): Action
    {
        $pendingCount = $this->jobOpeningId
            ? \App\Models\ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->where('status', 'draft')
                ->where('event', \App\Models\CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED)
                ->count()
            : 0;

        return Action::make('kirimNotifPenolakanWawancara')
            ->label("Kirim Notif Tidak Dilanjutkan ({$pendingCount})")
            ->icon('heroicon-m-envelope')
            ->color('danger')
            ->visible(fn () => $pendingCount > 0)
            ->requiresConfirmation()
            ->modalHeading('Kirim Pemberitahuan Tidak Lolos Wawancara?')
            ->modalDescription("Email tidak dilanjutkan akan dikirim ke {$pendingCount} kandidat yang tidak lolos wawancara. Tindakan ini tidak bisa dibatalkan.")
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->action(function (): void {
                if (! $this->jobOpeningId) return;

                $drafts = \App\Models\ApplicationNotification::with(['jobApplication.user'])
                    ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                    ->where('status', 'draft')
                    ->where('event', \App\Models\CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED)
                    ->get();

                $count = 0;
                foreach ($drafts as $notif) {
                    $email = $notif->jobApplication->user->email;
                    $notif->update(['status' => 'queued']);
                    $count++;
                }

                \Filament\Notifications\Notification::make()
                    ->title("Email tidak dilanjutkan terkirim ke {$count} kandidat.")
                    ->success()->send();
            });
    }

    public function tandaiHasilWawancaraAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('tandaiHasilWawancara')
            ->label('Catat Hasil')
            ->icon('heroicon-m-clipboard-document-check')
            ->color('gray')
            ->form([
                Radio::make('hasil')->label('Hasil Wawancara')->options([
                    'lulus'       => 'Lulus',
                    'tidak_lulus' => 'Tidak Lulus',
                ])->required(),
            ])
            ->action(function (array $data, $record): void {
                $company = $record->jobOpening->company;

                if ($data['hasil'] === 'lulus') {
                    // ponytail: semua yang lulus wawancara singgah dulu di tahap Offering Letter
                    // sebelum lanjut MCU/onboarding — lihat OfferingLetterPage.
                    $record->update(['interview_result' => 'lulus', 'status' => 'offering']);
                    \App\Models\JobOpeningStageLog::record($record->job_opening_id, 'interview', 'offering', 1);
                    \App\Models\ApplicationNotification::createAndSend(
                        $record,
                        \App\Models\CompanyMessageTemplate::EVENT_INTERVIEW_PASSED
                    );
                } else {
                    $record->update(['interview_result' => 'tidak_lulus', 'status' => 'rejected']);
                    \App\Models\JobOpeningStageLog::record($record->job_opening_id, 'interview', 'rejected', 1);
                    // Buat draft notif penolakan — TIDAK langsung dikirim, admin kirim manual nanti
                    \App\Models\ApplicationNotification::createDraftFor(
                        $record,
                        \App\Models\CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED
                    );
                }

                \Filament\Notifications\Notification::make()->title('Hasil wawancara tersimpan')->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->whereIn('field_key', ['full_name', 'gender', 'whatsapp_number']), 'jobOpening.company'])
                    ->when(
                        $this->jobOpeningId && $this->isJobOpeningAllowed($this->jobOpeningId),
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId)->where('status', 'interview'),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—'),

                Tables\Columns\TextColumn::make('interview_result')
                    ->label('Hasil')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'lulus'       => 'Lulus',
                        'tidak_lulus' => 'Tidak Lulus',
                        default       => 'Belum dicatat',
                    })
                    ->color(fn ($state) => match ($state) {
                        'lulus'       => 'success',
                        'tidak_lulus' => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.pages.applicant-detail-page') . '?application=' . $record->id),
                $this->tandaiHasilWawancaraAction()->visible(fn ($record) => $record->interview_result === null),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang sudah dijadwalkan wawancara akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}
