<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rupadana\ApiService\Contracts\HasAllowedFilters;

class Source extends Model implements HasAllowedFilters
{
    use HasFactory;

    public $casts = [
        'label' => 'json',
    ];

    /**
     * Get the sheets for the source.
     */
    public function sheets()
    {
        return $this->hasMany(Sheet::class);
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
