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

    public function nameWithCanton(): string
    {
        if ($this->commune && $this->commune->canton && $this->commune->canton->label) {
            return $this->name . ' ' . $this->commune->canton->label ;
        }

        return $this->name;
    }

    public function fixCantonSuffix(): void
    {
        if (!$this->commune->canton || !$this->commune->canton->label) {
            throw new \Exception('Canton or canton label is missing for commune ' . $this->name . ' with id ' . $this->id . '.');
        }

        $code = $this->commune->canton->label;
        $pattern = '/(\s\(' . preg_quote($code, '/') . '\)|\s' . preg_quote($code, '/') . ')$/';

        $this->name = preg_replace($pattern, '', $this->name);
        $this->save();
    }

    /**
     * Get the total number of dwellings for this zipcode code and name across all communes.
     */
    public function getTotalDwellings(): int
    {
        return Zipcode::where('code', $this->code)
            ->where('name', $this->name)
            ->sum('number_of_dwellings');
    }
}
