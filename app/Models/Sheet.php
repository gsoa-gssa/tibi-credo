<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Sheet extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'commune_id' => 'integer',
        'batch_id' => 'integer',
        "numerator_id" => "integer",
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function numerator(): BelongsTo
    {
        return $this->belongsTo(Numerator::class);
    }

    /**
     * Hook into the model's boot method.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($sheet) {
            if ($sheet->batch_id) {
                $sheet->status = 'added2batch';
            } else {
                $sheet->status = 'recorded';
            }
        });

        static::deleting(function ($sheet) {
            if ($sheet->batch_id) {
                Notification::make()
                    ->info()
                    ->title("Sheet {$sheet->numerator_id} is part of a batch and cannot be deleted. <a href=\"/batches/{$sheet->batch_id}\" class=\"underline\">View Batch</a>")
                    ->send();
                return false;
            }

            // Find the scan and move it back to unassigned folder
            $scan = glob(storage_path('app/public/sheet-scans/assigned/' . $sheet->numerator_id . '_*'));
            if (count($scan) === 0) {
                Notification::make()
                    ->info()
                    ->title("No scan found for sheet {$sheet->numerator_id}. This sheet is fishy. Please contact support.")
                    ->send();
                return false;
            }
            $scan = $scan[0];
            $filename = File::basename($scan);
            $oldname = explode("_", $filename)[1];
            Storage::move('public/sheet-scans/assigned/' . $filename, 'public/sheet-scans/unassigned/' . $oldname);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
