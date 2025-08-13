<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaeppliResource\Pages;
use App\Filament\Resources\MaeppliResource\RelationManagers;
use App\Models\Maeppli;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaeppliResource extends Resource
{
    protected static ?string $model = Maeppli::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.sheetManagement');
    }

    // Add model label
    public static function getModelLabel(): string
    {
        return __('maeppli.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('maeppli.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->label('Bezeichnung'),
                    Forms\Components\Select::make('commune_id')
                        ->label(__('maeppli.commune'))
                        ->relationship('commune', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder(__('input.placeholder.select_commune')),
                ])->columns(2),
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('sheets_count')
                        ->label(__('maeppli.sheets_count'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->required(),
                    Forms\Components\TextInput::make('sheets_valid_count')
                        ->label(__('maeppli.sheets_valid_count'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->required(),
                    Forms\Components\TextInput::make('sheets_invalid_count')
                        ->label(__('maeppli.sheets_invalid_count'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->required(),
                ])->columns(3),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('maeppli.label'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('commune.name')
                    ->label(__('maeppli.commune'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('maeppli.sheets_count'))
                    ->numeric()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_valid_count')
                    ->label(__('maeppli.sheets_valid_count'))
                    ->numeric()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_invalid_count')
                    ->label(__('maeppli.sheets_invalid_count'))
                    ->numeric()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            RelationManagers\SheetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaepplis::route('/'),
            'view' => Pages\ViewMaeppli::route('/{record}'),
            'edit' => Pages\EditMaeppli::route('/{record}/edit'),
        ];
    }
}
