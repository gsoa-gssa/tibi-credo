<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CommuneLink extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 6;

    public function getTitle(): string
    {
        return __('pages.communeReminders.namePlural');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.communeReminders.namePlural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.workflows');
    }

    public function mount(): void
    {
        $this->redirect(route('filament.app.resources.communes.index'));
    }

    protected static string $view = 'filament.pages.commune-link';
}
