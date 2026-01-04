<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
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
    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
    }
}
