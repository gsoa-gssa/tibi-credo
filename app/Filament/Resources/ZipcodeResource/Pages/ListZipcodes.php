<?php

namespace App\Filament\Resources\ZipcodeResource\Pages;

use App\Filament\Resources\ZipcodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZipcodes extends ListRecords
{
    protected static string $resource = ZipcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
