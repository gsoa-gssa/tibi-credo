<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
use App\Traits\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rupadana\ApiService\Contracts\HasAllowedFilters;


class Source extends Model implements HasAllowedFilters
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = [
        'code',
        'short_description_de',
        'short_description_fr',
        'short_description_it',
        'sheets_printed',
        'addition_cost',
        'comments',
    ];

    protected $casts = [
        'short_description_de' => 'string',
        'short_description_fr' => 'string',
        'short_description_it' => 'string',
        'sheets_printed' => 'integer',
        'addition_cost' => 'float',
        'comments' => 'string',
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

    public function signatureCollection()
    {
        return $this->belongsTo(\App\Models\SignatureCollection::class, 'signature_collection_id');
    }
}
