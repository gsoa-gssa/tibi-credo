<?php

namespace App\Filament\Actions;

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

