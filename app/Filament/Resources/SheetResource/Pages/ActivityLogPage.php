<?php

namespace App\Filament\Resources\SheetResource\Pages;

use App\Filament\Resources\SheetResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ActivityLogPage extends ListActivities
{
    protected static string $resource = SheetResource::class;
}
