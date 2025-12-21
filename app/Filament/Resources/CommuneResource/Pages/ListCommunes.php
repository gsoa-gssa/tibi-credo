<?php

namespace App\Filament\Resources\CommuneResource\Pages;

use App\Filament\Resources\CommuneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommunes extends ListRecords
{
    protected static string $resource = CommuneResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
