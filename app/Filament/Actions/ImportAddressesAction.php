<?php

namespace App\Filament\Actions;

/**
 * Filament action to trigger address import from BFS database.
 *
 * EXECUTION: Dispatches queued job (non-blocking)
 * 
 * WORKFLOW:
 * 1. User clicks "Import Addresses" button in Zipcode management
 * 2. Action checks if job already running
 * 3. Dispatches ImportAddressesFromBfs job to queue
 * 4. Shows notification immediately (job runs in background)
 *
 * PREREQUISITES:
 * - Queue worker must be running: php artisan queue:work
 * - UpdateZipcodesFromBfs must have completed (to download data.sqlite)
 *
 * RELATED:
 * - Job: ImportAddressesFromBfs (does the actual import)
 * - Different from: ImportBfsAction (manual commune CSV upload)
 */
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Jobs\ImportAddressesFromBfs;
use Illuminate\Support\Facades\Auth;

class ImportAddressesAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_addresses';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import Addresses')
            ->icon('heroicon-o-map-pin')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Import Addresses from BFS')
            ->modalDescription('This will import all official addresses from the BFS database. This is a background task and may take several minutes.')
            ->modalSubmitActionLabel('Import Addresses')
            ->extraModalFooterActions([
                Action::make('dry_run')
                    ->label('Dry Run')
                    ->color('gray')
                    ->action(function () {
                        if ($this->isJobRunning()) {
                            Notification::make()
                                ->warning()
                                ->title('Import already in progress')
                                ->body('An address import is already running. Please wait for it to complete.')
                                ->send();
                            return;
                        }

                        ImportAddressesFromBfs::dispatch(Auth::id(), true);

                        Notification::make()
                            ->info()
                            ->title('Dry Run Started')
                            ->body('Running dry run in the background. You will receive a notification with the results.')
                            ->send();
                    }),
            ])
            ->action(function () {
                if ($this->isJobRunning()) {
                    Notification::make()
                        ->warning()
                        ->title('Import already in progress')
                        ->body('An address import is already running. Please wait for it to complete.')
                        ->send();
                    return;
                }

                ImportAddressesFromBfs::dispatch(Auth::id(), false);

                Notification::make()
                    ->info()
                    ->title('Address Import Started')
                    ->body('Importing addresses from BFS in the background.')
                    ->send();
            });
    }

    protected function isJobRunning(): bool
    {
        return \DB::table('jobs')
            ->where('payload', 'like', '%ImportAddressesFromBfs%')
            ->exists();
    }
}
