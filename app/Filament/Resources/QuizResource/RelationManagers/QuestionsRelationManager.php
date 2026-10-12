<?php

namespace App\Filament\Resources\QuizResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $title = 'Soal';
    protected static ?string $modelLabel = 'Soal';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
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
                Tables\Actions\Action::make('kelolaSoal')
                    ->label('Kelola Soal')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn () => route('filament.admin.pages.quiz-questions-page', ['quiz' => $this->getOwnerRecord()->id])),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription('Klik "Kelola Soal" untuk mulai menambahkan.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
