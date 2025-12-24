<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
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

    /**
     * Get a list of all streets in this zipcode, separated by same commune as this / other communes.
     * If there are house numbers on a street with same code/name but different commune,
     * include the house numbers with the street_name.
     * Otherwise, just include the street name without numbers.
     */
    public function getStreetsWithNumbers(): array
    {
        $streets = [
            'same_commune' => [],
            'other_communes' => []
        ];

        // Get addresses by zipcode code/name (not zipcode_id, as data may be inconsistent)
        $addresses = Address::whereHas('zipcode', function ($q) {
                $q->where('code', $this->code)
                  ->where('name', $this->name);
            })
            ->with('commune')
            ->orderBy('street_name')
            ->orderBy('street_number')
            ->get();

        // Group addresses by street name
        $streetGroups = $addresses->groupBy('street_name');

        foreach ($streetGroups as $streetName => $streetAddresses) {
            // Use the first address of this street to call getSameStreetNumberRanges
            $firstAddress = $streetAddresses->first();
            $ranges = $firstAddress->getSameStreetNumberRanges($this->commune_id);

            // omit numbers if not relevant
            if (empty($ranges['same_commune'])){
                $streets['other_communes'][] = $streetName;
                continue;
            }
            if (empty($ranges['other_communes'])){
                $streets['same_commune'][] = $streetName;
                continue;
            }

            // Format same commune
            if (!empty($ranges['same_commune'])) {
                $rangeStrings = [];
                foreach ($ranges['same_commune'] as $range) {
                    if ($range['start'] === $range['end']) {
                        $rangeStrings[] = $range['start'];
                    } else {
                        $rangeStrings[] = $range['start'] . '-' . $range['end'];
                    }
                }
                $streets['same_commune'][] = $streetName . ': ' . implode(', ', $rangeStrings);
            }

            // Format other communes
            if (!empty($ranges['other_communes'])) {
                $rangeStrings = [];
                foreach ($ranges['other_communes'] as $range) {
                    if ($range['start'] === $range['end']) {
                        $rangeStrings[] = $range['start'];
                    } else {
                        $rangeStrings[] = $range['start'] . '-' . $range['end'];
                    }
                }
                $streets['other_communes'][] = $streetName . ': ' . implode(', ', $rangeStrings);
            }
        }

        return $streets;
    }
}
