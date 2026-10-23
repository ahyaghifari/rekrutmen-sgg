<?php

namespace App\Filament\Pages;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSection;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class QuizQuestionsPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel = 'Soal Kuis';
    protected static ?string $title           = 'Soal Kuis';
    protected static ?string $navigationGroup = 'Master';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.quiz-questions-page';

    public ?string $quizId = null;
    public string $mode = 'table'; // 'table' (urutkan/lihat) atau 'edit' (kelola lewat repeater)

    public ?array $data = [];

    public function mount(): void
    {
        $this->quizId = request('quiz');
        $this->form->fill(['questions' => []]);
    }

    public function getQuizzes(): \Illuminate\Support\Collection
    {
        return Quiz::orderBy('name')->get(['id', 'name']);
    }

    public function updatedQuizId(): void
    {
        $this->mode = 'table';
        $this->resetTable();
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;

        if ($mode === 'edit') {
            $this->loadQuestionsIntoForm();
        }
    }

    private function loadQuestionsIntoForm(): void
    {
        $questions = $this->quizId
            ? QuizQuestion::where('quiz_id', $this->quizId)->orderBy('sort_order')->get()
            : collect();

        $this->form->fill([
            'questions' => $questions->map(fn (QuizQuestion $q) => [
                'quiz_section_id' => $q->quiz_section_id,
                'question'   => $q->question,
                'image_path' => $q->image_path,
                'max_select' => $q->max_select,
                'options'    => collect($q->options ?? [])->map(fn ($opt) => [
                    'label'      => $opt,
                    'image'      => $q->option_images[$opt] ?? null,
                    'is_correct' => $q->max_select
                        ? in_array($opt, explode(',', (string) $q->correct_option), true)
                        : $opt === $q->correct_option,
                ])->all(),
            ])->all(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Repeater::make('questions')
                    ->label('Daftar Soal')
                    ->schema([
                        Forms\Components\Select::make('quiz_section_id')
                            ->label('Subtest')
                            ->options(fn () => $this->quizId
                                ? QuizSection::where('quiz_id', $this->quizId)->orderBy('sort_order')->pluck('name', 'id')
                                : [])
                            ->placeholder('Tanpa subtest')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('question')
                            ->label('Pertanyaan')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Gambar Soal (opsional)')
                            ->image()
                            ->directory('quiz-questions')
                            ->imagePreviewHeight('160')
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('max_select')
                            ->label('Maks. pilihan dipilih (kosong = pilih satu)')
                            ->numeric()
                            ->minValue(2)
                            ->nullable()
                            ->helperText('Isi untuk soal multi-jawaban, lalu centang semua jawaban benar.'),

                        Forms\Components\Repeater::make('options')
                            ->label('Pilihan Jawaban')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Pilihan')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Checkbox::make('is_correct')
                                    ->label('Jawaban Benar'),
                                Forms\Components\FileUpload::make('image')
                                    ->label('Gambar Pilihan (opsional)')
                                    ->image()
                                    ->directory('quiz-options')
                                    ->imagePreviewHeight('80')
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Tambah Pilihan')
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'Soal baru')
                    ->collapsible()
                    ->collapsed()
                    ->reorderableWithButtons()
                    ->addActionLabel('Tambah Soal')
                    ->columnSpanFull(),
            ]);
    }

    /** Simpan semua soal sekaligus — hapus yang lama, tulis ulang dari repeater. */
    public function save(): void
    {
        if (! $this->quizId) return;

        $state = $this->form->getState();

        QuizQuestion::where('quiz_id', $this->quizId)->delete();

        foreach ($state['questions'] ?? [] as $i => $q) {
            $options = collect($q['options'] ?? []);

            QuizQuestion::create([
                'quiz_id'        => $this->quizId,
                'quiz_section_id' => $q['quiz_section_id'] ?? null,
                'question'       => $q['question'],
                'image_path'     => $q['image_path'] ?? null,
                'options'        => $options->pluck('label')->all(),
                'option_images'  => $options->filter(fn ($o) => ! empty($o['image']))->pluck('image', 'label')->all() ?: null,
                'max_select'     => $q['max_select'] ?: null,
                'correct_option' => ($q['max_select'] ?? null)
                    ? $options->where('is_correct', true)->pluck('label')->implode(',')
                    // DISC tidak punya kunci; kolom correct_option NOT NULL.
                    : ($options->firstWhere('is_correct', true)['label'] ?? (Quiz::whereKey($this->quizId)->value('type') === 'disc' ? '' : null)),
                'sort_order'     => $i,
            ]);
        }

        Notification::make()->title('Soal tersimpan')->success()->send();
        $this->mode = 'table';
        $this->resetTable();
    }

    public function templateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('template')
            ->label('Unduh Template')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('gray')
            ->visible(fn () => (bool) $this->quizId)
            ->action(fn () => Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithColumnWidths {
                public function array(): array
                {
                    return [
                        ['Pertanyaan', 'A', 'B', 'C', 'D', 'E', 'Jawaban Benar'],
                        ['Contoh: 2 + 2 = ?', '3', '4', '5', '6', '', 'B'],
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 60,
                        'B' => 15,
                        'C' => 15,
                        'D' => 15,
                        'E' => 15,
                        'F' => 15,
                        'G' => 18,
                    ];
                }
            }, 'template-soal.xlsx'));
    }

    /** Import soal dari Excel: kolom Pertanyaan, A..E, Jawaban Benar (isi hurufnya). Menambah, tidak menghapus soal lama. */
    public function importAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('import')
            ->label('Import Soal (Excel)')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('gray')
            ->visible(fn () => (bool) $this->quizId)
            ->form([
                Forms\Components\FileUpload::make('file')
                    ->label('File Excel')
                    ->directory('quiz-imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->required()
                    ->helperText('Kolom: Pertanyaan, A, B, C, D, E (kosongkan yang tidak dipakai), Jawaban Benar (isi hurufnya, mis. B). Baris pertama harus header. Soal baru ditambahkan, soal lama tidak dihapus.'),
            ])
            ->modalHeading('Import Soal dari Excel')
            ->modalSubmitActionLabel('Import')
            ->modalCancelActionLabel('Batal')
            ->action(function (array $data): void {
                $path = $data['file'];
                $fullPath = Storage::disk('public')->path($path);
                $rows = Excel::toArray(new class {}, $fullPath)[0] ?? [];
                Storage::disk('public')->delete($path);

                $header = array_map('trim', array_map('strtolower', array_shift($rows) ?? []));
                $nextSort = (int) (QuizQuestion::where('quiz_id', $this->quizId)->max('sort_order') ?? -1) + 1;

                $imported = 0;
                $skipped  = 0;

                foreach ($rows as $row) {
                    if (empty(array_filter($row, fn ($v) => trim((string) $v) !== ''))) continue;

                    $assoc = array_combine($header, array_pad($row, count($header), null));
                    $question = trim((string) ($assoc['pertanyaan'] ?? ''));

                    $optionsByLetter = [];
                    foreach (['a', 'b', 'c', 'd', 'e'] as $letter) {
                        $val = trim((string) ($assoc[$letter] ?? ''));
                        if ($val !== '') $optionsByLetter[$letter] = $val;
                    }

                    $correctLetter = strtolower(trim((string) ($assoc['jawaban benar'] ?? '')));
                    $correct = $optionsByLetter[$correctLetter] ?? null;

                    if ($question === '' || count($optionsByLetter) < 2 || $correct === null) {
                        $skipped++;
                        continue;
                    }

                    $options = array_values($optionsByLetter);

                    QuizQuestion::create([
                        'quiz_id'        => $this->quizId,
                        'question'       => $question,
                        'options'        => $options,
                        'correct_option' => $correct,
                        'sort_order'     => $nextSort++,
                    ]);
                    $imported++;
                }

                $this->resetTable();

                Notification::make()
                    ->title("{$imported} soal berhasil diimport" . ($skipped > 0 ? ", {$skipped} baris dilewati (tidak lengkap/jawaban benar tidak cocok)." : '.'))
                    ->color($skipped > 0 ? 'warning' : 'success')
                    ->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->quizId
                    ? QuizQuestion::query()->where('quiz_id', $this->quizId)
                    : QuizQuestion::query()->whereRaw('1 = 0')
            )
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->width(50)->sortable(),
                Tables\Columns\TextColumn::make('section.name')->label('Subtest')->placeholder('—'),
                Tables\Columns\ImageColumn::make('image_path')->label('Gambar')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('question')->label('Pertanyaan')->limit(60),
                Tables\Columns\TextColumn::make('correct_option')->label('Jawaban Benar')->badge()->color('success'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                $this->templateAction(),
                $this->importAction(),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription($this->quizId ? 'Klik "Kelola Soal" untuk mulai menambahkan.' : 'Pilih kuis dulu untuk melihat soalnya.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
