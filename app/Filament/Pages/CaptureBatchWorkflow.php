<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Resources\MaeppliResource;

class CaptureBatchWorkflow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('pages.captureBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.captureBatchWorkflow.navigationGroup');
    }

    public function mount()
    {
        return redirect()->to(MaeppliResource::getUrl('create'));
    }
}
