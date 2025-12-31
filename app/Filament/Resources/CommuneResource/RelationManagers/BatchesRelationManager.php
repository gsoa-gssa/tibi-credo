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
                // Tables\Columns\IconColumn::make('open')
                //     ->label(__('batch.fields.open'))
                //     ->icon(fn (Batch $batch) => $batch->open ? 'heroicon-o-clock' : 'heroicon-o-archive-box')
                //     ->tooltip(fn (Batch $batch) => $batch->open ? __('batch.filters.open.open') : __('batch.filters.open.closed'))
                //     ->color(fn (Batch $batch) => $batch->open ? 'warning' : 'success'),
                Tables\Columns\ToggleColumn::make('open')
                    ->label(__('batch.fields.open'))
                    ->onIcon('heroicon-o-clock')
                    ->offIcon('heroicon-o-archive-box')
                    ->onColor('warning')
                    ->offColor('success')
                    ->tooltip(fn (Batch $batch) => $batch->open ? __('batch.filters.open.open') : __('batch.filters.open.closed')),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('batch.fields_short.sheets_count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('signature_count')
                    ->label(__('batch.fields_short.signature_count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label(__('batch.fields_short.expected_delivery_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('expected_return_date')
                    ->label(__('batch.fields_short.expected_return_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('sendKind.short_name_de')
                    ->label(__('batch.fields.send_kind')),
                Tables\Columns\TextColumn::make('receiveKind.short_name_de')
                    ->label(__('batch.fields.receive_kind')),
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
