<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SheetsRelationManager extends RelationManager
{
    protected static ?string $inverseRelationship = 'batch';
    protected static string $relationship = 'sheets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->required()
                    ->maxLength(255)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(
                fn() => "Number of Sheets: " . $this->getAllTableRecordsCount()
            )
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono),
                Tables\Columns\TextColumn::make('signatureCount')
            ])
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
}
