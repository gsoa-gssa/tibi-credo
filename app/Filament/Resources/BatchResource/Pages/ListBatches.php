<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Filament\Resources\BatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\BatchResource\Widgets\BatchesByCreationDateChart::make(),
            \App\Filament\Resources\BatchResource\Widgets\SignaturesByCreationDateChart::make(),
        ];
    }
}
