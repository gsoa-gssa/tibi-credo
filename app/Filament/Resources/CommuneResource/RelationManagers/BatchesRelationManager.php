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
use App\Models\Batch;
use App\Filament\Resources\BatchResource;

class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('batch.namePlural');
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->recordUrl(fn (Model $record) => BatchResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('batch.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('batch.fields.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('batch.fields.sheets'))
                    ->counts('sheets')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_without_maeppli_count')
                    ->label(__('batch.fields.sheets_not_in_maeppli'))
                    ->counts(['sheets as sheets_without_maeppli_count' => fn ($q) => $q->whereNull('maeppli_id')])
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
