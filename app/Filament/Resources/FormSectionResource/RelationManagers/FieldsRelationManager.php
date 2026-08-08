<?php

namespace App\Filament\Resources\FormSectionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';
    protected static ?string $title = 'Field';
    protected static ?string $modelLabel = 'Field';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Key (nama variabel)')
                ->required()
                ->helperText('Huruf kecil + underscore. Contoh: nik, tinggi_badan')
                ->rules(['regex:/^[a-z_]+$/'])
                ->maxLength(100),

            Forms\Components\TextInput::make('label')
                ->label('Label Tampilan')
                ->required()
                ->maxLength(255),

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
                ])
                ->live(),

            Forms\Components\Toggle::make('is_required')
                ->label('Wajib Diisi')
                ->default(true),

            Forms\Components\Textarea::make('options')
                ->label('Opsi (JSON)')
                ->rows(3)
                ->columnSpanFull()
                ->helperText('select/radio: {"choices":["Opsi A","Opsi B"]} | file: {"max_kb":5120,"mimes":["pdf"]}')
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['select', 'radio', 'file']))
                ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null)
                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->width(50)
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Key')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('label')
                    ->label('Label'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'textarea'        => 'warning',
                        'select', 'radio' => 'success',
                        'file'            => 'danger',
                        'number', 'date'  => 'gray',
                        default           => 'primary',
                    }),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Wajib')
                    ->boolean(),
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
