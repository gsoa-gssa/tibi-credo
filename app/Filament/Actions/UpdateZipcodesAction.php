<?php

namespace App\Filament\Actions;

/**
 * Filament action to download and import zipcode data from BFS.
 *
 * EXECUTION: Dispatches queued job (non-blocking)
 * 
 * WORKFLOW:
 * 1. User clicks "Update Zipcodes" button in Zipcode management
 * 2. Action checks if job already running
 * 3. Dispatches UpdateZipcodesFromBfs job to queue
 * 4. Job downloads https://public.madd.bfs.admin.ch/ch.zip
 * 5. Extracts data.sqlite to storage/app/bfs/
 * 6. Imports zipcodes into database
 *
 * PREREQUISITES:
 * - Queue worker must be running: php artisan queue:work
 *
 * RELATED:
 * - Job: UpdateZipcodesFromBfs (downloads ZIP + imports zipcodes)
 * - Used by: ImportAddressesFromBfs (reads the downloaded data.sqlite)
 * - Different from: ImportBfsAction (manual commune CSV upload)
 */
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Jobs\UpdateZipcodesFromBfs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UpdateZipcodesAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'updateZipcodes';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Update Zipcodes')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Update Zipcodes from BFS')
            ->modalDescription('This will download the latest zipcode data from the Swiss Federal Statistical Office. This is a background task and may take several minutes.')
            ->modalSubmitActionLabel('Proceed')
            ->action(function () {
                // Check if there's already a job running
                if ($this->isJobRunning()) {
                    Notification::make()
                        ->warning()
                        ->title('Update already in progress')
                        ->body('A zipcode update is already running. Please wait for it to complete.')
                        ->send();
                    return;
                }

                UpdateZipcodesFromBfs::dispatch(Auth::id());

                Notification::make()
                    ->info()
                    ->title('Zipcode update started')
                    ->body('Checking for updates from BFS in the background.')
                    ->send();
            });
    }

    protected function isJobRunning(): bool
    {
        return \DB::table('jobs')
            ->where('payload', 'like', '%UpdateZipcodesFromBfs%')
            ->exists();
    }
}

