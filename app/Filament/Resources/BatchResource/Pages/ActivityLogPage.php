<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Filament\Resources\BatchResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ActivityLogPage extends ListActivities
{
    protected static string $resource = BatchResource::class;
}
