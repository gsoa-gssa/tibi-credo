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
    ];

    protected $casts = [
        'id' => 'integer',
        'last_contacted_on' => 'date',
        'address_checked' => 'boolean',
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

    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class);
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

    public function fixCantonSuffix(): void
    {
        if (!$this->canton || !$this->canton->label) {
            throw new \Exception('Canton or canton label is missing for commune ' . $this->name . ' with id ' . $this->id . '.');
        }

        $code = $this->canton->label;
        $pattern = '/(\s\(' . preg_quote($code, '/') . '\)|\s' . preg_quote($code, '/') . ')$/';

        $this->name = preg_replace($pattern, '', $this->name);
        $this->save();
    }
}
