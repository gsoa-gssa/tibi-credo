<?php

namespace App\Filament\Resources\CommuneResource\Pages;

use App\Filament\Resources\CommuneResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCommune extends ViewRecord
{
    protected static string $resource = CommuneResource::class;

    public function getTitle(): string
    {
        return $this->record->name_with_canton_and_zipcode;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CommuneResource\Widgets\CommuneStats::make(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('label')
                ->label(__('Label'))
                ->icon('heroicon-m-printer')
                ->url(fn () => route('communes.label', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
            Actions\EditAction::make(),
        ];
    }
}
