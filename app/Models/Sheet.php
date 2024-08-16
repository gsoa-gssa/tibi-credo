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
        return str_starts_with($this->label, 'VOX') ? $this->label : sprintf('%06d', $this->label);
    }

    /**
     * Get Contacts for this Sheet
     */
    public function contacts() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
