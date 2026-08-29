<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScopedToCompany;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\McuDoctorExamination;
use App\Models\McuLabResult;
use App\Models\McuNurseAssessment;
use App\Models\McuRadiologyResult;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class McuStationPage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms, ScopedToCompany;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Isi Pemeriksaan MCU';
    protected static ?string $title           = 'Isi Pemeriksaan MCU';
    protected static ?string $navigationGroup = 'Kandidat';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.mcu-station-page';

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
        $this->resetTable();
    }

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

    private const LAB_FIELDS = [
        'hemoglobin', 'leukosit', 'eritrosit', 'trombosit', 'hematokrit',
        'basofil', 'eosinofil', 'stab', 'segmen', 'limfosit', 'monosit',
        'mcv', 'mch', 'mchc', 'gds', 'gda',
    ];

    private const DOCTOR_FIELDS = [
        'kulit', 'rambut', 'visus_mata', 'buta_warna', 'lapang_pandang',
        'hidung', 'telinga', 'tonsil', 'faring', 'gigi', 'kelenjar_gondok',
        'suara_pernafasan', 'ronchi', 'wheezing', 'lainnya_paru', 'jantung',
        'hati', 'limpa', 'epigastrium', 'ginjal', 'lainnya_abdomen',
        'ekstremitas', 'neurologi',
    ];

    public function isiPerawatAction(): Action
    {
        return Action::make('isiPerawat')
            ->label('Isi Perawat')
            ->icon('heroicon-m-heart')
            ->color('gray')
            ->visible(fn () => auth()->user()->can('fill_mcu_nurse'))
            ->fillForm(fn ($record) => $record->mcuInvitee?->nurseAssessment?->toArray() ?? [])
            ->form([
                Section::make('Vital Sign')->columns(3)->schema([
                    TextInput::make('tinggi_badan')->label('Tinggi Badan (cm)')->numeric()->required(),
                    TextInput::make('berat_badan')->label('Berat Badan (kg)')->numeric()->required(),
                    TextInput::make('tekanan_darah')->label('Tekanan Darah')->required(),
                    TextInput::make('nadi')->label('Nadi')->numeric()->required(),
                    TextInput::make('suhu')->label('Suhu (°C)')->numeric()->required(),
                    TextInput::make('pernafasan')->label('Pernafasan')->numeric()->required(),
                ]),
                Textarea::make('keluhan_kesehatan')->label('Keluhan Kesehatan')->rows(2),
                Section::make('Riwayat Penyakit Keluarga')->columns(2)->schema([
                    Toggle::make('rpk_hipertensi')->label('Hipertensi'),
                    Toggle::make('rpk_stroke')->label('Stroke'),
                    Toggle::make('rpk_jantung')->label('Jantung'),
                    Toggle::make('rpk_diabetes')->label('Diabetes'),
                    TextInput::make('rpk_lainnya')->label('Lainnya')->columnSpanFull(),
                ]),
                Textarea::make('riwayat_penyakit_dahulu')->label('Riwayat Penyakit Dahulu')->rows(2),
                Textarea::make('riwayat_operasi')->label('Riwayat Operasi')->rows(2),
                Section::make('Alergi')->columns(2)->schema([
                    Toggle::make('alergi_makanan')->label('Makanan'),
                    Toggle::make('alergi_obat')->label('Obat'),
                    Toggle::make('alergi_debu')->label('Debu'),
                    TextInput::make('alergi_lainnya')->label('Lainnya')->columnSpanFull(),
                ]),
                Section::make('Kebiasaan')->columns(2)->schema([
                    Toggle::make('kebiasaan_olahraga')->label('Olahraga'),
                    Toggle::make('kebiasaan_merokok')->label('Merokok'),
                    Toggle::make('kebiasaan_alkohol')->label('Alkohol'),
                    Toggle::make('kebiasaan_obat')->label('Obat-obatan'),
                    TextInput::make('kebiasaan_lainnya')->label('Lainnya')->columnSpanFull(),
                ]),
                Section::make('EKG')->columns(2)->schema([
                    TextInput::make('hasil_ekg')->label('Hasil EKG'),
                    Textarea::make('keterangan_ekg')->label('Keterangan EKG')->columnSpanFull(),
                    FileUpload::make('file_ekg')->label('File EKG')->disk('public')->directory('mcu/ekg'),
                ]),
                Textarea::make('catatan')->label('Catatan')->rows(2),
            ])
            ->modalWidth('3xl')
            ->action(function (array $data, $record): void {
                $record->mcuInvitee->nurseAssessment()->updateOrCreate(
                    ['mcu_invitee_id' => $record->mcuInvitee->id],
                    $data
                );
                \Filament\Notifications\Notification::make()->title('Asesmen perawat tersimpan')->success()->send();
            });
    }

    public function isiLabAction(): Action
    {
        $fields = collect(self::LAB_FIELDS)->map(fn ($f) => TextInput::make($f)->label(ucfirst($f)))->all();

        return Action::make('isiLab')
            ->label('Isi Lab')
            ->icon('heroicon-m-beaker')
            ->color('gray')
            ->visible(fn () => auth()->user()->can('fill_mcu_lab'))
            ->fillForm(fn ($record) => $record->mcuInvitee?->labResult?->toArray() ?? [])
            ->form([
                Section::make('Hasil Laboratorium')->columns(4)->schema($fields),
                Section::make()->columns(2)->schema([
                    TextInput::make('hbsag')->label('HBsAg'),
                    TextInput::make('hiv')->label('HIV'),
                ]),
                Textarea::make('kesimpulan')->label('Kesimpulan')->rows(2),
                FileUpload::make('file_path')->label('File Hasil Lab')->disk('public')->directory('mcu/lab'),
            ])
            ->modalWidth('3xl')
            ->action(function (array $data, $record): void {
                $record->mcuInvitee->labResult()->updateOrCreate(
                    ['mcu_invitee_id' => $record->mcuInvitee->id],
                    $data
                );
                \Filament\Notifications\Notification::make()->title('Hasil lab tersimpan')->success()->send();
            });
    }

    public function isiRadiologiAction(): Action
    {
        return Action::make('isiRadiologi')
            ->label('Isi Radiologi')
            ->icon('heroicon-m-camera')
            ->color('gray')
            ->visible(fn () => auth()->user()->can('fill_mcu_radiology'))
            ->fillForm(fn ($record) => $record->mcuInvitee?->radiologyResult?->toArray() ?? [])
            ->form([
                Section::make()->columns(2)->schema([
                    TextInput::make('jenis_pemeriksaan')->label('Jenis Pemeriksaan'),
                    TextInput::make('foto_thorax')->label('Foto Thorax'),
                    TextInput::make('jantung')->label('Jantung'),
                    TextInput::make('paru_kanan')->label('Paru Kanan'),
                    TextInput::make('paru_kiri')->label('Paru Kiri'),
                    TextInput::make('diafragma')->label('Diafragma'),
                    TextInput::make('tulang')->label('Tulang'),
                ]),
                Textarea::make('kesimpulan')->label('Kesimpulan')->rows(2),
                FileUpload::make('file_path')->label('File Hasil Radiologi')->disk('public')->directory('mcu/radiologi'),
            ])
            ->modalWidth('2xl')
            ->action(function (array $data, $record): void {
                $record->mcuInvitee->radiologyResult()->updateOrCreate(
                    ['mcu_invitee_id' => $record->mcuInvitee->id],
                    $data
                );
                \Filament\Notifications\Notification::make()->title('Hasil radiologi tersimpan')->success()->send();
            });
    }

    public function isiDokterAction(): Action
    {
        $fields = collect(self::DOCTOR_FIELDS)->map(fn ($f) => TextInput::make($f)->label(ucfirst(str_replace('_', ' ', $f))))->all();

        return Action::make('isiDokter')
            ->label('Isi Dokter')
            ->icon('heroicon-m-user-circle')
            ->color('gray')
            ->visible(fn () => auth()->user()->can('fill_mcu_doctor'))
            ->fillForm(fn ($record) => $record->mcuInvitee?->doctorExamination?->toArray() ?? [])
            ->form([
                Section::make('Pemeriksaan Fisik')->columns(3)->schema($fields),
                Textarea::make('diagnosa')->label('Diagnosa')->rows(2),
                Textarea::make('rekomendasi')->label('Rekomendasi/Saran Dokter')->rows(2)
                    ->helperText('Saran tertulis dokter — keputusan final fit/unfit tetap diputuskan HRD di halaman MCU.'),
                Textarea::make('catatan')->label('Catatan')->rows(2),
            ])
            ->modalWidth('4xl')
            ->action(function (array $data, $record): void {
                $record->mcuInvitee->doctorExamination()->updateOrCreate(
                    ['mcu_invitee_id' => $record->mcuInvitee->id],
                    $data
                );
                \Filament\Notifications\Notification::make()->title('Pemeriksaan dokter tersimpan')->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->with(['answers' => fn ($q) => $q->where('field_key', 'full_name'), 'mcuInvitee.nurseAssessment', 'mcuInvitee.labResult', 'mcuInvitee.radiologyResult', 'mcuInvitee.doctorExamination'])
                    ->when(
                        $this->jobOpeningId,
                        fn (Builder $q) => $q->where('job_opening_id', $this->jobOpeningId)->where('status', 'mcu')->whereHas('mcuInvitee'),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->getStateUsing(fn ($record) => $record->answers->firstWhere('field_key', 'full_name')?->value ?? '—'),

                Tables\Columns\IconColumn::make('nurse_done')
                    ->label('Perawat')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->nurseAssessment)
                    ->boolean(),

                Tables\Columns\IconColumn::make('lab_done')
                    ->label('Lab')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->labResult)
                    ->boolean(),

                Tables\Columns\IconColumn::make('radiology_done')
                    ->label('Radiologi')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->radiologyResult)
                    ->boolean(),

                Tables\Columns\IconColumn::make('doctor_done')
                    ->label('Dokter')
                    ->getStateUsing(fn ($record) => (bool) $record->mcuInvitee?->doctorExamination)
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    $this->isiPerawatAction(),
                    $this->isiLabAction(),
                    $this->isiRadiologiAction(),
                    $this->isiDokterAction(),
                ])->label('Isi Pemeriksaan')->icon('heroicon-m-pencil-square'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pilih perusahaan & lowongan')
            ->emptyStateDescription('Kandidat yang sudah diundang MCU akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
