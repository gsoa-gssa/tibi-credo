<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class MigrateSignatureCollectionIdOnComments extends Command
{
    protected $signature = 'activitylog:migrate-signature-collection-id {--dry-run}';
    protected $description = 'Set signature_collection_id=2 on comment activities for communes where it is missing or null.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $query = Activity::where('event', 'comment')
            ->where('subject_type', \App\Models\Commune::class)
            ->where(function ($q) {
                $q->whereNull('properties->signature_collection_id')
                  ->orWhere('properties->signature_collection_id', null);
            });

        $count = $query->count();
        $this->info("Found $count comment activities to update.");

        if ($dryRun) {
            $this->info('Dry run: no changes made. The following comments would be updated:');
            $query->chunkById(100, function ($activities) {
                foreach ($activities as $activity) {
                    $this->line('- ID: ' . $activity->id . ' - ' . ($activity->description ?? '[no description]'));
                }
            });
            return 0;
        }

        $updated = 0;
        $query->chunkById(100, function ($activities) use (&$updated) {
            foreach ($activities as $activity) {
                $properties = $activity->properties ?? [];
                $properties['signature_collection_id'] = 2;
                $activity->properties = $properties;
                $activity->save();
                $updated++;
            }
        });

        $this->info("Updated $updated activities.");
        return 0;
    }
}
