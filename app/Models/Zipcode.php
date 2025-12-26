<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Zipcode extends Model
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

    /**
     * Get a summary of streets with numbers, abbreviated for display.
     * Keeps the shorter list in full, abbreviates the longer list with similar street names only.
     * Uses Levenshtein distance to find similar street names (threshold: < 3).
     */
    public function getStreetsWithNumbersSummary(): array
    {
        $cacheKey = 'zipcode:' . $this->id . ':streets_with_numbers_summary';
        
        return Cache::rememberForever($cacheKey, function () {
            $streets = $this->getStreetsWithNumbers();
            
            $same_commune = $streets['same_commune'];
            $other_communes = $streets['other_communes'];
            
            // Determine which is the full list and which should be abbreviated
            $full_list = $same_commune;
            $abbrev_list = $other_communes;
            $is_same_commune_full = true;
            
            if (count($other_communes) < count($same_commune)) {
                $full_list = $other_communes;
                $abbrev_list = $same_commune;
                $is_same_commune_full = false;
            }
            
            // Extract street names from full list for comparison (remove the ": number" part)
            $full_street_names = array_map(function ($street) {
                return preg_replace('/:\\s.+$/', '', $street);
            }, $full_list);
            
            // Find similar streets in abbreviated list using Levenshtein distance
            $similar_streets = [];
            foreach ($abbrev_list as $street) {
                $street_name = preg_replace('/:\\s.+$/', '', $street);
                $min_distance = PHP_INT_MAX;
                
                foreach ($full_street_names as $full_name) {
                    $distance = levenshtein(
                        $this->normalizeStreetName($street_name),
                        $this->normalizeStreetName($full_name)
                    );
                    $min_distance = min($min_distance, $distance);
                }
                
                if ($min_distance < 5) {
                    $similar_streets[] = $street;
                }
            }
            
            // Build the summary
            $summary = [];
            
            if ($is_same_commune_full) {
                $summary['same_commune'] = implode(', ', $full_list);
                $summary['other_communes'] = '';
                
                if (!empty($similar_streets)) {
                    $summary['other_communes'] = __('commune.label.among_others') . implode(', ', $similar_streets);
                } else {
                    if (!empty($full_list)) {
                        $summary['other_communes'] = __('commune.label.no_similar_streets_found');
                    }
                }
            } else {
                $summary['other_communes'] = implode(', ', $full_list);
                $summary['same_commune'] = '';
                
                if (!empty($similar_streets)) {
                    $summary['same_commune'] = __('commune.label.among_others') . implode(', ', $similar_streets);
                } else {
                    if (!empty($full_list)) {
                        $summary['same_commune'] = __('commune.label.no_similar_streets_found');
                    }
                }
            }
            
            return $summary;
        });
    }

    /**
     * Some zipcodes which are commonly used to represent communes
     * like 8000 don't actually exist.
     * Add them.
     */
    public static function addRoundZips(): void
    {
        $additional_zipcodes = [
            'Geneva' => '1200',
            'Bern' => '3000',
            'Basel' => '4000',
            'Luzern' => '6000',
            'Zurich' => '8000',
        ];
        foreach ($additional_zipcodes as $communeName => $zipCode) {
            $commune = Commune::where('name', $communeName)->first();
            if ($commune) {
                $existing = Zipcode::where('commune_id', $commune->id)
                    ->where('code', $zipCode)
                    ->first();
                if (!$existing) {
                    Zipcode::create([
                        'commune_id' => $commune->id,
                        'code' => $zipCode,
                        'name' => $commune->nameWithCanton(),
                        'number_of_dwellings' => 0,
                    ]);
                }
            }
        }
    }

    /**
     * Forget the cached summary for a specific zipcode id.
     */
    public static function forgetSummaryCacheById(int $id): void
    {
        Cache::forget('zipcode:' . $id . ':streets_with_numbers_summary');
    }

    /**
     * Forget cached summaries for all zipcodes sharing the same code & name across communes.
     */
    public static function forgetSummaryCacheForCodeName(string $code, string $name): void
    {
        Zipcode::where('code', $code)
            ->where('name', $name)
            ->pluck('id')
            ->each(function ($id) {
                Cache::forget('zipcode:' . $id . ':streets_with_numbers_summary');
            });
    }

    protected static function booted(): void
    {
        static::saved(function (Zipcode $zipcode) {
            // Always forget this zipcode's cache
            self::forgetSummaryCacheById($zipcode->id);

            // If code or name changed, also forget caches of the previous group
            $originalCode = $zipcode->getOriginal('code');
            $originalName = $zipcode->getOriginal('name');
            if (($originalCode && $originalCode !== $zipcode->code) || ($originalName && $originalName !== $zipcode->name)) {
                self::forgetSummaryCacheForCodeName($originalCode ?? $zipcode->code, $originalName ?? $zipcode->name);
                self::forgetSummaryCacheForCodeName($zipcode->code, $zipcode->name);
            }
        });

        static::deleted(function (Zipcode $zipcode) {
            self::forgetSummaryCacheForCodeName($zipcode->code, $zipcode->name);
            self::forgetSummaryCacheById($zipcode->id);
        });
    }

    /**
     * Normalize a street name for comparison.
     * Converts to lowercase, removes whitespace, and removes common street suffixes.
     */
    private function normalizeStreetName(string $name): string
    {
        // Convert to lowercase and remove all whitespace
        $normalized = strtolower(preg_replace('/\s+/', '', $name));
        
        // Remove common street suffixes in German, French, and Italian
        $suffixes = [
            'strasse',
            'strasse',
            'strasse',
            'straße',  // German ß
            'avenue',
            'via',
            'platz',
            'platz',
            'weg',
            'gasse',
            'gässli',
            'allee',
            'boulevard',
            'corso',
            'viale',
            'rue',
            'place',
        ];
        
        foreach ($suffixes as $suffix) {
            // Remove suffix if it appears at the end
            if (substr($normalized, -strlen($suffix)) === $suffix) {
                $normalized = substr($normalized, 0, -strlen($suffix));
            }
        }
        
        return $normalized;
    }
}
