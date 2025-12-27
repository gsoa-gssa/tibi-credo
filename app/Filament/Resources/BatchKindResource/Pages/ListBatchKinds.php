<?php

namespace App\Filament\Resources\BatchKindResource\Pages;

use App\Filament\Resources\BatchKindResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchKinds extends ListRecords
{
    protected static string $resource = BatchKindResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
