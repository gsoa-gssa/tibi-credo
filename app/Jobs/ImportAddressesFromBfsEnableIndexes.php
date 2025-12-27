<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImportAddressesFromBfsEnableIndexes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        \App\Models\Address::enableIndexes();
        Log::info('Re-enabled indexes after import');

        // Send final success notification
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Address Import Completed')
                ->body('All address chunks imported and indexes re-enabled.')
                ->sendToDatabase($user);
        }
    }
}
