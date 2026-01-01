<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\SignatureSheet;

class PublicSignatureSheetList extends Page implements HasTable
{
    use InteractsWithTable;

    // protected static ?string $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.public-signature-sheet-list';

    protected function getTableQuery()
    {
        $scopeId = request()->query('signature_collection_id');
        return SignatureSheet::query()->where('signature_collection_id', $scopeId);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('short_name')->searchable()->label(__('signatureSheet.fields.short_name')),
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
        return __('signatureSheet.namePlural') . $collection_name;
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label(__('View'))
                ->url(fn ($record) => \URL::temporarySignedRoute('public.signature-sheets.show', now()->addMinutes(30), ['sheet' => $record->id, 'signature_collection_id' => request()->query('signature_collection_id')]))
                ->openUrlInNewTab(false),
        ];
    }

    public static function canAccess(): bool
    {
        return request()->hasValidSignature();
    }
}
