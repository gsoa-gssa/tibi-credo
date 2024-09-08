<?php

namespace App\Filament\Resources\CountingResource\Pages;

use App\Filament\Resources\CountingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCountings extends ListRecords
{
    protected static string $resource = CountingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
