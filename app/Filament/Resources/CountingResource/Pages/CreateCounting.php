<?php

namespace App\Filament\Resources\CountingResource\Pages;

use App\Filament\Resources\CountingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCounting extends CreateRecord
{
    protected static string $resource = CountingResource::class;
}
