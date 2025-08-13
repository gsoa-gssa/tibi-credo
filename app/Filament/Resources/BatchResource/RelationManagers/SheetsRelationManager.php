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
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => \App\Filament\Resources\SheetResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('signatureCount'),
                Tables\Columns\IconColumn::make('maeppli_id')
                    ->label(__('maeppli.name'))
                    ->getStateUsing(fn ($record) => $record->maeppli_id ?? false)
                    ->icon(function ($state) {
                        return $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->url(fn ($record) => $record->maeppli ?
                        \App\Filament\Resources\MaeppliResource::getUrl('view', ['record' => $record->maeppli]) :
                        null)
                    ->extraAttributes(fn ($record) => $record->maeppli ? [
                        'title' => $record->maeppli->label,
                    ] : []),
            ])
            ->defaultSort('label', 'asc')
            ->filters([
                // maepple null or not
                Tables\Filters\Filter::make('maeppli_id')
                    ->label(__('maeppli.name'))
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('maeppli_id')),
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
