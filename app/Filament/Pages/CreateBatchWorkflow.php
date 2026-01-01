<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Resources\BatchResource;

class CreateBatchWorkflow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('pages.createBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.createBatchWorkflow.navigationGroup');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount()
    {
        return redirect()->to(BatchResource::getUrl('create'));
    }
}
