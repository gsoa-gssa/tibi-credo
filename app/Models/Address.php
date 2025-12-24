<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'commune_id',
        'zipcode_id',
        'street_name',
        'street_number',
    ];

    protected $casts = [
        'id' => 'integer',
        'commune_id' => 'integer',
        'zipcode_id' => 'integer',
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function zipcode(): BelongsTo
    {
        return $this->belongsTo(Zipcode::class);
    }

    /**
     * Get all addresses sharing this street name (including the current address).
     */
    public function getSameStreetAddresses()
    {
        return static::query()
            ->whereHas('zipcode', function ($q) {
                $q->where('code', $this->zipcode->code)
                  ->where('name', $this->zipcode->name);
            })
            ->where('street_name', $this->street_name)
            ->get();
    }

    /**
     * Get number ranges of addresses on the same street and same/other commune.
     * Algorithm: Iterate through sorted addresses, track ranges of uninterrupted numbers.
     * 
     * @param int|null $referenceCommune The commune ID to use for comparison. Defaults to this address's commune_id.
     */
    public function getSameStreetNumberRanges($referenceCommune = null)
    {
        $referenceCommune = $referenceCommune ?? $this->commune_id;
        $addresses = $this->getSameStreetAddresses();
        // sort by numeric prefix of street_number
        $addresses = $addresses->sortBy(function ($address) {
            preg_match('/^\d+/', $address->street_number ?? '', $matches);
            return intval($matches[0] ?? 0);
        })->values();
        $unambiguousNumericPrefixes = [];
        $currentNumber = null;
        $currentCommune = null;
        $currentUnambiguous = true;
        foreach ($addresses as $address) {
            $number = $address->street_number;
            // get numeric prefix only
            preg_match('/^\d+/', $number ?? '', $matches);
            $number = intval($matches[0] ?? 0);
            if ($number === $currentNumber) {
                if ($address->commune_id === $currentCommune) {
                    // still unambiguous
                } else {
                    // ambiguous now
                    $currentUnambiguous = false;
                }
            } else {
                if ($currentUnambiguous && $currentNumber !== null) {
                    // save previous number as unambiguous
                    $unambiguousNumericPrefixes[] = $currentNumber;
                } else {
                    // reset unambiguous tracking
                    $currentUnambiguous = true;
                }
                $currentNumber = $number;
                $currentCommune = $address->commune_id;
            } 
        }
        // save last number if unambiguous
        if ($currentUnambiguous && $currentNumber !== null) {
            $unambiguousNumericPrefixes[] = $currentNumber;
        }

        // now we can do the ranges: if a number is ambiguous, we add it as single entry
        // otherwise we take the first and last number of current/other commune and
        // put them into the range no matter if they are consecutive or not
        $ranges_same_commune = [];
        $ranges_other_communes = [];
        $current_range_start = null;
        $current_range_end = null;
        $current_range_same_commune = null;
        
        foreach ($addresses as $address){
            $number_full = $address->street_number;
            // get numeric prefix only
            preg_match('/^\d+/', $number_full ?? '', $matches);
            $number = intval($matches[0] ?? 0);
            $is_unambiguous = in_array($number, $unambiguousNumericPrefixes);

            if(!$is_unambiguous){
                // save current range if exists
                if($current_range_start !== null){
                    $range = [
                        'start' => $current_range_start,
                        'end' => $current_range_end,
                    ];
                    if($current_range_same_commune){
                        $ranges_same_commune[] = $range;
                    } else {
                        $ranges_other_communes[] = $range;
                    }
                    $current_range_start = null;
                    $current_range_end = null;
                    $current_range_same_commune = null;
                }
                // add single ambiguous number
                $range = [
                    'start' => $number_full,
                    'end' => $number_full,
                ];
                if($address->commune_id === $referenceCommune){
                    $ranges_same_commune[] = $range;
                } else {
                    $ranges_other_communes[] = $range;
                }
            } else {
                // unambiguous number, extend current range or start new
                if($current_range_start === null){
                    // start new range
                    $current_range_start = $number;
                    $current_range_end = $number;
                    $current_range_same_commune = ($address->commune_id === $referenceCommune);
                } else {
                    // check if commune changed
                    $is_same_commune = ($address->commune_id === $referenceCommune);
                    if ($is_same_commune !== $current_range_same_commune) {
                        // commune changed, save current range and start new one
                        $range = [
                            'start' => $current_range_start,
                            'end' => $current_range_end,
                        ];
                        if($current_range_same_commune){
                            $ranges_same_commune[] = $range;
                        } else {
                            $ranges_other_communes[] = $range;
                        }
                        $current_range_start = $number;
                        $current_range_end = $number;
                        $current_range_same_commune = $is_same_commune;
                    } else {
                        // same commune, extend range
                        $current_range_end = $number;
                    }
                }
            }
        }
        
        // Save the last range if it exists
        if($current_range_start !== null){
            $range = [
                'start' => $current_range_start,
                'end' => $current_range_end,
            ];
            if($current_range_same_commune){
                $ranges_same_commune[] = $range;
            } else {
                $ranges_other_communes[] = $range;
            }
        }

        return [
            'same_commune' => $ranges_same_commune,
            'other_communes' => $ranges_other_communes,
        ];
    }
}
