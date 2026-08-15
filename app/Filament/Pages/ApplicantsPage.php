<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\ScreeningDecision;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ApplicantsPage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Daftar Pelamar';
    protected static ?string $title           = 'Daftar Pelamar';
    protected static string  $view            = 'filament.pages.applicants-page';
    protected static ?int    $navigationSort  = 4;

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

    public function getSelectedJobOpening(): ?JobOpening
    {
        return $this->jobOpeningId ? JobOpening::with('company')->find($this->jobOpeningId) : null;
    }

    public function getScreeningCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'screening')->count();
    }

    public function getAppliedCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return JobApplication::where('job_opening_id', $this->jobOpeningId)
            ->where('status', 'applied')->count();
    }

    public function getDraftDecisionCount(): int
    {
        if (! $this->jobOpeningId) return 0;
        return ScreeningDecision::whereHas('jobApplication',
            fn ($q) => $q->where('job_opening_id', $this->jobOpeningId)
        )->count();
    }

    public function seleksiBerkasAction(): Action
    {
        $screeningApplicants = $this->jobOpeningId
            ? JobApplication::with(['answers' => fn ($q) => $q->where('field_key', 'full_name')])
                ->where('job_opening_id', $this->jobOpeningId)
                ->where('status', 'screening')
                ->get()
            : collect();

        // Pre-populate dari draft yang sudah ada
        $existingDrafts = $this->jobOpeningId
            ? ScreeningDecision::whereHas('jobApplication',
                fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->where('decision', 'passed')
                ->pluck('job_application_id')
                ->toArray()
            : [];

        $options = $screeningApplicants->mapWithKeys(fn ($app) => [
            $app->id => $app->answers->firstWhere('field_key', 'full_name')?->value ?? '(tanpa nama)',
        ])->all();

        $count = count($options);

        return Action::make('seleksiBerkas')
            ->label($count > 0 ? "Seleksi Berkas ({$count} pelamar)" : 'Seleksi Berkas')
            ->icon('heroicon-m-clipboard-document-check')
            ->color('warning')
            ->fillForm(['passed_ids' => $existingDrafts])
            ->form([
                CheckboxList::make('passed_ids')
                    ->label('Centang pelamar yang LOLOS seleksi berkas')
                    ->helperText('Yang tidak dicentang = Tidak Dilanjutkan. Keputusan disimpan sebagai draft — status pelamar belum berubah sampai kamu terapkan.')
                    ->options($options)
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1),
            ])
            ->modalHeading('Seleksi Berkas — Pilih yang Lolos')
            ->modalDescription("Total {$count} pelamar dalam tahap Peninjauan Berkas.")
            ->modalSubmitActionLabel('Simpan Draft Keputusan')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('lg')
            ->action(function (array $data): void {
                if (! $this->jobOpeningId) return;

                $passedIds     = $data['passed_ids'] ?? [];
                $allIds        = JobApplication::where('job_opening_id', $this->jobOpeningId)
                    ->where('status', 'screening')
                    ->pluck('id')
                    ->toArray();

                ScreeningDecision::saveDraftForOpening($this->jobOpeningId, $passedIds, $allIds);

                $passed   = count($passedIds);
                $rejected = count($allIds) - $passed;

                \Filament\Notifications\Notification::make()
                    ->title("Draft tersimpan: {$passed} lolos, {$rejected} tidak dilanjutkan. Belum diterapkan ke pelamar.")
                    ->warning()
                    ->send();
            });
    }

    public function publishScreeningAction(): Action
    {
        $draftCount = $this->getDraftDecisionCount();

        $decisions = $this->jobOpeningId
            ? ScreeningDecision::with(['jobApplication.answers'])
                ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->get()
            : collect();

        $passed        = $decisions->where('decision', 'passed');
        $rejectedCount = $decisions->where('decision', 'rejected')->count();

        $passedNames = $passed->map(fn ($d) =>
            $d->jobApplication->answers->firstWhere('field_key', 'full_name')?->value ?? '—'
        )->values();

        $passedHtml = $passedNames->map(fn ($name) =>
            '<li style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;">'
            . '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;"></span>'
            . e($name) . '</li>'
        )->join('');

        return Action::make('publishScreening')
            ->label("Terapkan Keputusan ({$draftCount})")
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->modalHeading('Terapkan Keputusan Seleksi Berkas?')
            ->modalDescription('Status lamaran akan berubah dan tidak bisa dibatalkan.')
            ->form([
                Placeholder::make('lolos')
                    ->label("Lolos Seleksi Berkas — {$passed->count()} orang")
                    ->content(new HtmlString(
                        '<ul style="border:1px solid #e5e7eb;border-radius:8px;max-height:200px;overflow-y:auto;margin:0;padding:0;list-style:none;">'
                        . ($passedHtml ?: '<li style="padding:8px 12px;font-size:13px;color:#9ca3af;">Tidak ada</li>')
                        . '</ul>'
                    )),
                Placeholder::make('ditolak')
                    ->label("Tidak Dilanjutkan — {$rejectedCount} orang")
                    ->content(new HtmlString(
                        '<p style="font-size:13px;color:#dc2626;">'
                        . "{$rejectedCount} pelamar akan ditandai Tidak Dilanjutkan."
                        . '</p>'
                    )),
            ])
            ->modalSubmitActionLabel('Terapkan Saja')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('lg')
            ->extraModalFooterActions([
                Action::make('applyAndNotify')
                    ->label('Terapkan & Beritahu yang Lolos')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->action(function (): void {
                        if (! $this->jobOpeningId) return;

                        [$passedCount, $rejectedCount] = ScreeningDecision::applyForOpening($this->jobOpeningId);

                        $this->sendPassedNotificationsForOpening($this->jobOpeningId);

                        $this->resetTable();

                        \Filament\Notifications\Notification::make()
                            ->title("{$passedCount} lolos (email terkirim), {$rejectedCount} tidak dilanjutkan (email ditahan).")
                            ->success()
                            ->send();
                    }),
            ])
            ->action(function (): void {
                if (! $this->jobOpeningId) return;

                [$passedCount, $rejectedCount] = ScreeningDecision::applyForOpening($this->jobOpeningId);

                $this->resetTable();

                \Filament\Notifications\Notification::make()
                    ->title("{$passedCount} lolos, {$rejectedCount} tidak dilanjutkan. Draft email siap di halaman Notifikasi.")
                    ->success()
                    ->send();
            });
    }

    private function sendPassedNotificationsForOpening(string $jobOpeningId): void
    {
        $drafts = ApplicationNotification::with(['jobApplication.user'])
            ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $jobOpeningId))
            ->where('status', 'draft')
            ->where('event', \App\Models\CompanyMessageTemplate::EVENT_SCREENING_PASSED)
            ->get();

        foreach ($drafts as $notif) {
            $email = $notif->jobApplication->user->email;
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\ApplicantNotificationMail($notif));
            $notif->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }

    public function kirimNotifPenolakanBerkasAction(): Action
    {
        $pendingCount = $this->jobOpeningId
            ? ApplicationNotification::whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                ->where('status', 'draft')
                ->where('event', \App\Models\CompanyMessageTemplate::EVENT_SCREENING_REJECTED)
                ->count()
            : 0;

        return Action::make('kirimNotifPenolakanBerkas')
            ->label("Kirim Notif Penolakan ({$pendingCount})")
            ->icon('heroicon-m-envelope')
            ->color('danger')
            ->visible(fn () => $pendingCount > 0)
            ->requiresConfirmation()
            ->modalHeading('Kirim Pemberitahuan Penolakan Seleksi Berkas?')
            ->modalDescription("Email penolakan akan dikirim ke {$pendingCount} pelamar yang tidak lolos seleksi berkas. Tindakan ini tidak bisa dibatalkan.")
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->action(function (): void {
                if (! $this->jobOpeningId) return;

                $drafts = ApplicationNotification::with(['jobApplication.user'])
                    ->whereHas('jobApplication', fn ($q) => $q->where('job_opening_id', $this->jobOpeningId))
                    ->where('status', 'draft')
                    ->where('event', \App\Models\CompanyMessageTemplate::EVENT_SCREENING_REJECTED)
                    ->get();

                $count = 0;
                foreach ($drafts as $notif) {
                    $email = $notif->jobApplication->user->email;
                    \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\ApplicantNotificationMail($notif));
                    $notif->update(['status' => 'sent', 'sent_at' => now()]);
                    $count++;
                }

                \Filament\Notifications\Notification::make()
                    ->title("Email penolakan terkirim ke {$count} pelamar.")
                    ->success()->send();
            });
    }

    public function bulkMoveToScreeningAction(): Action
    {
        $count = $this->getAppliedCount();

        return Action::make('bulkMoveToScreening')
            ->label("Mulai Peninjauan Berkas ({$count})")
            ->icon('heroicon-m-arrow-right')
            ->color('info')
            ->modalHeading('Mulai Peninjauan Berkas')
            ->modalDescription("Kamu akan memindahkan {$count} lamaran dari status Lamaran Masuk ke Peninjauan Berkas. Setelah ini, tinjau berkas tiap pelamar satu per satu.")
            ->modalIcon('heroicon-o-magnifying-glass')
            ->modalIconColor('info')
            ->modalSubmitActionLabel("Ya, Pindahkan {$count} Lamaran")
            ->modalCancelActionLabel('Batal')
            ->modalWidth('md')
            ->action(function () {
                if (! $this->jobOpeningId) return;

                $count = JobApplication::where('job_opening_id', $this->jobOpeningId)
                    ->where('status', 'applied')
                    ->update(['status' => 'screening']);

                $this->resetTable();

                \Filament\Notifications\Notification::make()
                    ->title("{$count} lamaran dipindahkan ke Peninjauan Berkas")
                    ->success()
                    ->send();
            });
    }

    public function getStats(): array
    {
        if (! $this->jobOpeningId) return [];

        $base = fn () => JobApplication::where('job_opening_id', $this->jobOpeningId);

        return [
            ['label' => 'Total Pelamar',    'value' => $base()->count(),                                'color' => '#042c6c', 'icon' => 'heroicon-o-users'],
            ['label' => 'Lamaran Masuk',    'value' => $base()->where('status', 'applied')->count(),    'color' => '#64748b', 'icon' => 'heroicon-o-inbox'],
            ['label' => 'Screening',        'value' => $base()->where('status', 'screening')->count(),  'color' => '#0284c7', 'icon' => 'heroicon-o-magnifying-glass'],
            ['label' => 'Lolos Berkas',     'value' => $base()->where('status', 'shortlisted')->count(),'color' => '#047C54', 'icon' => 'heroicon-o-check-badge'],
            ['label' => 'Tidak Dilanjutkan','value' => $base()->where('status', 'rejected')->count(),   'color' => '#dc2626', 'icon' => 'heroicon-o-x-circle'],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->whereIn('field_key', ['full_name', 'gender', 'whatsapp_number', 'education_level'])])
                    ->when(
                        $this->jobOpeningId && $this->isJobOpeningAllowed($this->jobOpeningId),
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->when($this->statusFilter, fn (Builder $q) => $q->where('status', $this->statusFilter))
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
                        'applied'     => 'gray',
                        'screening'   => 'info',
                        'shortlisted' => 'warning',
                        'interview'   => 'warning',
                        'passed'      => 'success',
                        'rejected'    => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'applied'     => 'Lamaran Masuk',
                        'screening'   => 'Peninjauan Berkas',
                        'shortlisted' => 'Lolos Seleksi Berkas',
                        'interview'   => 'Wawancara',
                        'passed'      => 'Diterima',
                        'rejected'    => 'Tidak Dilanjutkan',
                        default       => $state,
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
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Pilih perusahaan lalu lowongan di atas untuk melihat daftar pelamar.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
