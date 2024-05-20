<?php

namespace App\Filament\Resources\ZipcodeResource\Pages;

use App\Filament\Resources\ZipcodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZipcode extends EditRecord
{
    protected static string $resource = ZipcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
