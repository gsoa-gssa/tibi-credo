<?php

namespace App\Filament\Resources\SheetResource\Pages;

use App\Filament\Resources\SheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSheet extends EditRecord
{
    protected static string $resource = SheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
