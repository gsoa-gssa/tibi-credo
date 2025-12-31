<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\Commune;
use Filament\Tables\Enums\FiltersLayout;

class CommuneLink extends Page implements HasTable
{
    use InteractsWithTable;

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

    public function table(Table $table): Table
    {
        return $table
            ->query(Commune::query())
            ->columns([
                Tables\Columns\TextColumn::make('name_with_canton_and_zipcode')
                    ->label(__('commune.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_contacted_on')
                    ->label(__('commune.fields.last_contacted_on'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                \App\Filament\Filters\SignaturesInOpenBatchesFilter::make(),
                \App\Filament\Filters\LastContactedBeforeFilter::make(),
                \App\Filament\Filters\LastContactedAfterFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3);
    }

    protected static string $view = 'filament.pages.commune-link';
}
