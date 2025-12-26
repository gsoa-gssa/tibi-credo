<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CaptureBatchWorkflow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?int $navigationSort = 3;

    public function getTitle(): string
    {
        return __('pages.captureBatchWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.captureBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.captureBatchWorkflow.navigationGroup');
    }

    protected static string $view = 'filament.pages.capture-batch-workflow-stub';
}
