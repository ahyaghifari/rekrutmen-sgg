<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Quiz;
use App\Mail\ApplicantNotificationMail;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class TesOnlinePage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Tes';
    protected static ?string $title           = 'Tes';
    protected static ?string $navigationGroup = 'Kandidat';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.tes-online-page';

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

    public function getSelectedJobOpening(): ?JobOpening
    {
        return $this->jobOpeningId ? JobOpening::find($this->jobOpeningId) : null;
    }

    public function getBelumDiaktifkanCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'shortlisted')->whereNull('quiz_sent_at')->count();
    }

    /** Lowongan harus punya minimal 1 kuis terpasang sebelum tesnya bisa diaktifkan. */
    public function hasQuizAssigned(): bool
    {
        return (bool) $this->getSelectedJobOpening()?->quizzes()->exists();
    }

    /** Kuis dikunci begitu tes sudah diaktifkan ke minimal 1 kandidat — supaya set soal tidak berubah di tengah jalan. */
    public function isQuizLocked(): bool
    {
        if (! $this->jobOpeningId) return false;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)->whereNotNull('quiz_sent_at')->exists();
    }

    public function pilihKuisAction(): Action
    {
        return Action::make('pilihKuis')
            ->label($this->hasQuizAssigned() ? 'Ubah Kuis' : 'Pilih Kuis')
            ->icon('heroicon-m-clipboard-document-list')
            ->color('gray')
            ->visible(fn () => (bool) $this->jobOpeningId)
            ->disabled(fn () => $this->isQuizLocked())
            ->tooltip(fn () => $this->isQuizLocked() ? 'Tes sudah diaktifkan untuk lowongan ini, kuis tidak bisa diubah lagi.' : null)
            ->form([
                Select::make('quiz_ids')
                    ->label('Kuis')
                    ->options(fn () => Quiz::orderBy('name')->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Pilih satu atau lebih kuis untuk lowongan ini. Kelola bank soal di menu Soal Kuis.'),
            ])
            ->fillForm(fn () => ['quiz_ids' => $this->getSelectedJobOpening()?->quizzes->pluck('id')->all() ?? []])
            ->modalHeading('Pilih Kuis untuk Lowongan Ini')
            ->modalSubmitActionLabel('Simpan')
            ->modalCancelActionLabel('Batal')
            ->action(function (array $data): void {
                $opening = $this->getSelectedJobOpening();
                if (! $opening) return;

                $opening->quizzes()->sync($data['quiz_ids'] ?? []);

                \Filament\Notifications\Notification::make()->title('Kuis lowongan tersimpan')->success()->send();
            });
    }

    /** Aktifkan tes untuk semua kandidat lolos berkas yang belum diaktifkan, langsung kirim email. */
    private function activateAndNotify(JobApplication $app): void
    {
        $app->update(['quiz_sent_at' => now()]);
        ApplicationNotification::createAndSend($app, CompanyMessageTemplate::EVENT_QUIZ_SENT);
    }

    public function aktifkanTesAction(): Action
    {
        $count = $this->getBelumDiaktifkanCount();

        return Action::make('aktifkanTes')
            ->label("Aktifkan Tes untuk Semua ({$count} kandidat)")
            ->icon('heroicon-m-play')
            ->color('info')
            ->visible(fn () => $this->hasQuizAssigned())
            ->requiresConfirmation()
            ->modalHeading('Aktifkan Tes')
            ->modalDescription("Tes akan langsung diaktifkan untuk {$count} kandidat lolos seleksi berkas yang belum diaktifkan, dan email pemberitahuan langsung terkirim ke masing-masing kandidat.")
            ->modalSubmitActionLabel('Aktifkan & Kirim Email')
            ->modalCancelActionLabel('Batal')
            ->action(function (): void {
                if (! $this->jobOpeningId) return;

                $apps = JobApplication::where('job_opening_id', $this->jobOpeningId)
                    ->where('status', 'shortlisted')->whereNull('quiz_sent_at')
                    ->with(['user', 'jobOpening'])
                    ->get();

                foreach ($apps as $app) {
                    $this->activateAndNotify($app);
                }

                $this->resetTable();
                \Filament\Notifications\Notification::make()
                    ->title(count($apps) . ' kandidat diaktifkan tesnya. Email terkirim.')
                    ->success()->send();
            });
    }

    public function getLulusTesCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'shortlisted')->where('quiz_result', 'lulus')->count();
    }

    public function getBelumDiputuskanCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'shortlisted')->whereNotNull('quiz_sent_at')->whereNull('quiz_result')->count();
    }

    public function tandaiTidakLulusTesAction(): Action
    {
        $pending = $this->jobOpeningId
            ? JobApplication::with(['answers' => fn ($q) => $q->where('field_key', 'full_name')])
                ->where('job_opening_id', $this->jobOpeningId)
                ->where('status', 'shortlisted')->whereNotNull('quiz_sent_at')->whereNull('quiz_result')
                ->get()
            : collect();

        $options = $pending->mapWithKeys(fn ($app) => [
            $app->id => $app->answers->firstWhere('field_key', 'full_name')?->value ?? '(tanpa nama)',
        ])->all();

        $count = count($options);

        return Action::make('tandaiTidakLulusTes')
            ->label("Tandai & Beritahu Tidak Lolos ({$count})")
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->form([
                CheckboxList::make('selected_ids')
                    ->label('Pilih kandidat yang TIDAK LOLOS Tes')
                    ->helperText('Kandidat dipilih akan diubah statusnya ke Tidak Dilanjutkan dan langsung dikirim email.')
                    ->options($options)->searchable()->bulkToggleable()->columns(1),
            ])
            ->modalHeading('Tandai Tidak Lolos Tes & Kirim Pemberitahuan')
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
                    ->where('status', 'shortlisted')->get();

                $count = 0;
                foreach ($apps as $app) {
                    $app->update(['quiz_result' => 'tidak_lulus', 'status' => 'rejected']);
                    $notif = ApplicationNotification::createDraftFor($app, CompanyMessageTemplate::EVENT_QUIZ_REJECTED);
                    if ($notif && $app->user) {
                        Mail::to($app->user->email)->send(new ApplicantNotificationMail($notif));
                        $notif->update(['status' => 'sent', 'sent_at' => now()]);
                    }
                    $count++;
                }

                \App\Models\JobOpeningStageLog::record($this->jobOpeningId, 'shortlisted', 'rejected', $count);
                $this->resetTable();

                \Filament\Notifications\Notification::make()
                    ->title("{$count} kandidat ditandai tidak lolos tes dan email terkirim.")
                    ->success()->send();
            });
    }

    public function kirimNotifPenolakanTesAction(): Action
    {
        $pendingCount = $this->jobOpeningId
            ? ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->where('status', 'draft')->where('event', CompanyMessageTemplate::EVENT_QUIZ_REJECTED)->count()
            : 0;

        return Action::make('kirimNotifPenolakanTes')
            ->label("Kirim Notif Tidak Dilanjutkan ({$pendingCount})")
            ->icon('heroicon-m-envelope')
            ->color('danger')
            ->visible(fn () => $pendingCount > 0)
            ->requiresConfirmation()
            ->modalHeading('Kirim Pemberitahuan Tidak Lolos Tes?')
            ->modalDescription("Email akan dikirim ke {$pendingCount} kandidat. Tidak bisa dibatalkan.")
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->action(function (): void {
                if (! $this->jobOpeningId) return;
                $drafts = ApplicationNotification::with(['jobApplication.user'])
                    ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                    ->where('status', 'draft')->where('event', CompanyMessageTemplate::EVENT_QUIZ_REJECTED)->get();

                $count = 0;
                foreach ($drafts as $notif) {
                    Mail::to($notif->jobApplication->user->email)->send(new ApplicantNotificationMail($notif));
                    $notif->update(['status' => 'sent', 'sent_at' => now()]);
                    $count++;
                }
                \Filament\Notifications\Notification::make()->title("Email terkirim ke {$count} kandidat.")->success()->send();
            });
    }

    public function tandaiHasilTesAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('tandaiHasilTes')
            ->label('Catat Keputusan')
            ->icon('heroicon-m-clipboard-document-check')
            ->color('gray')
            ->form([
                Radio::make('hasil')->label('Keputusan')->options([
                    'lulus'       => 'Lulus',
                    'tidak_lulus' => 'Tidak Lulus',
                ])->required(),
            ])
            ->action(function (array $data, $record): void {
                if ($data['hasil'] === 'lulus') {
                    $record->update(['quiz_result' => 'lulus']);
                    $record->logMilestone('quiz_lulus');
                    ApplicationNotification::createAndSend($record, CompanyMessageTemplate::EVENT_QUIZ_PASSED);
                } else {
                    $record->update(['quiz_result' => 'tidak_lulus', 'status' => 'rejected']);
                    ApplicationNotification::createDraftFor($record, CompanyMessageTemplate::EVENT_QUIZ_REJECTED);
                }

                \Filament\Notifications\Notification::make()->title('Keputusan tersimpan')->success()->send();
            });
    }

    public function lihatHasilAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('lihatHasil')
            ->label('Lihat Hasil')
            ->icon('heroicon-m-document-magnifying-glass')
            ->color('gray')
            ->visible(fn ($record) => $record->quizAttempts->where('status', 'submitted')->isNotEmpty())
            ->modalHeading('Hasil Tes')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->form(function ($record) {
                $attempts = $record->quizAttempts()->where('status', 'submitted')->with(['quiz', 'answers'])->get();

                return $attempts->map(function ($attempt) {
                    $correct = $attempt->answers->where('is_correct', true)->count();
                    $total   = $attempt->answers->count();
                    $duration = $attempt->durationLabel();

                    return Placeholder::make('attempt_' . $attempt->id)
                        ->label($attempt->quiz->name)
                        ->content(new HtmlString(
                            '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">'
                            . '<span>' . $correct . ' dari ' . $total . ' benar'
                            . ($duration ? ' &middot; dikerjakan ' . $duration : '')
                            . ' &middot; dikumpulkan ' . $attempt->submitted_at->translatedFormat('d M Y, H:i') . '</span>'
                            . '<a href="' . route('filament.admin.pages.hasil-tes-detail-page', ['attempt' => $attempt->id]) . '" style="font-weight:700;">Lihat Detail &rarr;</a>'
                            . '</div>'
                        ));
                })->all();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'quizAttempts'])
                    ->when(
                        $this->jobOpeningId && $this->isJobOpeningAllowed($this->jobOpeningId),
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId)->where('status', 'shortlisted'),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—'),

                Tables\Columns\TextColumn::make('quiz_sent_at')->label('Diaktifkan')->dateTime('d M Y, H:i')->placeholder('Belum diaktifkan'),

                Tables\Columns\TextColumn::make('attempt_progress')
                    ->label('Progres Tes')
                    ->getStateUsing(function ($record) {
                        $total = $this->getSelectedJobOpening()?->quizzes->count() ?? 0;
                        $done  = $record->quizAttempts->where('status', 'submitted')->count();

                        return "{$done}/{$total} selesai";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $total = $this->getSelectedJobOpening()?->quizzes->count() ?? 0;
                        $done  = $record->quizAttempts->where('status', 'submitted')->count();

                        return match (true) {
                            $total > 0 && $done === $total => 'success',
                            $done > 0                      => 'warning',
                            default                         => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Skor Rata-rata')
                    ->getStateUsing(function ($record) {
                        $scores = $record->quizAttempts->where('status', 'submitted')->pluck('score');

                        return $scores->isEmpty() ? '—' : round($scores->avg(), 2) . '%';
                    }),

                Tables\Columns\TextColumn::make('quiz_result')
                    ->label('Keputusan')
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
            ])
            ->actions([
                $this->lihatHasilAction(),
                $this->tandaiHasilTesAction()->visible(fn ($record) => $record->quiz_sent_at !== null && $record->quiz_result === null),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang lolos seleksi berkas untuk lowongan ini akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }
}
