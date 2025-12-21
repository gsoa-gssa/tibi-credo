<?php

namespace App\Filament\Actions;

use App\Models\Commune;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

class ExportAuthorityCandidatesBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'export_authority_addresses';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('commune.bulkActions.export_authority_addresses'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (Collection $records) {
                $total = $records->count();
                $skipped = 0;
                $exported = 0;

                $handle = fopen('php://temp', 'r+');
                fputcsv($handle, ['Kunden-ID', 'authority_candidate_name', 'Strasse', 'Hausnummer', 'PLZ', 'Ort']);
                
                foreach ($records as $commune) {
                    // Skip communes without place
                    if (empty($commune->authority_address_place)) {
                        $skipped++;
                        continue;
                    }

                    fputcsv($handle, [
                        $commune->id,
                        __('commune.authority_candidate_prefix', ['name' => $commune->name], $commune->lang),
                        $commune->authority_address_street,
                        $commune->authority_address_house_number,
                        $commune->authority_address_postcode,
                        $commune->authority_address_place,
                    ]);
                    $exported++;
                }
                
                rewind($handle);

                Notification::make()
                    ->success()
                    ->title(__('commune.bulkActions.export_authority_addresses_complete'))
                    ->body(__('commune.bulkActions.export_authority_addresses_stats', [
                        'total' => $total,
                        'exported' => $exported,
                        'skipped' => $skipped,
                    ]))
                    ->send();
                
                return response()->streamDownload(function () use ($handle) {
                    fpassthru($handle);
                }, 'gemeinde-adressen.csv', [
                    'Content-Type' => 'text/csv',
                ]);
            });
    }
}
