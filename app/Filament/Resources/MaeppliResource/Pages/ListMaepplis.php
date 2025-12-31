<?php

namespace App\Filament\Resources\MaeppliResource\Pages;

use App\Filament\Resources\MaeppliResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaepplis extends ListRecords
{
    protected static string $resource = MaeppliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
