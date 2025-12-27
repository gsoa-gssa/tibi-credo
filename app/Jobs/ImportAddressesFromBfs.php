<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Address;
use App\Models\Commune;
use App\Models\Zipcode;

/**
 * Import official addresses from BFS (Swiss Federal Statistical Office) SQLite database.
 *
 * EXECUTION: Queued background job (requires queue worker)
 * 
 * DEPENDENCIES:
 * - Requires UpdateZipcodesFromBfs to run first (downloads data.sqlite)
 * - Reads from: storage/app/bfs/data.sqlite
 * - Requires populated communes and zipcodes tables for mapping
 *
 * DATA SOURCE:
 * - Filters DOFFADR='1' (official addresses only)
 * - Excludes expired entries (DEXPDAT/GEXPDAT checks)
 * - Expected ~3.4M addresses from entrance + building tables
 *
 * PERFORMANCE:
 * - Batch size: 5000 records
 * - Disables indexes before import, re-enables after
 * - Timeout: 3600 seconds (1 hour)
 *
 * USAGE:
 * ImportAddressesFromBfs::dispatch($userId, $dryRun = false);
 */
class ImportAddressesFromBfs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CHUNK_SIZE = 10000;
    protected const SQLITE_PATH = 'storage/app/bfs/data.sqlite';

    public function __construct(
        public int $userId,
        public bool $dryRun = false
    ) {}

    public function handle(): void
    {
        Log::info('ImportAddressesFromBfs starting', [
            'userId' => $this->userId,
            'dryRun' => $this->dryRun
        ]);

        $sqlitePath = storage_path('app/bfs/data.sqlite');
        if (!file_exists($sqlitePath)) {
            Log::error('SQLite file not found', ['path' => $sqlitePath]);
            $this->notifyError('SQLite file not found. Please run Update Zipcodes first.');
            return;
        }

        // Open SQLite connection just to count rows
        $sqlite = new \SQLite3($sqlitePath, SQLITE3_OPEN_READONLY);
        $countQuery = "
            SELECT COUNT(*) as total
            FROM entrance e
            JOIN building b ON b.EGID = e.EGID
            WHERE e.DOFFADR = '1'
        ";
        $countResult = $sqlite->query($countQuery);
        $totalRows = $countResult->fetchArray(SQLITE3_ASSOC)['total'];
        $sqlite->close();
        Log::info('Total addresses to import', ['total' => $totalRows]);

        if ($this->dryRun) {
            // For dry run, build mappings and notify only
            $communeMap = Commune::bfsToPk();
            $zipcodeMap = $this->buildZipcodeMap();
            $this->notifyDryRun($totalRows, $communeMap, $zipcodeMap);
            return;
        }

        // Disable indexes before import
        Log::info('Disabling indexes for bulk import');
        \App\Models\Address::disableIndexes();

        // Truncate existing addresses
        Log::info('Truncating addresses table');
        DB::table('addresses')->truncate();

        // Dispatch chunk jobs in a chain (serial execution), ending with re-enable indexes job
        $jobs = [];
        for ($offset = 0; $offset < $totalRows; $offset += self::CHUNK_SIZE) {
            $jobs[] = new \App\Jobs\ImportAddressesFromBfsChunk($offset, self::CHUNK_SIZE, $this->userId);
        }
        // Add the enable indexes job at the end
        $jobs[] = new \App\Jobs\ImportAddressesFromBfsEnableIndexes($this->userId);
        if (!empty($jobs)) {
            $first = array_shift($jobs);
            $first::withChain($jobs)->dispatch($first->offset, $first->chunkSize, $first->userId);
        }

        Log::info('Dispatched all chunk jobs and enable-indexes job', [
            'totalRows' => $totalRows,
            'chunkSize' => self::CHUNK_SIZE,
            'chunks' => ceil($totalRows / self::CHUNK_SIZE),
            'finalJob' => 'ImportAddressesFromBfsEnableIndexes'
        ]);
    }

    /**
     * Build mapping from zipcode + name to zipcode_id
     */
    protected function buildZipcodeMap(): array
    {
        return Zipcode::all()
            ->mapWithKeys(function ($zipcode) {
                $key = $zipcode->code . '|' . $zipcode->name;
                return [$key => $zipcode->id];
            })
            ->toArray();
    }

    /**
     * Send dry run notification
     */
    protected function notifyDryRun(int $totalRows, array $communeMap, array $zipcodeMap): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        Notification::make()
            ->success()
            ->title('Address Import Dry Run')
            ->body("Dry run completed successfully.\n\n" .
                   "Total addresses found: " . number_format($totalRows) . "\n" .
                   "Commune mappings: " . number_format(count($communeMap)) . "\n" .
                   "Zipcode mappings: " . number_format(count($zipcodeMap)) . "\n\n" .
                   "No data was written to the database.")
            ->sendToDatabase($user);
    }

    /**
     * Send success notification
     */
    protected function notifyProcessed(int $processed, int $skipped): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        Notification::make()
            ->success()
            ->title('Address Import Completed')
            ->body("Successfully imported " . number_format($processed) . " addresses." .
                   ($skipped > 0 ? "\n\nSkipped " . number_format($skipped) . " addresses (missing commune or zipcode mapping)." : ""))
            ->sendToDatabase($user);
    }

    /**
     * Send error notification
     */
    protected function notifyError(string $message): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        Notification::make()
            ->danger()
            ->title('Address Import Failed')
            ->body($message)
            ->sendToDatabase($user);
    }
}
