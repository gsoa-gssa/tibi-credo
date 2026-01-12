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

    public function mount(): void
    {
        if ($lang = request()->query('lang')) {
            app()->setLocale($lang);
        }
    }

    protected function getTableQuery()
    {
        $scopeId = request()->query('signature_collection_id');
        // Sort by the total countings (objects) for each source, descending
        return Source::query()
            ->where('signature_collection_id', $scopeId)
            ->withSum(['countings as countings_total' => function ($q) use ($scopeId) {
                $q->whereHas('source', function ($q2) use ($scopeId) {
                    $q2->where('signature_collection_id', $scopeId);
                });
            }], 'count')
            ->orderByDesc('countings_total');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('countings_total')
                ->label(__('source.computed_fields.total')),
            Tables\Columns\TextColumn::make('code')
                ->searchable()
                ->label(__('source.fields.code')),
            Tables\Columns\TextColumn::make('short_description_de')
                ->label(__('source.fields.short_description_de'))
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('short_description_fr')
                ->label(__('source.fields.short_description_fr'))
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('short_description_it')
                ->label(__('source.fields.short_description_it'))
                ->toggleable(isToggledHiddenByDefault: true),
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
        return [
            Tables\Actions\Action::make('view')
                ->label(__('source.actions.view'))
                ->icon('heroicon-o-eye')
                ->url(fn($record) => \Illuminate\Support\Facades\URL::signedRoute('public.source.view', [
                    'source' => $record->id,
                    'lang' => app()->getLocale(),
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public static function canAccess(): bool
    {
        return request()->hasValidSignature();
    }

    protected function getHeaderWidgets(): array
    {
        $scopeId = request()->query('signature_collection_id');
        return [
            \App\Filament\Resources\SourceResource\Widgets\SourcePieChart::make([
                'signatureCollectionId' => $scopeId,
                'columnSpan' => 'full',
            ]),
        ];
    }
}
