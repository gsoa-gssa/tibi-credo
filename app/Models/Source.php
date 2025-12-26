<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rupadana\ApiService\Contracts\HasAllowedFilters;

class Source extends Model implements HasAllowedFilters
{
    use HasFactory;

    public $casts = [
        'label' => 'json',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
    }

    /**
     * Get the countings for this source.
     */
    public function countings(): HasMany
    {
        return $this->hasMany(Counting::class);
    }

    /**
     * Define which fields can be filtered for this model.
     */
    public static function getAllowedFilters(): array
    {
        $filters = [
            "code"
        ];

        return $filters;
    }
}
