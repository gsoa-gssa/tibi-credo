<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

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
    }
}
