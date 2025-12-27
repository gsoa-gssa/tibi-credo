<?php

namespace App\Filament\Resources;

use App\Models\BatchKind;
use App\Filament\Resources\BatchKindResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BatchKindResource extends Resource
{
    protected static ?string $model = BatchKind::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    public static function getModelLabel(): string
    {
        return __('batchKind.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('batchKind.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Short Names')
                    ->schema([
                        Forms\Components\TextInput::make('short_name_de')
                            ->label('German')
                            ->required(),
                        Forms\Components\TextInput::make('short_name_fr')
                            ->label('French')
                            ->required(),
                        Forms\Components\TextInput::make('short_name_it')
                            ->label('Italian')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Subjects')
                    ->schema([
                        Forms\Components\Textarea::make('subject_de')
                            ->label('German'),
                        Forms\Components\Textarea::make('subject_fr')
                            ->label('French'),
                        Forms\Components\Textarea::make('subject_it')
                            ->label('Italian'),
                    ]),
                Forms\Components\Section::make('Bodies')
                    ->schema([
                        Forms\Components\RichEditor::make('body_de')
                            ->label('German'),
                        Forms\Components\RichEditor::make('body_fr')
                            ->label('French'),
                        Forms\Components\RichEditor::make('body_it')
                            ->label('Italian'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('short_name_de')
                    ->label('German')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_name_fr')
                    ->label('French')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_name_it')
                    ->label('Italian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchKinds::route('/'),
            'create' => Pages\CreateBatchKind::route('/create'),
            'edit' => Pages\EditBatchKind::route('/{record}/edit'),
        ];
    }
}
