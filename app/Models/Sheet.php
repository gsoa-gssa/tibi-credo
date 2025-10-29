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
        $this->fixLabel();

        // if the label has the right format, add space for better readability
        if (preg_match('/^(VOX-)?(\d+)$/', $this->label, $matches)) {
            $prefix = $matches[1] ?? '';
            $number = $matches[2];
            return $prefix . preg_replace('/(\d{3})(\d{3})/', '$1 $2', sprintf('%06d', $number));
        } else {
            return $this->label; // Return original label if it doesn't match expected format
        }
    }

    /**
     * Fix the label format: no whitespace is saved to db, capitalize VOX, etc.
     */
    public function fixLabel(): void
    {
        $label_old = $this->label;

        // remove all whitespace
        $this->label = preg_replace('/\s+/', '', $this->label);

        // remove leading zeros
        $this->label = preg_replace('/^0+/', '', $this->label);

        // if it has no dash after vox, add it
        if (preg_match('/^VOX(\d+)$/ui', $this->label, $matches)) {
            $this->label = 'VOX-' . $matches[1];
        } elseif (preg_match('/^VOX[^0-9](\d+)$/ui', $this->label, $matches)) {
            // if the wrong unicode symbol is used after vox, replace it with a dash
            $this->label = 'VOX-' . $matches[1];
        }

        if($label_old !== $this->label) {
            $this->saveQuietly();
        }
    }

    /**
     * Get Contacts for this Sheet
     */
    public function contacts() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function checkMaeppliExistsIfNotNull(): bool
    {
        if ($this->maeppli_id === null) {
            return true;
        }

        return Maeppli::where('id', $this->maeppli_id)->exists();
    }

    public static function findSheetsWithInvalidMaeppli()
    {
        return static::whereNotNull('maeppli_id')
            ->whereDoesntHave('maeppli')
            ->get();
    }
}
