<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class MigrateFilamentCommentsToActivityLog extends Command
{
    protected $signature = 'comments:migrate-to-activity-log {--dry-run : Show what would be migrated without actually migrating}';
    protected $description = 'Migrate filament_comments to activity log entries';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $comments = DB::table('filament_comments')
            ->orderBy('created_at')
            ->get();

        if ($comments->isEmpty()) {
            $this->info('No comments found to migrate.');
            return 0;
        }

        $this->info("Found {$comments->count()} comments to migrate.");
        $this->newLine();

        $migrated = 0;
        foreach ($comments as $comment) {
            $user = User::find($comment->user_id);
            
            $this->line("ID: {$comment->id}");
            $this->line("User: " . ($user ? $user->name : "Unknown (ID: {$comment->user_id})"));
            $this->line("Model: {$comment->subject_type} (ID: {$comment->subject_id})");
            $this->line("Comment: " . strip_tags($comment->comment));
            $this->line("Created: {$comment->created_at}");
            
            if (!$dryRun) {
                try {
                    // Get the model instance
                    $modelClass = $comment->subject_type;
                    
                    // Try to find the model, including soft-deleted ones
                    $model = null;
                    if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                        $model = $modelClass::withTrashed()->find($comment->subject_id);
                    } else {
                        $model = $modelClass::find($comment->subject_id);
                    }
                    
                    if (!$model) {
                        $this->error("  ❌ Model not found (checked with" . (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass)) ? ' withTrashed' : 'out soft deletes') . ")!");
                        $this->newLine();
                        continue;
                    }

                    // Create activity log entry
                    activity()
                        ->performedOn($model)
                        ->causedBy($user)
                        ->event('comment')
                        ->withProperties(['migrated_from_filament_comments' => true, 'original_id' => $comment->id])
                        ->createdAt(new \DateTime($comment->created_at))
                        ->log($comment->comment);
                    
                    $this->info("  ✅ Migrated successfully");
                    $migrated++;
                } catch (\Exception $e) {
                    $this->error("  ❌ Error: " . $e->getMessage());
                }
            } else {
                $this->info("  ⏭️  Would migrate");
            }
            
            $this->newLine();
        }

        if ($dryRun) {
            $this->info("DRY RUN COMPLETE - {$comments->count()} comments would be migrated.");
            $this->info("Run without --dry-run to perform the migration.");
        } else {
            $this->info("MIGRATION COMPLETE - {$migrated} of {$comments->count()} comments migrated successfully.");
        }

        return 0;
    }
}
