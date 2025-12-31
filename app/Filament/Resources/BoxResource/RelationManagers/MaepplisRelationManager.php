<?php

namespace App\Filament\Resources\BoxResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use App\Models\Commune;

class MaepplisRelationManager extends RelationManager
{
    protected static string $relationship = 'maepplis';

    // translated title
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('box.related.maepplis');
    }

    // No editing form: only associate/dissociate Maeppli with this Box

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('display_label_html')
                    ->label(__('maeppli.fields.label'))
                    ->searchable(['label_number'])
                    ->html()
                    ->getStateUsing(fn ($record) => $record->display_label_html),
                TextColumn::make('commune.name_with_canton_and_zipcode')
                  ->label(__('commune.name'))
                  ->searchable(),
                TextColumn::make('signatures_valid_count')
                  ->label(__('maeppli.fields.signatures_valid_count')),
            ])
            ->filters([
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\DissociateAction::make()
                    ->label(__('maeppli.actions.remove_from_box')),
            ])
            ->bulkActions([
                Tables\Actions\DissociateBulkAction::make()
                    ->label(__('maeppli.actions.remove_selected_from_box')),
            ]);
    }
}
