<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobOpeningResource\Pages;
use App\Models\Company;
use App\Models\JobOpening;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class JobOpeningResource extends BaseResource
{
    protected static ?string $model = JobOpening::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Lowongan';
    protected static ?string $modelLabel = 'Lowongan';
    protected static ?string $pluralModelLabel = 'Lowongan';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('company');

        if (static::isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', static::companyId());
    }

    public static function canCreate(): bool
    {
        // Perusahaan mode 'hris' hanya bisa ditambah lowongannya dari HRIS.
        if (! static::isSuperAdmin()) {
            $co = Company::find(static::companyId());

            return $co && $co->management_mode === 'free';
        }

        return Company::where('management_mode', 'free')->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Lowongan')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Perusahaan')
                    // Hanya perusahaan mode 'free' — yang mode 'hris' dikelola di HRIS.
                    ->options(fn () => Company::query()
                        ->where('management_mode', 'free')
                        ->when(! static::isSuperAdmin(), fn ($q) => $q->where('id', static::companyId()))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->default(fn () => static::isSuperAdmin() ? null : static::companyId())
                    ->disabled(fn () => !static::isSuperAdmin())
                    ->dehydrated()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                        $set('company_name', Company::find($state)?->name)
                    ),

                Forms\Components\TextInput::make('company_name')
                    ->label('Nama Perusahaan (manual)')
                    ->helperText('Otomatis terisi dari perusahaan di atas, atau isi manual.')
                    ->nullable(),

                Forms\Components\TextInput::make('title')
                    ->label('Posisi / Jabatan')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('quota')
                    ->label('Kuota Posisi')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Forms\Components\TextInput::make('max_applicants')
                    ->label('Maks. Pelamar')
                    ->numeric()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Batas jumlah lamaran yang diterima. Kosongkan = tidak terbatas.'),

                Forms\Components\DatePicker::make('applications_deadline')
                    ->label('Batas Lamaran')
                    ->nullable()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->helperText('Lamaran tidak diterima setelah tanggal ini.'),

                Forms\Components\TextInput::make('hris_job_opening_id')
                    ->label('ID Lowongan HRIS')
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Terisi otomatis dari HRIS. Jangan ubah manual.')
                    ->disabled(fn ($record) => $record?->hris_job_opening_id !== null),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Tes')->schema([
                Forms\Components\Select::make('quizzes')
                    ->label('Kuis')
                    ->relationship('quizzes', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih satu atau lebih kuis yang dipakai untuk lowongan ini. Kelola bank soal di menu Kuis.')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('requires_psychotest')
                    ->label('Wajib Psikotes Offline')
                    ->helperText('Tandai kalau posisi ini juga mewajibkan psikotes offline tambahan (di luar Tes sistem).'),
            ])->columns(2),

            Forms\Components\Section::make('Detail')->schema([
                Forms\Components\RichEditor::make('description')
                    ->label('Deskripsi Jabatan')
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('requirements')
                    ->label('Kualifikasi & Persyaratan')
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Persetujuan Khusus Jabatan')
                ->description('Persetujuan yang khusus untuk jabatan ini (mis. jadwal absensi). Kalau judulnya SAMA PERSIS dengan salah satu persetujuan perusahaan, versi jabatan ini yang dipakai (menimpa). Judul baru = persetujuan tambahan.')
                ->schema([
                    Forms\Components\Repeater::make('agreements')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Judul')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\RichEditor::make('content')
                                ->label('Naskah')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Persetujuan baru')
                        ->addActionLabel('Tambah persetujuan')
                        ->defaultItems(0),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_display')
                    ->label('Perusahaan')
                    ->getStateUsing(fn ($record) => $record->company?->name ?? $record->company_name)
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('company', fn ($q) => $q->where('name', 'like', "%$search%"))
                              ->orWhere('company_name', 'like', "%$search%")
                    )
                    ->visible(fn () => static::isSuperAdmin()),

                Tables\Columns\TextColumn::make('title')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stage')
                    ->label('Tahap')
                    ->getStateUsing(fn ($record) => $record->currentStage()['label'])
                    ->badge()
                    ->color(fn ($record) => $record->currentStage()['color']),

                Tables\Columns\TextColumn::make('quota')
                    ->label('Kuota')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_applicants')
                    ->label('Maks. Pelamar')
                    ->default('∞')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('applications_deadline')
                    ->label('Batas Lamaran')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('hris_job_opening_id')
                    ->label('HRIS')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hris_job_opening_id !== null)
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Terakhir diubah oleh')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Status'),

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->options(fn () => Company::orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => static::isSuperAdmin()),
            ])
            ->actions([
                Tables\Actions\Action::make('stageLogs')
                    ->label('Riwayat')
                    ->icon('heroicon-m-chart-bar')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Riwayat Proses — ' . $record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function ($record): HtmlString {
                        $logs = $record->stageLogs()->with('actor')->get();

                        $labelMap = [
                            'applied'     => 'Lamaran Masuk',
                            'screening'   => 'Peninjauan Berkas',
                            'shortlisted' => 'Lolos Seleksi Berkas',
                            'interview'   => 'Wawancara',
                            'offering'    => 'Offering Letter',
                            'mcu'         => 'MCU',
                            'onboarding'  => 'TTD & Onboarding',
                            'passed'      => 'Diterima',
                            'rejected'    => 'Tidak Dilanjutkan',
                        ];

                        $colorMap = [
                            'shortlisted' => '#047C54',
                            'interview'   => '#0284c7',
                            'mcu'         => '#7c3aed',
                            'onboarding'  => '#d97706',
                            'passed'      => '#16a34a',
                            'rejected'    => '#dc2626',
                            'screening'   => '#64748b',
                            'applied'     => '#94a3b8',
                        ];

                        if ($logs->isEmpty()) {
                            return new HtmlString('<p style="color:#94a3b8;font-size:13px;padding:16px 0;">Belum ada riwayat proses untuk lowongan ini.</p>');
                        }

                        $rows = $logs->map(function ($log) use ($labelMap, $colorMap) {
                            $from  = $log->from_status ? ($labelMap[$log->from_status] ?? $log->from_status) : '—';
                            $to    = $labelMap[$log->to_status] ?? $log->to_status;
                            $color = $colorMap[$log->to_status] ?? '#64748b';
                            $actor = $log->actor?->name ?? 'Sistem';
                            $date  = $log->done_at->translatedFormat('d M Y, H:i');
                            return "<tr style=\"border-bottom:1px solid #f1f5f9;\">
                                <td style=\"padding:10px 12px;font-size:12px;color:#64748b;white-space:nowrap;\">{$date}</td>
                                <td style=\"padding:10px 12px;font-size:12px;color:#334155;\">{$from}</td>
                                <td style=\"padding:10px 12px;\"><span style=\"background:{$color}20;color:{$color};font-weight:700;font-size:11px;padding:3px 10px;border-radius:999px;\">{$to}</span></td>
                                <td style=\"padding:10px 12px;font-size:13px;font-weight:700;color:#1e293b;text-align:center;\">{$log->count}</td>
                                <td style=\"padding:10px 12px;font-size:12px;color:#64748b;\">{$actor}</td>
                            </tr>";
                        })->join('');

                        return new HtmlString("
                            <div style=\"overflow-x:auto;\">
                            <table style=\"width:100%;border-collapse:collapse;\">
                                <thead>
                                    <tr style=\"background:#f8fafc;border-bottom:2px solid #e2e8f0;\">
                                        <th style=\"padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;\">Waktu</th>
                                        <th style=\"padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;\">Dari</th>
                                        <th style=\"padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;\">Ke Tahap</th>
                                        <th style=\"padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:center;\">Jumlah</th>
                                        <th style=\"padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;\">Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>{$rows}</tbody>
                            </table>
                            </div>
                        ");
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->hris_job_opening_id === null && $record->company?->management_mode !== 'hris'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->hris_job_opening_id === null && $record->company?->management_mode !== 'hris'),
                Tables\Actions\ViewAction::make()
                    ->visible(fn ($record) => $record->hris_job_opening_id !== null || $record->company?->management_mode === 'hris'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobOpenings::route('/'),
            'create' => Pages\CreateJobOpening::route('/create'),
            'edit'   => Pages\EditJobOpening::route('/{record}/edit'),
            'view'   => Pages\ViewJobOpening::route('/{record}'),
        ];
    }
}
