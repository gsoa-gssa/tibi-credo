<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commune extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
    ];

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
