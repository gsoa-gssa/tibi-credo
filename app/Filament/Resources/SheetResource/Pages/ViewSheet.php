<?php

namespace App\Filament\Resources\SheetResource\Pages;

use App\Filament\Resources\SheetResource;
use Filament\Actions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Parallax\FilamentComments\Actions\CommentsAction;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSheet extends ViewRecord
{
    protected static string $resource = SheetResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('sheet.sections.details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('label')
                            ->label('Label'),
                        Infolists\Components\TextEntry::make('signatureCount')
                            ->label('Signature Count'),
                        Infolists\Components\IconEntry::make('vox')
                            ->label('VOX')
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-circle'),
                        Infolists\Components\TextEntry::make('source.code')
                            ->label('Source'),
                        Infolists\Components\TextEntry::make('commune.name')
                            ->label('Commune')
                            ->url(fn ($record) => $record->commune ? 
                                \App\Filament\Resources\CommuneResource::getUrl('view', ['record' => $record->commune]) : 
                                null)
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Created by'),
                    ])->columns(2),
                
                Infolists\Components\Section::make(__('sheet.sections.certification'))
                    ->schema([
                        Infolists\Components\TextEntry::make('maeppli.label')
                            ->label('Maeppli')
                            ->url(fn ($record) => $record->maeppli ? 
                                \App\Filament\Resources\MaeppliResource::getUrl('view', ['record' => $record->maeppli]) : 
                                null)
                            ->color('primary')
                            ->placeholder('No Maeppli assigned'),
                        Infolists\Components\TextEntry::make('batch.id')
                            ->label('Batch')
                            ->formatStateUsing(fn ($state, $record) => $record->batch ? 
                                "Batch #{$record->batch->id}" : 
                                'No batch assigned')
                            ->url(fn ($record) => $record->batch ? 
                                \App\Filament\Resources\BatchResource::getUrl('view', ['record' => $record->batch]) : 
                                null)
                            ->color('primary'),
                    ])->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CommentsAction::make(),
            EditAction::make()
        ];
    }
}
