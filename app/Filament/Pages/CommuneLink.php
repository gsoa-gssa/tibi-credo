<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\Commune;
use App\Filament\Resources\CommuneResource;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\CommuneResource\BulkActions\RemindersBulkActionGroup;
use \App\Filament\Resources\CommuneResource\Filters;

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

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Commune::query())
            ->columns([
                Tables\Columns\TextColumn::make('name_with_canton_and_zipcode')
                    ->label(__('commune.name'))
                    ->url(fn ($record) => CommuneResource::getUrl('view', ['record' => $record]))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_contacted_on')
                    ->label(__('commune.fields.last_contacted_on'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Filters\SignaturesInOpenBatchesFilter::make(),
                Filters\NoCommentsAfterFilter::make(),
                Filters\HasCommentsAfterFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->bulkActions([
                RemindersBulkActionGroup::make(),
            ]);
    }

    protected static string $view = 'filament.pages.commune-link';
}
