<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Canton;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\CantonResource\Pages;
use App\Filament\Resources\CantonResource\RelationManagers;

class CantonResource extends Resource
{
    protected static ?string $model = Canton::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.geoData');
    }

    public static function getModelLabel(): string
    {
        return __('canton.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('canton.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('canton.fields.name'))
                    ->formatStateUsing(fn (Canton $record) => $record->getLocalizedName())
                    ->disabled(),
                Forms\Components\TextInput::make('label')
                    ->label(__('canton.fields.label'))
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('canton.fields.name'))
                    ->formatStateUsing(fn (Canton $record) => $record->getLocalizedName())
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label(__('canton.fields.label'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommunesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCantons::route('/'),
            'view' => Pages\ViewCanton::route('/{record}'),
        ];
    }
}
