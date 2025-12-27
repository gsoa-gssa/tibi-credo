<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Commune;
use App\Models\Zipcode;

class ImportAddressesFromBfsChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $offset;
    public int $chunkSize;
    public int $userId;

    public function __construct(int $offset, int $chunkSize, int $userId)
    {
        $this->offset = $offset;
        $this->chunkSize = $chunkSize;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        try {
            $sqlitePath = storage_path('app/bfs/data.sqlite');
            $sqlite = new \SQLite3($sqlitePath, SQLITE3_OPEN_READONLY);
            $sqlite->busyTimeout(30000);

            $communeMap = Commune::bfsToPk();
            $zipcodeMap = Zipcode::all()
                ->mapWithKeys(fn($z) => [$z->code . '|' . $z->name => $z->id])
                ->toArray();

            $query = "
                SELECT
                    b.GGDENR AS commune_bfs_id,
                    e.DPLZ4 AS zipcode,
                    e.DPLZNAME AS place_name,
                    e.STRNAME AS street_name,
                    e.DEINR AS street_number
                FROM entrance e
                JOIN building b ON b.EGID = e.EGID
                WHERE e.DOFFADR = '1'
                ORDER BY e.EDID
                LIMIT {$this->chunkSize} OFFSET {$this->offset}
            ";

            $result = $sqlite->query($query);
            if (!$result) {
                throw new \Exception('SQLite query failed: ' . $sqlite->lastErrorMsg());
            }

            $batch = [];
            $processed = 0;
            $skipped = 0;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $communeId = $communeMap[(int)$row['commune_bfs_id']] ?? null;
                $zipcodeId = $zipcodeMap[$row['zipcode'] . '|' . $row['place_name']] ?? null;
                if (!$communeId || !$zipcodeId) {
                    $skipped++;
                    continue;
                }

                $batch[] = [
                    'commune_id' => $communeId,
                    'zipcode_id' => $zipcodeId,
                    'street_name' => $row['street_name'] ?: null,
                    'street_number' => $row['street_number'] ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($batch) > 0) {
                DB::table('addresses')->insert($batch);
                $processed += count($batch);
            }

            $sqlite->close();
            Log::info('Chunk import completed', [
                'offset' => $this->offset,
                'chunkSize' => $this->chunkSize,
                'processed' => $processed,
                'skipped' => $skipped
            ]);

            // Send success notification for this chunk
            $this->notifyChunkSuccess($processed, $skipped);
        } catch (\Exception $e) {
            Log::error('Chunk import failed', [
                'offset' => $this->offset,
                'chunkSize' => $this->chunkSize,
                'error' => $e->getMessage()
            ]);
            $this->notifyChunkError($e->getMessage());
            throw $e;
        }
    }

    protected function notifyChunkSuccess(int $processed, int $skipped): void
    {
        $user = \App\Models\User::find($this->userId);
        if (!$user) return;
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Address Import Chunk Completed')
            ->body("Chunk (offset: {$this->offset}) imported {$processed} addresses. Skipped: {$skipped}.")
            ->sendToDatabase($user);
    }

    protected function notifyChunkError(string $message): void
    {
        $user = \App\Models\User::find($this->userId);
        if (!$user) return;
        \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Address Import Chunk Failed')
            ->body("Chunk (offset: {$this->offset}) failed: $message")
            ->sendToDatabase($user);
    }
}
