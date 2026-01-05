<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Source;

class PublicSourceList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.public-source-list';

    protected function getTableQuery()
    {
        $scopeId = request()->query('signature_collection_id');
        return Source::query()->where('signature_collection_id', $scopeId);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('code')->searchable()->label(__('source.fields.code')),
            Tables\Columns\TextColumn::make('short_description_de')->label(__('source.fields.short_description_de')),
            Tables\Columns\TextColumn::make('short_description_fr')->label(__('source.fields.short_description_fr')),
            Tables\Columns\TextColumn::make('short_description_it')->label(__('source.fields.short_description_it')),
        ];
    }

    public function getTitle(): string
    {
        $scopeId = request()->query('signature_collection_id');
        $collection_name = '';
        if ($scopeId) {
            $collection = \App\Models\SignatureCollection::find($scopeId);
            if ($collection) {
                $collection_name = ' - ' . $collection->short_name;
            }
        }
        return __('source.namePlural') . $collection_name;
    }

    protected function getTableActions(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return request()->hasValidSignature();
    }
}
