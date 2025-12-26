<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Counting extends Model
{
    use HasFactory, SoftDeletes;

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Hook into the Eloquent model's boot method.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $totalBefore = static::where('date', '<', $model->date)->sum('count');
            $model->sumToDate = $totalBefore + $model->count;
        });

        static::deleting(function ($model) {
            $count = $model->count;
            static::where('date', '>', $model->date)->decrement('sumToDate', $count);
        });

        static::restoring(function ($model) {
            $count = $model->count;
            $model->sumToDate = static::where('date', '<', $model->date)->sum('count') + $count;
            static::where('date', '>', $model->date)->increment('sumToDate', $count);
        });

        static::updating(function ($model) {
            $difference = $model->getOriginal('count') - $model->count;
            $model->sumToDate -= $difference;
            if ($difference == 0) {
                return;
            } else {
                static::where('date', '>', $model->date)->decrement('sumToDate', $difference);
            }
        });
    }
}
