<?php

namespace App\Filament\Resources\SignatureSheetResource\Pages;

use App\Filament\Resources\SignatureSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignatureSheets extends ListRecords
{
    protected static string $resource = SignatureSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
