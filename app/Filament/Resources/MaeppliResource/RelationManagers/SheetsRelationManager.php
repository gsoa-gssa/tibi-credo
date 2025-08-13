<?php

namespace App\Filament\Resources\MaeppliResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SheetsRelationManager extends RelationManager
{
    protected static string $relationship = 'sheets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('label')
                    ->relationship("sheets", 'label')
                    ->placeholder('Bitte wählen'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => \App\Filament\Resources\SheetResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('signatureCount'),
            ])
            ->defaultSort('label', 'asc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()
            ])
            ->actions([
                Tables\Actions\DissociateAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
