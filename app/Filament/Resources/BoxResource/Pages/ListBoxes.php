<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use Filament\Resources\Pages\ListRecords;

class ListBoxes extends ListRecords
{
    protected static string $resource = BoxResource::class;
}
