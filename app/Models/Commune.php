<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class Commune extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        // ...existing fields...
        'authority_address_name',
        'authority_address_street',
        'authority_address_house_number',
        'authority_address_extra',
        'authority_address_postcode',
        'authority_address_place',
        'address_checked',
        'checked_on',
        'name_with_canton',
    ];

    protected $casts = [
        'id' => 'integer',
        'last_contacted_on' => 'date',
        'address_checked' => 'boolean',
        'checked_on' => 'date',
    ];

    const SVE_GE_BFS_IDS = [
        6601,
        6602,
        6603,
        6604,
        6605,
        6606,
        6607,
        6608,
        6609,
        6610,
        6612,
        6613,
        6614,
        6615,
        6616,
        6617,
        6618,
        6619,
        6620,
        6621,
        6622,
        6623,
        6624,
        6625,
        6626,
        6628,
        6629,
        6630,
        6631,
        6632,
        6633,
        6634,
        6635,
        6636,
        6637,
        6638,
        6639,
        6640,
        6641,
        6642,
        6643,
        6644,
        6645,
    ];
    /**
     * Return the formatted authority address as HTML.
     */
    public function address_html(): string
    {
        $lines = [];
        if ($this->authority_address_name) {
            $lines[] = e($this->authority_address_name);
        }
        $street = trim(($this->authority_address_street ?? '') . ' ' . ($this->authority_address_house_number ?? ''));
        if ($street) {
            $lines[] = e($street);
        }
        if ($this->authority_address_extra) {
            $lines[] = e($this->authority_address_extra);
        }
        $cityLine = trim(($this->authority_address_postcode ?? '') . ' ' . ($this->authority_address_place ?? ''));
        if ($cityLine) {
            $lines[] = e($cityLine);
        }
        return implode('<br>', $lines);
    }


    public function zipcodes(): HasMany
    {
        return $this->hasMany(Zipcode::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function maepplis(): HasMany
    {
        return $this->hasMany(Maeppli::class);
    }

    /**
     * All addresses belonging to this commune.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Models\Address::class, 'commune_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function nameWithCanton(): string
    {
        if ($this->canton && $this->canton->label) {
            return $this->name . ' ' . $this->canton->label ;
        }

        return $this->name;
    }

    public function withoutCantonSuffix($name_candidate): string
    {
        if (!$this->canton || !$this->canton->label) {
            return $this->name;
        }

        $code = $this->canton->label;
        $pattern = '/[,\s]*\(?' . preg_quote($code, '/') . '\)?$/';

        return preg_replace($pattern, '', $name_candidate);
    }

    public static function bfsToPk()
    {
        $mapping = Commune::pluck('id', 'officialId')->toArray();
        
        // Map all Geneva special communes to Geneva (6621)
        $genevaId = $mapping[6621] ?? null;
        if ($genevaId) {
            foreach (self::SVE_GE_BFS_IDS as $bfsId) {
                $mapping[$bfsId] = $genevaId;
            }
        }
        
        return $mapping;
    }

    public function saveNameWithCanton(): void
    {
        $this->name_with_canton = $this->nameWithCanton();
    }

    public function fixCantonSuffix(): void
    {
        $this->name = $this->withoutCantonSuffix($this->name);
    }

    /**
     * Get the commune with the highest complexity score.
     * Score is calculated as: zipcodes count + street count from the shorter array.
     */
    public static function mostComplicatedCommune(): ?self
    {
        $communes = self::with('zipcodes')->get();
        
        $maxScore = -1;
        $mostComplicated = null;
        
        foreach ($communes as $commune) {
            $score = 0;
            
            // Add 1 for each zipcode
            $score += $commune->zipcodes->count();
            
            // For each zipcode, add the count of streets from the shorter array
            foreach ($commune->zipcodes as $zipcode) {
                $streetsByType = $zipcode->getStreetsWithNumbers();
                $sameCount = count($streetsByType['same_commune']);
                $otherCount = count($streetsByType['other_communes']);
                
                // Add the count from the shorter array
                $score += min($sameCount, $otherCount);
            }
            
            if ($score > $maxScore) {
                // print to terminal
                echo "New most complicated commune: " . $commune->nameWithCanton() . " with score " . $score . PHP_EOL;
                $maxScore = $score;
                $mostComplicated = $commune;
            }
        }
        
        return $mostComplicated;
    }

    protected static function booted(): void
    {
        static::saving(function (Commune $commune) {
            $commune->fixCantonSuffix();
            $commune->saveNameWithCanton();
        });
    }
}
