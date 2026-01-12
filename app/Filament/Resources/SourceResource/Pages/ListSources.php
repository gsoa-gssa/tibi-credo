<?php

namespace App\Filament\Resources\SourceResource\Pages;

use App\Filament\Resources\SourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\URL;
use Filament\Facades\Filament;

class ListSources extends ListRecords
{
    protected static string $resource = SourceResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make(),
        ];
        $user = Filament::auth()->user();
        if ($user && ($user->hasRole('admin') || $user->hasRole('super_admin'))) {
            $actions[] = Actions\Action::make('public_link')
                ->label(__('source.actions.public_link'))
                ->icon('heroicon-o-link')
                ->url(function () {
                    $scopeId = Filament::auth()->user()->signature_collection_id;
                    return URL::signedRoute('public.sources', ['signature_collection_id' => $scopeId, 'lang' => app()->getLocale()]);
                })
                ->openUrlInNewTab();
        }
        return $actions;
    }
    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\SourceResource\Widgets\SourcePieChart::make(),
        ];
    }
}
