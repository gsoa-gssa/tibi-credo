<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Zipcode;
use App\Models\Commune;

class UpdateZipcodesFromBfs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const BFS_ZIP_URL = 'https://public.madd.bfs.admin.ch/ch.zip';
    protected const STORAGE_PATH = 'storage/app/bfs/ch.zip';
    protected const MAX_AGE_SECONDS = 86400; // 1 day

    public function __construct(
        public int $userId
    ) {}

    public function handle(): void
    {
        Log::info('UpdateZipcodesFromBfs starting', ['userId' => $this->userId]);
        
        $zipPath = storage_path('app/bfs/ch.zip');
        $shouldDownload = false;
        $downloadReason = null;
        $wasDownloaded = false; // Track if we actually downloaded

        // Check if file exists and how old it is
        if (!file_exists($zipPath)) {
            $shouldDownload = true;
            $downloadReason = 'File does not exist locally';
            Log::info('File does not exist');
        } else {
            $fileAge = time() - filemtime($zipPath);
            Log::info('File exists', ['age_seconds' => $fileAge, 'max_age' => self::MAX_AGE_SECONDS]);
            
            if ($fileAge > self::MAX_AGE_SECONDS) {
                // File is older than 1 day, check remote version
                try {
                    Log::info('Checking remote version');
                    $response = Http::head(self::BFS_ZIP_URL);
                    
                    if ($response->successful()) {
                        $remoteLastModified = $response->header('Last-Modified');
                        
                        if ($remoteLastModified) {
                            $remoteTime = strtotime($remoteLastModified);
                            $localTime = filemtime($zipPath);
                            
                            Log::info('Comparing versions', [
                                'remote' => $remoteLastModified,
                                'remote_timestamp' => $remoteTime,
                                'local_timestamp' => $localTime
                            ]);
                            
                            if ($remoteTime > $localTime) {
                                $shouldDownload = true;
                                $downloadReason = 'Remote version is newer';
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to check remote version', ['error' => $e->getMessage()]);
                    $this->notifyError('Failed to check remote version: ' . $e->getMessage());
                    return;
                }
            }
        }

        if ($shouldDownload) {
            Log::info('Starting download', ['reason' => $downloadReason]);
            $this->downloadFile($downloadReason);
            $wasDownloaded = true;
        } else {
            Log::info('Skipping download - file is up to date');
            $this->notifySkipped();
        }

        // Check if we need to extract data.sqlite
        $sqlitePath = storage_path('app/bfs/data.sqlite');
        $shouldExtract = $wasDownloaded; // Only extract if we just downloaded
        
        if (!$wasDownloaded && !file_exists($sqlitePath)) {
            $shouldExtract = true; // Also extract if file doesn't exist
        }

        if ($shouldExtract) {
            // unzip the file using system command for large files
            Log::info('Starting ZIP extraction', ['zipPath' => $zipPath]);
            try {
                $extractPath = storage_path('app/bfs/');
                
                // Extract only the data.sqlite file from the ZIP
                $cmd = escapeshellcmd("unzip -o " . escapeshellarg($zipPath) . " 'data.sqlite' -d " . escapeshellarg($extractPath));
                Log::info('Running unzip command for data.sqlite', ['cmd' => $cmd]);
                
                passthru($cmd, $returnCode);
                
                if ($returnCode !== 0) {
                    Log::error('Unzip failed', ['return_code' => $returnCode]);
                    $this->notifyError('ZIP extraction failed: unzip returned code ' . $returnCode);
                    return;
                }
                
                Log::info('ZIP extraction completed successfully');
            } catch (\Exception $e) {
                Log::error('ZIP extraction error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $this->notifyError('ZIP extraction failed: ' . $e->getMessage());
                return;
            }
            
            $this->notifyExtracted();
        }

        // delete existing zipcodes
        Zipcode::query()->delete();

        // open the sqlite database and update zipcodes
        try {
            $db = new \SQLite3($sqlitePath);
            
            $query = <<<SQL
                SELECT 
                    e.DPLZ4,
                    e.DPLZNAME,
                    b.GGDENR,
                    b.GGDENAME,
                    COUNT(*) AS count
                FROM 
                    entrance e
                JOIN 
                    building b ON e.EGID = b.EGID
                WHERE 
                    e.DOFFADR = '1'
                GROUP BY 
                    e.DPLZ4, e.DPLZNAME, b.GGDENR, b.GGDENAME
                SQL;
            
            $results = $db->query($query);
            Log::info('SQLite query completed');
            
            // Build a map of commune officialId => commune_id for fast lookup
            $communeMap = Commune::pluck('id', 'officialId')->toArray();
            
            $batch = [];
            $batchSize = 1000;
            $totalProcessed = 0;
            
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                $communeId = $communeMap[$row['GGDENR']] ?? null;
                
                if ($communeId) {
                    $batch[] = [
                        'code' => $row['DPLZ4'],
                        'name' => $row['DPLZNAME'],
                        'commune_id' => $communeId,
                        'number_of_dwellings' => $row['count'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (count($batch) >= $batchSize) {
                    DB::table('zipcodes')->insert($batch);
                    $totalProcessed += count($batch);
                    Log::info('Batch inserted', ['count' => count($batch), 'total' => $totalProcessed]);
                    $batch = [];
                }
            }
            
            // Insert remaining records
            if (!empty($batch)) {
                DB::table('zipcodes')->insert($batch);
                $totalProcessed += count($batch);
                Log::info('Final batch inserted', ['count' => count($batch), 'total' => $totalProcessed]);
            }
            
            $db->close();
            
            Log::info('SQLite processing completed', ['total_records' => $totalProcessed]);
            $this->notifyProcessed($totalProcessed);
        } catch (\Exception $e) {
            Log::error('SQLite query failed', ['error' => $e->getMessage()]);
            $this->notifyError('SQLite query failed: ' . $e->getMessage());
            return;
        }
    }

    protected function downloadFile(?string $reason): void
    {
        try {
            $zipPath = storage_path('app/bfs/ch.zip');
            
            // Create directory if it doesn't exist
            @mkdir(dirname($zipPath), 0755, true);

            // Get file size from remote before downloading
            $headResponse = Http::timeout(30)->head(self::BFS_ZIP_URL);
            $contentLength = (int) $headResponse->header('Content-Length', 0);

            if ($contentLength > 0) {
                $this->notifyDownloadStarting($contentLength);
            }

            // Stream download directly to disk to handle large files
            $tempPath = $zipPath . '.tmp';
            $handle = fopen($tempPath, 'w');

            if ($handle === false) {
                throw new \RuntimeException("Unable to open file for writing: $tempPath");
            }

            try {
                Http::timeout(300)->sink($handle)->get(self::BFS_ZIP_URL);
                fclose($handle);

                // Verify file was written and is valid
                if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                    throw new \RuntimeException('Downloaded file is empty');
                }

                // Atomic move to final location
                rename($tempPath, $zipPath);

                $fileSize = filesize($zipPath);
                $this->notifySuccess($reason, $fileSize);
            } catch (\Exception $e) {
                fclose($handle);
                @unlink($tempPath);
                throw $e;
            }
        } catch (\Exception $e) {
            $this->notifyError('Download failed: ' . $e->getMessage());
        }
    }

    protected function notifySuccess(string $reason, int $fileSize): void
    {
        $message = "Downloaded ({$reason}). File size: " . number_format($fileSize / 1024 / 1024, 2) . ' MB';
        
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(
                Notification::make()
                    ->success()
                    ->title('Zipcodes updated successfully')
                    ->body($message)
                    ->toDatabase()
            );
        }
    }

    protected function notifyDownloadStarting(int $bytes): void
    {
        $sizeMb = number_format($bytes / 1024 / 1024, 2);
        
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(
                Notification::make()
                    ->info()
                    ->title('Downloading zipcodes')
                    ->body("Downloading {$sizeMb} MB from BFS...")
                    ->toDatabase()
            );
        }
    }

    protected function notifySkipped(): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(
                Notification::make()
                    ->success()
                    ->title('Zipcodes check complete')
                    ->body('Local file is up to date')
                    ->toDatabase()
            );
        }
    }

    protected function notifyError(string $error): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(
                Notification::make()
                    ->danger()
                    ->title('Zipcode update failed')
                    ->body($error)
                    ->toDatabase()
            );
        }
    }

    protected function notifyExtracted(): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(
                Notification::make()
                    ->success()
                    ->title('Data extracted')
                    ->body('ZIP file has been extracted successfully')
                    ->toDatabase()
            );
        }
    }

    protected function notifyProcessed(int $count = 0): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(
                Notification::make()
                    ->success()
                    ->title('Data processed')
                    ->body("SQLite database processed successfully. {$count} zipcodes imported.")
                    ->toDatabase()
            );
        }
    }
}
