<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SyncJsonTranslations extends Command
{
    protected $signature = 'translations:sync-json';
    protected $description = 'Sync JSON translation files with the master (de.json)';

    public function handle()
    {
        $langPath = base_path('lang');
        $masterFile = $langPath . '/de.json';
        $secondaryFiles = ['fr.json', 'it.json', 'en.json'];

        if (!file_exists($masterFile)) {
            $this->error('Master file de.json not found.');
            return 1;
        }

        $master = json_decode(file_get_contents($masterFile), true);
        if (!is_array($master)) {
            $this->error('Master file de.json is not valid JSON.');
            return 1;
        }

        foreach ($secondaryFiles as $file) {
            $filePath = $langPath . '/' . $file;
            if (!file_exists($filePath)) {
                $this->warn("File $file not found, creating new.");
                $secondary = [];
            } else {
                $secondary = json_decode(file_get_contents($filePath), true) ?? [];
            }

            // Remove keys not in master
            $secondary = array_intersect_key($secondary, $master);
            // Add missing keys from master
            foreach ($master as $key => $value) {
                if (!array_key_exists($key, $secondary)) {
                    $secondary[$key] = $value;
                }
            }
            // Sort keys
            ksort($secondary);
            file_put_contents($filePath, json_encode($secondary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Synced $file");
        }
        $this->info('All secondary translation files are now synced with de.json.');
        return 0;
    }
}
