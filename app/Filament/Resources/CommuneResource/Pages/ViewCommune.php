<?php

namespace App\Filament\Resources\CommuneResource\Pages;

use App\Filament\Resources\CommuneResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCommune extends ViewRecord
{
    protected static string $resource = CommuneResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CommuneResource\Widgets\CommuneStats::make()
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\EditAction::make(),
        ];
    }
}
