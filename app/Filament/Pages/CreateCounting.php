<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Resources\CountingResource;

class CreateCounting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('pages.createCounting.name');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.workflows');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount()
    {
        return redirect()->to(\App\Filament\Resources\CountingResource::getUrl('create'));
    }
}

