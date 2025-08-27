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
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class Sheet extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasFilamentComments;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'commune_id' => 'integer',
        'batch_id' => 'integer',
        "numerator_id" => "integer",
        "vox" => "boolean",
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

    public function maeppli(): BelongsTo
    {
        return $this->belongsTo(Maeppli::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Hook into the model's boot method.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($sheet) {
            if ($sheet->maeppli_id) {
                $sheet->status = 'processed';
            }
            else if ($sheet->batch_id) {
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
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    /**
     * Get formatted label
     */
    public function getLabel()
    {
        // Remove 'VOX' prefix if present and get the number part
        if (preg_match('/^(VOX-)?(\d+)$/', $this->label, $matches)) {
            $prefix = $matches[1] ?? '';
            $number = $matches[2];
            return $prefix . preg_replace('/(\d{3})(\d{3})/', '$1 $2', sprintf('%06d', $number));
        } else {
            return $this->label; // Return original label if it doesn't match expected format
        }
    }

    public function fixLabel(): void
    {
        # if label has only digits, return early
        if (preg_match('/^\d+$/', $this->label)) {
            return;
        }

        # remove all whitespace
        $this->label = preg_replace('/\s+/', '', $this->label);

        # otherwise it should match vox[nondigit][0-9]+
        if (preg_match('/^VOX[^0-9](\d+)$/u', $this->label, $matches)) {
            $this->label = 'VOX-' . $matches[1];
        }

        $this->label = $this->getLabel();
        $this->label = preg_replace('/\s+/', '', $this->label);

        $this->saveQuietly();
    }

    /**
     * Get Contacts for this Sheet
     */
    public function contacts() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
