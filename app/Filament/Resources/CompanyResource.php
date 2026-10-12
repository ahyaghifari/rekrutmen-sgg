<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers\FormSectionsRelationManager;
use App\Enums\OfferingLetterTemplate;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyResource extends BaseResource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Perusahaan';
    protected static ?string $modelLabel = 'Perusahaan';
    protected static ?string $pluralModelLabel = 'Perusahaan';
    protected static ?string $navigationGroup = 'Master';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::isSuperAdmin()) {
            return $query;
        }

        return $query->where('id', static::companyId());
    }

    public static function canCreate(): bool
    {
        return static::isSuperAdmin();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Perusahaan')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn () => !static::isSuperAdmin()),

                Forms\Components\TextInput::make('hris_code')
                    ->label('Kode HRIS')
                    ->maxLength(10)
                    ->helperText('Kode perusahaan di sistem HRIS (contoh: 0001).')
                    ->nullable()
                    ->disabled(fn () => !static::isSuperAdmin()),
            ])->columns(2),

            Forms\Components\Section::make('Tampilan')->schema([
                Forms\Components\FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->directory('companies/logos')
                    ->imagePreviewHeight('80')
                    ->nullable()
                    ->disabled(fn () => !static::isSuperAdmin()),

                Forms\Components\ColorPicker::make('brand_color')
                    ->label('Warna Brand')
                    ->required()
                    ->default('#042c6c')
                    ->disabled(fn () => !static::isSuperAdmin()),

                Forms\Components\FileUpload::make('hero_image_path')
                    ->label('Foto Hero (halaman depan)')
                    ->image()
                    ->directory('companies/heroes')
                    ->imagePreviewHeight('120')
                    ->helperText('Foto yang tampil di banner utama halaman depan.')
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Kontak')->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email Kontak')
                    ->email()
                    ->nullable(),

                Forms\Components\TextInput::make('phone')
                    ->label('Nomor Telepon / WA')
                    ->tel()
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Offering Letter')
                ->description('Penandatangan & tampilan berlaku untuk semua offering letter perusahaan ini. Komponen kompensasi (nominal beda tiap posisi) diatur per lowongan, bukan di sini.')
                ->schema([
                    Forms\Components\Select::make('offering_letter_template')
                        ->label('Gaya Surat')
                        ->options(OfferingLetterTemplate::options())
                        ->default(OfferingLetterTemplate::Sgg->value)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('offering_letter_background_path')
                        ->label('Background Surat (Letterhead)')
                        ->image()
                        ->disk('local')
                        ->visibility('private')
                        ->directory('offering-letter-bg')
                        ->imagePreviewHeight('120')
                        ->helperText('Gambar full A4 (mis. 1241x1755px) berisi kop surat, kontak, watermark. Kosongkan untuk pakai background default.')
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('offering_signatory_name')
                        ->label('Nama Penandatangan')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('offering_signatory_title')
                        ->label('Jabatan Penandatangan')
                        ->maxLength(255),

                    Forms\Components\FileUpload::make('offering_signatory_signature_path')
                        ->label('Gambar Tanda Tangan')
                        ->image()
                        ->directory('companies/signatures')
                        ->imagePreviewHeight('60')
                        ->nullable(),

                    Forms\Components\TextInput::make('offering_benefit_note')
                        ->label('Catatan Benefit Berbintang')
                        ->helperText('Muncul di bawah daftar benefit kalau ada item yang ditandai bintang.')
                        ->placeholder('*) akan diberikan jika telah menjadi karyawan tetap.')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('offeringBenefits')
                        ->relationship()
                        ->label('Daftar Benefit')
                        ->schema([
                            Forms\Components\TextInput::make('label')->label('Benefit')->required()->maxLength(255),
                            Forms\Components\Toggle::make('is_starred')->label('Bintang (*)'),
                        ])
                        ->columns(2)
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Benefit baru')
                        ->addActionLabel('Tambah benefit')
                        ->defaultItems(0)
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('offeringTerms')
                        ->relationship()
                        ->label('Ketentuan Umum')
                        ->schema([
                            Forms\Components\TextInput::make('label')->label('Ketentuan')->required()->maxLength(255),
                        ])
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Ketentuan baru')
                        ->addActionLabel('Tambah ketentuan')
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Template Email Notifikasi')
                ->description('Gunakan {nama} dan {posisi} sebagai placeholder. Jika kosong, sistem pakai pesan default.')
                ->schema([
                    Forms\Components\Fieldset::make('Tidak Dilanjutkan (setelah peninjauan berkas)')->schema([
                        Forms\Components\TextInput::make('template_rejected_subject')
                            ->label('Subjek Email')
                            ->placeholder('Update Lamaran Kamu – {posisi}')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('template_rejected_body')
                            ->label('Isi Pesan')
                            ->rows(5)
                            ->placeholder("Halo {nama},\n\nTerima kasih telah melamar...")
                            ->columnSpanFull(),
                    ])->columns(1),

                    Forms\Components\Fieldset::make('Lolos Seleksi Berkas (shortlisted)')->schema([
                        Forms\Components\TextInput::make('template_passed_subject')
                            ->label('Subjek Email')
                            ->placeholder('Selamat! Kamu Lolos Seleksi Berkas – {posisi}')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('template_passed_body')
                            ->label('Isi Pesan')
                            ->rows(5)
                            ->placeholder("Halo {nama},\n\nKami dengan senang hati menginformasikan...")
                            ->columnSpanFull(),
                    ])->columns(1),

                    Forms\Components\Fieldset::make('Tidak Dilanjutkan (manual, di luar alur normal)')->schema([
                        Forms\Components\TextInput::make('template_manual_rejected_subject')
                            ->label('Subjek Email')
                            ->placeholder('Update Lamaran Kamu – {posisi}')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('template_manual_rejected_body')
                            ->label('Isi Pesan')
                            ->rows(5)
                            ->placeholder("Halo {nama},\n\nTerima kasih atas partisipasimu...")
                            ->helperText('Dipakai saat admin menandai "Tidak Dilanjutkan" di luar alur normal (mis. kandidat mengundurkan diri), di tahap manapun.')
                            ->columnSpanFull(),
                    ])->columns(1),
                ])
                ->collapsible()
                ->collapsed(),

            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_hospital')
                    ->label('Rumah Sakit')
                    ->helperText('Tampil sebagai tujuan lamaran di portal dokter.')
                    ->live()
                    ->disabled(fn () => !static::isSuperAdmin()),
                Forms\Components\Toggle::make('dokter_umum_open')
                    ->label('Penerimaan Dokter Umum Dibuka')
                    ->helperText('Buka/tutup rekrutmen dokter umum di rumah sakit ini (tanpa lowongan).')
                    ->visible(fn (Forms\Get $get) => (bool) $get('is_hospital'))
                    ->disabled(fn () => !static::isSuperAdmin()),
                Forms\Components\Toggle::make('is_active')
                    ->label('Perusahaan Aktif')
                    ->helperText('Nonaktifkan untuk menyembunyikan lowongan dari halaman publik.')
                    ->default(true)
                    ->disabled(fn () => !static::isSuperAdmin()),
            ])->visible(fn () => static::isSuperAdmin()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(36)
                    ->width(36)
                    ->defaultImageUrl(asset('logo-sgg.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ColorColumn::make('brand_color')
                    ->label('Warna'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon'),

                Tables\Columns\IconColumn::make('is_hospital')
                    ->label('RS')
                    ->boolean(),

                Tables\Columns\IconColumn::make('dokter_umum_open')
                    ->label('Dokter Umum Buka')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('hris_code')
                    ->label('Kode HRIS')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FormSectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
