<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Maeppli extends Model
{
    use SoftDeletes, LogsActivity, HasFilamentComments;

    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public static function boot ()
    {
        parent::boot();

        static::deleting(function ($maeppli) {
            foreach ($maeppli->sheets as $sheet) {
                $sheet->maeppli_id = null;
                $sheet->save();
            }
        });

        // Enforce same canton for all Maepplis inside a Box when assigning box_id
        static::saving(function ($maeppli) {
            if ($maeppli->isDirty('box_id') && $maeppli->box_id) {
                $box = Box::with('maepplis.commune.canton')->find($maeppli->box_id);
                if ($box) {
                    $existing = $box->maepplis->first();
                    if ($existing && $existing->commune && $maeppli->commune) {
                        $existingCanton = optional(optional($existing->commune)->canton)->label;
                        $newCanton = optional(optional($maeppli->commune)->canton)->label;
                        if ($existingCanton && $newCanton && $existingCanton !== $newCanton) {
                            throw new \Exception('Cannot assign Maeppli to Box: canton mismatch (' . $newCanton . ' vs ' . $existingCanton . ').');
                        }
                    }
                }
            }
        });
    }
}
