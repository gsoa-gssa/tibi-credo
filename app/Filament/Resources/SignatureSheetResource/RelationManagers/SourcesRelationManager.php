<?php

namespace App\Filament\Resources\SignatureSheetResource\RelationManagers;

use App\Models\Source;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Storage;

class SourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'sources';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('source.namePlural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('source.fields.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_description')
                    ->label(__('source.fields.short_description'))
                    ->searchable(
                        query: function ($query, string $search) {
                            $query->where(function ($q) use ($search) {
                                $q->where('short_description_de', 'like', "%$search%")
                                  ->orWhere('short_description_fr', 'like', "%$search%")
                                  ->orWhere('short_description_it', 'like', "%$search%")
                                ;
                            });
                        }
                    )
                    ->getStateUsing(fn ($record) => $record->getLocalized('short_description')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('customized_pdf')
                    ->label(__('source.actions.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Source $record) {
                        $sheet = $this->getOwnerRecord();
                        return $sheet->download($record);
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
