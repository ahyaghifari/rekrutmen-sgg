<?php

namespace App\Filament\Resources\QuizResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';
    protected static ?string $title = 'Subtest';
    protected static ?string $modelLabel = 'Subtest';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama Subtest')->required()->maxLength(255)
                ->helperText('Contoh: "Subtest 1 – Series".'),
            Forms\Components\Textarea::make('instructions')->label('Instruksi Subtest')->rows(2)->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Subtest'),
                Tables\Columns\TextColumn::make('questions_count')->label('Jumlah Soal')->counts('questions')->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([Tables\Actions\CreateAction::make()->mutateFormDataUsing(
                fn (array $data) => $data + ['sort_order' => (int) $this->getOwnerRecord()->sections()->max('sort_order') + 1]
            )])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada subtest')
            ->emptyStateDescription('Opsional. Tanpa subtest, soal tampil satu daftar.');
    }
}
