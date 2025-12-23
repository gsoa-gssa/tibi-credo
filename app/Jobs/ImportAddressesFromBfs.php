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

    protected const BATCH_SIZE = 5000;
    protected const SQLITE_PATH = 'storage/app/bfs/data.sqlite';

    public function __construct(
        public int $userId,
        public bool $dryRun = false
    ) {}

    /**
     * Set timeout for long-running job
     */
    public function timeout(): int
    {
        return 7200; // 2 hours
    }

    public function handle(): void
    {
        Log::info('ImportAddressesFromBfs starting', [
            'userId' => $this->userId,
            'dryRun' => $this->dryRun
        ]);

        // Disable query logging to save memory during bulk import
        DB::disableQueryLog();

        $sqlitePath = storage_path('app/bfs/data.sqlite');

        if (!file_exists($sqlitePath)) {
            Log::error('SQLite file not found', ['path' => $sqlitePath]);
            $this->notifyError('SQLite file not found. Please run Update Zipcodes first.');
            return;
        }

        try {
            // Open SQLite connection
            $sqlite = new \SQLite3($sqlitePath, SQLITE3_OPEN_READONLY);
            $sqlite->busyTimeout(30000);

            // Build commune mapping (BFS ID => PK)
            $communeMap = Commune::bfsToPk();
            Log::info('Commune mapping built', ['count' => count($communeMap)]);

            // Build zipcode mapping (code + place => PK)
            $zipcodeMap = $this->buildZipcodeMap();
            Log::info('Zipcode mapping built', ['count' => count($zipcodeMap)]);

            // Count total rows to process
            $countQuery = "
                SELECT COUNT(*) as total
                FROM entrance e
                JOIN building b ON b.EGID = e.EGID
                WHERE e.DOFFADR = '1'
            ";
            $countResult = $sqlite->query($countQuery);
            $totalRows = $countResult->fetchArray(SQLITE3_ASSOC)['total'];
            Log::info('Total addresses to import', ['total' => $totalRows]);

            if ($this->dryRun) {
                $this->notifyDryRun($totalRows, $communeMap, $zipcodeMap);
                $sqlite->close();
                return;
            }

            // Disable indexes before import
            Log::info('Disabling indexes for bulk import');
            $this->disableIndexes();

            // Truncate existing addresses
            Log::info('Truncating addresses table');
            DB::table('addresses')->truncate();

            // Process in chunks to avoid loading entire result set into memory
            $chunkSize = 50000;  // SQLite LIMIT per query
            $offset = 0;
            $totalProcessed = 0;
            $skipped = 0;
            $lastNotification = 0;

            while (true) {
                // Query chunk from SQLite
                $query = "
                    SELECT
                        b.GGDENR          AS commune_bfs_id,
                        e.DPLZ4           AS zipcode,
                        e.DPLZNAME        AS place_name,
                        e.STRNAME         AS street_name,
                        e.DEINR           AS street_number
                    FROM entrance e
                    JOIN building b ON b.EGID = e.EGID
                    WHERE e.DOFFADR = '1'
                    ORDER BY e.EDID
                    LIMIT $chunkSize OFFSET $offset
                ";

                $result = $sqlite->query($query);
                if (!$result) {
                    throw new \Exception('SQLite query failed: ' . $sqlite->lastErrorMsg());
                }

                $chunkCount = 0;
                $batch = [];
                
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $chunkCount++;

                    // Map commune BFS ID to internal PK
                    $communeBfsId = (int) $row['commune_bfs_id'];
                    $communeId = $communeMap[$communeBfsId] ?? null;

                    if (!$communeId) {
                        $skipped++;
                        continue;
                    }

                    // Map zipcode + place to zipcode_id
                    $zipcodeKey = $row['zipcode'] . '|' . $row['place_name'];
                    $zipcodeId = $zipcodeMap[$zipcodeKey] ?? null;

                    if (!$zipcodeId) {
                        $skipped++;
                        continue;
                    }

                    // Prepare record (pre-calculate now() once per batch)
                    $batch[] = [
                        'commune_id' => $communeId,
                        'zipcode_id' => $zipcodeId,
                        'street_name' => !empty($row['street_name']) ? $row['street_name'] : null,
                        'street_number' => !empty($row['street_number']) ? $row['street_number'] : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert immediately when batch reaches size (don't accumulate)
                    if (count($batch) >= self::BATCH_SIZE) {
                        DB::table('addresses')->insert($batch);
                        $totalProcessed += count($batch);
                        $batch = [];
                        gc_collect_cycles();

                        // Notify every 100k records
                        if ($totalProcessed - $lastNotification >= 100000) {
                            Log::info('Import progress', ['processed' => $totalProcessed, 'skipped' => $skipped]);
                            $lastNotification = $totalProcessed;
                        }
                    }
                }

                // Insert any remaining records from this chunk
                if (count($batch) > 0) {
                    DB::table('addresses')->insert($batch);
                    $totalProcessed += count($batch);
                    gc_collect_cycles();
                }

                // If this chunk returned fewer rows than the limit, we're done
                if ($chunkCount < $chunkSize) {
                    break;
                }

                // Move to next chunk
                $offset += $chunkSize;
                gc_collect_cycles();
            }

            $sqlite->close();

            // Re-enable indexes after import
            Log::info('Re-enabling indexes');
            $this->enableIndexes();

            Log::info('Import completed', [
                'processed' => $totalProcessed,
                'skipped' => $skipped
            ]);

            $this->notifyProcessed($totalProcessed, $skipped);

        } catch (\Exception $e) {
            Log::error('ImportAddressesFromBfs failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->notifyError($e->getMessage());
            
            // Try to re-enable indexes even if import failed
            try {
                $this->enableIndexes();
            } catch (\Exception $indexError) {
                Log::error('Failed to re-enable indexes', ['error' => $indexError->getMessage()]);
            }
        }
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
     * Disable indexes for bulk insert performance
     */
    protected function disableIndexes(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE addresses DISABLE KEYS');
        } elseif ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_zipcode');
            DB::statement('DROP INDEX IF EXISTS idx_commune');
            DB::statement('DROP INDEX IF EXISTS idx_zip_street');
            DB::statement('DROP INDEX IF EXISTS idx_commune_street');
        }
    }

    /**
     * Re-enable indexes after bulk insert
     */
    protected function enableIndexes(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE addresses ENABLE KEYS');
        } elseif ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_zipcode ON addresses(zipcode_id)');
            DB::statement('CREATE INDEX idx_commune ON addresses(commune_id)');
            DB::statement('CREATE INDEX idx_zip_street ON addresses(zipcode_id, street_name)');
            DB::statement('CREATE INDEX idx_commune_street ON addresses(commune_id, street_name)');
        }
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
