<?php

namespace App\Filament\Pages;

use App\Models\Quiz;
use App\Models\QuizQuestion;
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

class QuizQuestionsPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel = 'Soal Kuis';
    protected static ?string $title           = 'Soal Kuis';
    protected static ?string $navigationGroup = 'Perusahaan & Lowongan';
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
                'question'   => $q->question,
                'image_path' => $q->image_path,
                'options'    => collect($q->options ?? [])->map(fn ($opt) => [
                    'label'      => $opt,
                    'is_correct' => $opt === $q->correct_option,
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

                        Forms\Components\Repeater::make('options')
                            ->label('Pilihan Jawaban')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Pilihan')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Checkbox::make('is_correct')
                                    ->label('Jawaban Benar'),
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
                'question'       => $q['question'],
                'image_path'     => $q['image_path'] ?? null,
                'options'        => $options->pluck('label')->all(),
                'correct_option' => $options->firstWhere('is_correct', true)['label'] ?? null,
                'sort_order'     => $i,
            ]);
        }

        Notification::make()->title('Soal tersimpan')->success()->send();
        $this->mode = 'table';
        $this->resetTable();
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
                Tables\Columns\ImageColumn::make('image_path')->label('Gambar')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('question')->label('Pertanyaan')->limit(60),
                Tables\Columns\TextColumn::make('correct_option')->label('Jawaban Benar')->badge()->color('success'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription($this->quizId ? 'Klik "Kelola Soal" untuk mulai menambahkan.' : 'Pilih kuis dulu untuk melihat soalnya.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
