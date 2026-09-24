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

    /**
     * Semua lowongan wajib dibuat dari HRIS - mode 'free' (admin di sini boleh menambah lowongan
     * sendiri) sudah tidak dipakai lagi. Kolom companies.management_mode dibiarkan ada supaya tidak
     * perlu ubah skema lagi (sempat dihapus lalu dikembalikan), tapi nilainya selalu 'hris'.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Gaya surat SGG (tabel, 1 baris = label + Orientasi + Kontrak berdampingan) vs RS (list,
     * dua daftar independen). Default true (SGG) kalau company belum dipilih/template belum
     * diset — supaya form baru tetap tampil seperti sebelumnya sampai ketahuan company-nya RS.
     */
    protected static function isSggMode(?string $companyId): bool
    {
        if (! $companyId) {
            return true;
        }

        return Company::find($companyId)?->offering_letter_template !== \App\Enums\OfferingLetterTemplate::Rs;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Lowongan')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Perusahaan')
                    // Tidak disaring per mode lagi: form ini cuma dipakai halaman Lihat (semua
                    // lowongan dikelola dari HRIS, lihat canCreate()), jadi menyaring 'free' malah
                    // bikin nama perusahaannya kosong waktu dilihat.
                    ->options(fn () => Company::query()
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

                Forms\Components\TextInput::make('division_name')
                    ->label('Divisi')
                    ->maxLength(255)
                    ->nullable()
                    ->helperText('Dipakai token {divisi} di naskah persetujuan. Terisi otomatis kalau lowongan dibuat dari HRIS.'),

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

            Forms\Components\Section::make('Kompensasi Offering Letter')
                ->description('Komponen kompensasi (mis. "Gaji Pokok", "Seragam Kerja") untuk offering letter posisi ini. Beda posisi/lowongan bisa beda meski satu perusahaan. Bentuknya ngikut Gaya Surat perusahaan (Master > Perusahaan) — TABEL (SGG): satu daftar, tiap baris punya nominal Orientasi & Kontrak berdampingan, angka polos otomatis diformat jadi "Rp3.000.000". LIST (RS): dua daftar terpisah (Mitra & Kontrak), masing-masing tambah komponen sendiri-sendiri, nominal bebas teks/kosongkan.')
                ->schema([
                    Forms\Components\Repeater::make('offeringCompensationRows')
                        ->relationship('offeringCompensationRows', modifyQueryUsing: fn ($query) => $query->whereNull('kind'))
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->label('Komponen')
                                ->placeholder('Mis. Gaji Pokok')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('amount_orientasi')
                                ->label('Nominal — Orientasi')
                                ->placeholder('Angka polos, mis. 3000000')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('amount_kontrak')
                                ->label('Nominal — Kontrak')
                                ->placeholder('Angka polos, mis. 3500000')
                                ->maxLength(255),
                        ])
                        ->columns(2)
                        ->visible(fn (\Filament\Forms\Get $get) => static::isSggMode($get('company_id')))
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Komponen baru')
                        ->addActionLabel('Tambah komponen')
                        ->defaultItems(0),

                    Forms\Components\Repeater::make('offeringCompensationRowsMitra')
                        ->relationship('offeringCompensationRows', modifyQueryUsing: fn ($query) => $query->where('kind', 'mitra'))
                        ->label('Komponen Mitra (Orientasi)')
                        ->schema([
                            Forms\Components\Hidden::make('kind')->default('mitra'),
                            Forms\Components\TextInput::make('label')
                                ->label('Komponen')
                                ->placeholder('Mis. Gaji Pokok')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('amount_orientasi')
                                ->label('Nominal')
                                ->placeholder('Bebas teks atau kosongkan')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (\Filament\Forms\Get $get) => ! static::isSggMode($get('company_id')))
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Komponen baru')
                        ->addActionLabel('Tambah komponen Mitra')
                        ->defaultItems(0),

                    Forms\Components\Repeater::make('offeringCompensationRowsKontrak')
                        ->relationship('offeringCompensationRows', modifyQueryUsing: fn ($query) => $query->where('kind', 'kontrak'))
                        ->label('Komponen Kontrak')
                        ->schema([
                            Forms\Components\Hidden::make('kind')->default('kontrak'),
                            Forms\Components\TextInput::make('label')
                                ->label('Komponen')
                                ->placeholder('Mis. Gaji Yang Didapatkan')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('amount_kontrak')
                                ->label('Nominal')
                                ->placeholder('Bebas teks atau kosongkan')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (\Filament\Forms\Get $get) => ! static::isSggMode($get('company_id')))
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Komponen baru')
                        ->addActionLabel('Tambah komponen Kontrak')
                        ->defaultItems(0),
                ])
                ->collapsible()
                ->collapsed(),

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
                                ->columnSpanFull()
                                ->helperText('Bisa pakai token {jabatan} (nama lowongan) dan {divisi} — terisi otomatis per kandidat.'),
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

                // Lowongan dari HRIS (hris_job_opening_id terisi) read-only di sini - HRIS yang
                // menimpanya tiap sinkron, jadi editan di sini bakal hilang. Sisa lowongan lama yang
                // dibuat di project ini sebelum semuanya dipindah ke HRIS tetap bisa dirawat, karena
                // HRIS tidak punya cara menautkannya (RecruitmentSync::getUnlinkedJobOpenings() ada
                // tapi belum ada halaman yang memakainya).
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->hris_job_opening_id === null),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->hris_job_opening_id === null),
                Tables\Actions\ViewAction::make()
                    ->visible(fn ($record) => $record->hris_job_opening_id !== null),
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
