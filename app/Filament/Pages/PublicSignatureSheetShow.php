<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\SignatureSheet;
use App\Models\Source;

class PublicSignatureSheetShow extends Page implements HasTable
{
    use InteractsWithTable;

    // protected static ?string $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.public-signature-sheet-show';

    public SignatureSheet $sheet;

    public function getTitle(): string
    {
      if (!isset($this->sheet) || !$this->sheet?->short_name) {
          return __('signatureSheet.name');
      }
      return __('signatureSheet.download_with_source', [ 'name' => $this->sheet->short_name ]);
    }

    public function mount(SignatureSheet $sheet)
    {
        $this->sheet = $sheet;
    }

    protected function getTableQuery()
    {
        return Source::query()
            ->where('signature_collection_id', $this->sheet->signature_collection_id)
            ->orderBy('code');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('code')
              ->searchable()
              ->label(__('source.fields.code')),
            Tables\Columns\TextColumn::make('description')
              ->label(__('source.fields.short_description'))
              ->searchable(
                  query: function ($query, string $search) {
                      $query->where(function ($q) use ($search) {
                          $q->where('short_description_de', 'like', "%$search%")
                            ->orWhere('short_description_fr', 'like', "%$search%")
                            ->orWhere('short_description_it', 'like', "%$search%")
                          ;
                      });
                  }
              )
              ->getStateUsing(fn ($record) => $record->getLocalized('short_description')),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('download_pdf')
                ->label(__('source.actions.download_pdf'))
                ->url(fn ($record) => \URL::temporarySignedRoute('public.signature-sheets.download', now()->addMinutes(30), [
                    'sheet' => $this->sheet->id,
                    'source' => $record->id,
                    'signature_collection_id' => $this->sheet->signature_collection_id,
                ]))
                ->openUrlInNewTab(false),
        ];
    }

    public static function canAccess(): bool
    {
        return request()->hasValidSignature();
    }
}
