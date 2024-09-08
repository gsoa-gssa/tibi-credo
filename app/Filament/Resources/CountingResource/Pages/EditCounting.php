<?php

namespace App\Filament\Resources\CountingResource\Pages;

use App\Filament\Resources\CountingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounting extends EditRecord
{
    protected static string $resource = CountingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
