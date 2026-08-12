<?php

namespace App\Filament\Pages;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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

    public function mount(): void
    {
        $this->quizId = request('quiz');
    }

    public function getQuizzes(): \Illuminate\Support\Collection
    {
        return Quiz::orderBy('name')->get(['id', 'name']);
    }

    private function questionForm(): array
    {
        return [
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
                ->addActionLabel('Tambah Pilihan')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('points')
                ->label('Poin')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->required(),
        ];
    }

    private function withDerivedCorrectOption(array $data): array
    {
        $data['correct_option'] = collect($data['options'] ?? [])->firstWhere('is_correct', true)['label'] ?? null;

        return $data;
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
                Tables\Columns\TextColumn::make('points')->label('Poin'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Soal')
                    ->visible(fn () => (bool) $this->quizId)
                    ->model(QuizQuestion::class)
                    ->form($this->questionForm())
                    ->mutateFormDataUsing(fn (array $data) => $this->withDerivedCorrectOption($data) + ['quiz_id' => $this->quizId]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($this->questionForm())
                    ->mutateFormDataUsing(fn (array $data) => $this->withDerivedCorrectOption($data)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Pilih kuis dulu')
            ->emptyStateDescription('Pilih kuis di atas untuk mengelola soalnya.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
