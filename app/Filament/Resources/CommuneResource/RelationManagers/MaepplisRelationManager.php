<?php

namespace App\Filament\Resources\CommuneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MaeppliResource;

class MaepplisRelationManager extends RelationManager
{
    protected static string $relationship = 'maepplis';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('maeppli.namePlural');
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->recordUrl(fn (Model $record) => MaeppliResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('maeppli.label'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('maeppli.sheets_count'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_valid_count')
                    ->label(__('maeppli.sheets_valid_count'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_invalid_count')
                    ->label(__('maeppli.sheets_invalid_count'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('maeppli.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // none
            ])
            ->headerActions([
                // view-only
            ])
            ->actions([
                // view-only
            ])
            ->bulkActions([
                // view-only
            ]);
    }
}
