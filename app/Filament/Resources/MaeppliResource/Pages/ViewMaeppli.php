<?php

namespace App\Filament\Resources\MaeppliResource\Pages;

use App\Filament\Resources\MaeppliResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaeppli extends ViewRecord
{
    protected static string $resource = MaeppliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
