<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CreateBatchWorkflow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return __('pages.createBatchWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.createBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.createBatchWorkflow.navigationGroup');
    }

    protected static string $view = 'filament.pages.create-batch-workflow-stub';
}
