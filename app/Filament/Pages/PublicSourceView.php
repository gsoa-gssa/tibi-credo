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
        $sourceId = request()->route('source');
        $source = \App\Models\Source::find($sourceId);
        // Try to get start and end date from the signature collection
        $signatureCollection = $source?->signatureCollection;
        $startDate = $signatureCollection?->publication_date;
        $endDate = $signatureCollection?->end_date;
        return [
            'source' => $source,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }


}
