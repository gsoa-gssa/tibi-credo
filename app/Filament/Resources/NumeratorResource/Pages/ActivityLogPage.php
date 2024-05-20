<?php

namespace App\Filament\Resources\NumeratorResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\NumeratorResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ActivityLogPage extends ListActivities
{
    protected static string $resource = NumeratorResource::class;
}
