<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Source;

class PublicSourceView extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.public-source-view';

    public function getViewData(): array
    {
        if ($lang = request()->query('lang')) {
            app()->setLocale($lang);
        }
        $sourceId = request()->route('source');
        $source = \App\Models\Source::find($sourceId);
        return [
            'record' => $source,
        ];
    }


}
