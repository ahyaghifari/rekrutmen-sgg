<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Mail\ApplicantNotificationMail;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class TesOnlinePage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Tes Online';
    protected static ?string $title           = 'Tes Online';
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

    /** Aktifkan tes untuk semua kandidat lolos berkas yang belum diaktifkan, langsung kirim email. */
    private function activateAndNotify(JobApplication $app): void
    {
        $app->update(['quiz_sent_at' => now()]);
        ApplicationNotification::createDraftFor($app, CompanyMessageTemplate::EVENT_QUIZ_SENT);

        $notif = ApplicationNotification::where('job_application_id', $app->id)
            ->where('event', CompanyMessageTemplate::EVENT_QUIZ_SENT)
            ->where('status', 'draft')
            ->latest()->first();

        if ($notif && $app->user) {
            Mail::to($app->user->email)->send(new ApplicantNotificationMail($notif));
            $notif->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }

    public function aktifkanTesAction(): Action
    {
        $count = $this->getBelumDiaktifkanCount();

        return Action::make('aktifkanTes')
            ->label("Aktifkan Tes untuk Semua ({$count} kandidat)")
            ->icon('heroicon-m-play')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Aktifkan Tes Online')
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
                } else {
                    $record->update(['quiz_result' => 'tidak_lulus', 'status' => 'rejected']);
                }

                \Filament\Notifications\Notification::make()->title('Keputusan tersimpan')->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'quizAttempt'])
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

                Tables\Columns\TextColumn::make('attempt_status')
                    ->label('Status Pengerjaan')
                    ->getStateUsing(fn ($record) => match ($record->quizAttempt?->status) {
                        'submitted'   => 'Selesai',
                        'in_progress' => 'Sedang mengerjakan',
                        'expired'     => 'Waktu habis',
                        default       => 'Belum mulai',
                    })
                    ->badge()
                    ->color(fn ($record) => match ($record->quizAttempt?->status) {
                        'submitted'   => 'success',
                        'in_progress' => 'warning',
                        'expired'     => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Skor')
                    ->getStateUsing(fn ($record) => $record->quizAttempt?->score !== null ? $record->quizAttempt->score . '%' : '—'),

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
                $this->tandaiHasilTesAction()->visible(fn ($record) => $record->quiz_sent_at !== null && $record->quiz_result === null),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang lolos seleksi berkas untuk lowongan ini akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }
}
