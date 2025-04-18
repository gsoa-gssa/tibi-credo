<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rupadana\ApiService\Contracts\HasAllowedFilters;
use Rupadana\ApiService\Contracts\HasAllowedIncludes;
use Rupadana\ApiService\Contracts\HasAllowedSorts;

class Zipcode extends Model implements HasAllowedFilters, HasAllowedIncludes, HasAllowedSorts
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'commune_id' => 'integer',
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * Define which fields can be filtered for this model.
     */
    public static function getAllowedFilters(): array
    {
        return [
            "code",
            "name"
        ];
    }

    /**
     * Define which relations can be included for this model.
     */
    public static function getAllowedIncludes(): array
    {
        $includes = [
            "commune"
        ];
        return $includes;
    }

    /**
     * Define which fields can be sorted for this model.
     */
    public static function getAllowedSorts(): array
    {
        $sorts = [
            "number_of_dwellings",
        ];
        return $sorts;
    }
}
