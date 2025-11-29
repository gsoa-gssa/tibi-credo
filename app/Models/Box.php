<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{

    protected $appends = [
        'signatures_count',
        'signatures_count_total',
        'canton',
        'label',
        'label_final',
    ];

    /**
     * Maepplis in this box.
     */
    public function maepplis(): HasMany
    {
        return $this->hasMany(Maeppli::class);
    }

    /**
     * Sum of valid signatures across Maepplis (sheets_valid_count field).
     */
    public function getSignaturesCountAttribute(): int
    {
        return (int) $this->maepplis()->sum('sheets_valid_count');
    }

    /**
     * Sum of total signatures across Maepplis (sheets_valid_count+sheets_invalid_count).
     */
    public function getSignaturesCountTotalAttribute(): int
    {
        return (int) $this->maepplis()->sum(\DB::raw('sheets_valid_count + sheets_invalid_count'));
    }

    /**
     * Canton label (all Maepplis must have same canton). Derived from first Maeppli.
     */
    public function getCantonAttribute(): ?string
    {
        $first = $this->maepplis()->with(['commune.canton'])->first();
        return $first && $first->commune && $first->commune->canton ? $first->commune->canton->label : null;
    }

    public function getOrderWithinCanton(): ?int
    {
        $canton = $this->canton;
        if (!$canton) {
            return null;
        }

        $boxesWithCanton = static::whereHas('maepplis.commune.canton', function ($q) use ($canton) {
            $q->where('label', $canton);
        })->orderBy('id')->get(['id']);

        $index = $boxesWithCanton->search(fn($b) => $b->id === $this->id);
        return $index === false ? null : ($index + 1);
    }

    public function getTotalBoxesInCanton(): ?int
    {
        $canton = $this->canton;
        if (!$canton) {
            return null;
        }

        $boxesWithCanton = static::whereHas('maepplis.commune.canton', function ($q) use ($canton) {
            $q->where('label', $canton);
        })->get();

        return $boxesWithCanton->count();
    }

    /**
     * Basic label: B<id>-<canton><if canton known>
     */
    public function getLabelAttribute(): ?string
    {
        $canton = $this->canton;
        if (!$this->id) {
            return null; // not yet persisted
        }
        if (!$canton) {
            return 'B' . $this->id; // canton unknown - empty box
        }
        return 'B' . $this->id . '-' . $canton . $this->getOrderWithinCanton();
    }

    /**
     * Final label: B<id>/<total boxes>-<canton><index in canton>/<total canton boxes>
     * Example: B12/40-ZH3/7
     */
    public function getLabelFinalAttribute(): ?string
    {
        if (!$this->id) {
            return null;
        }

        $canton = $this->canton;
        $totalBoxes = static::count();

        if (!$canton) {
            return 'B' . $this->id . '/' . $totalBoxes; // canton unknown - empty box
        }

        $index = $this->getOrderWithinCanton();
        $countForCanton = $this->getTotalBoxesInCanton();
        if (!$index || !$countForCanton) {
            return 'B' . $this->id . '/' . $totalBoxes . '-' . $canton;
        }

        return 'B' . $this->id . '/' . $totalBoxes . '-' . $canton . $index . '/' . $countForCanton;
    }
}
