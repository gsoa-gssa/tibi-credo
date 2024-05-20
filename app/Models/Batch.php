<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $casts = [
        'id' => 'integer',
        'sendDate' => 'date',
        'commune_id' => 'integer',
    ];

    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * Hook into the model's boot method.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($batch) {
            $sheets = \App\Models\Sheet::where("commune_id", $batch->commune_id)->where("status", "recorded")->get();
            $sheets->each(function ($sheet) use ($batch) {
                $sheet->batch_id = $batch->id;
                $sheet->status = 'added2batch';
                $sheet->save();
            });
        });

        static::deleting(function ($batch) {
            $sheets = \App\Models\Sheet::where("batch_id", $batch->id)->get();
            $sheets->each(function ($sheet) {
                $sheet->batch_id = null;
                $sheet->status = 'recorded';
                $sheet->save();
            });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
