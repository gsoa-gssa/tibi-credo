<?php

namespace App\Filament\Resources\ContactTypeResource\Pages;

use App\Filament\Resources\ContactTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContactType extends CreateRecord
{
    protected static string $resource = ContactTypeResource::class;
}
