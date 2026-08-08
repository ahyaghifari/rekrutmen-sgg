<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FormSectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'formSections';
    protected static ?string $title = 'Bagian Form Lamaran';
    protected static ?string $modelLabel = 'Bagian';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Judul Bagian')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Repeater::make('fields')
                ->label('Field')
                ->relationship('fields')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Key (nama variabel)')
                        ->required()
                        ->helperText('Huruf kecil, underscore. Contoh: nik, tinggi_badan')
                        ->rules(['regex:/^[a-z_]+$/']),

                    Forms\Components\TextInput::make('label')
                        ->label('Label Tampilan')
                        ->required(),

                    Forms\Components\Select::make('type')
                        ->label('Tipe')
                        ->required()
                        ->default('text')
                        ->options([
                            'text'     => 'Text',
                            'textarea' => 'Textarea',
                            'number'   => 'Number',
                            'date'     => 'Date',
                            'select'   => 'Select (dropdown)',
                            'radio'    => 'Radio (pilihan)',
                            'file'     => 'File Upload',
                        ]),

                    Forms\Components\Toggle::make('is_required')
                        ->label('Wajib Diisi')
                        ->default(true),

                    Forms\Components\Textarea::make('options')
                        ->label('Opsi (JSON)')
                        ->rows(3)
                        ->helperText('select/radio: {"choices":["Opsi A","Opsi B"]} | file: {"max_kb":5120,"mimes":["pdf"]}')
                        ->columnSpanFull()
                        ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null)
                        ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null),
                ])
                ->columns(2)
                ->orderColumn('sort_order')
                ->collapsible()
                ->defaultItems(0)
                ->addActionLabel('Tambah Field')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Bagian'),

                Tables\Columns\TextColumn::make('fields_count')
                    ->label('Jumlah Field')
                    ->counts('fields'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
