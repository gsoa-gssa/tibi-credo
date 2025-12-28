<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Maeppli extends Model
{
    use SoftDeletes, LogsActivity, HasFilamentComments;

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    /**
     * Computed display label: CANTON-0001 (validCount).
     */
    public function getDisplayLabelAttribute(): string
    {
        $canton = $this->commune?->canton?->label ?? '??';
        $number = $this->label_number ? str_pad((string) $this->label_number, 4, '0', STR_PAD_LEFT) : '----';
        $valid = (int) ($this->signatures_valid_count ?? 0);

        return $canton . '-' . $number . ' (' . $valid . ')';
    }

    /**
     * HTML version with a rounded badge for the valid count.
     */
    public function getDisplayLabelHtmlAttribute(): string
    {
        $canton = e($this->commune?->canton?->label ?? '??');
        $number = $this->label_number ? str_pad((string) $this->label_number, 4, '0', STR_PAD_LEFT) : '----';
        $valid = (int) ($this->signatures_valid_count ?? 0);

        $badge = '<span class="inline-flex items-center justify-center rounded-full bg-primary-100 text-primary-700 text-xs font-normal px-2 py-1 min-w-[2.25rem] border border-primary-200">' . $valid . '</span>';

        return $canton . '-' . $number . ' ' . $badge;
    }

    public static function boot ()
    {
        parent::boot();

        static::saving(function ($maeppli) {
            // make sure there is a commune set
            if (!$maeppli->commune_id) {
                throw new \Exception('Maeppli must have a commune assigned.');
            }

            // chose label_number if not set: find max in canton and add 1
            // include deleted maepplis in the count
            if (!$maeppli->label_number) {
                $maxLabelNumber = Maeppli::withTrashed()->whereHas('commune', function ($query) use ($maeppli) {
                    $query->where('canton_id', $maeppli->commune->canton_id);
                })->max('label_number');
                $maeppli->label_number = $maxLabelNumber ? $maxLabelNumber + 1 : 1;
            }

            // Prevent changing label_number after creation
            if ($maeppli->exists && $maeppli->isDirty('label_number')) {
                $maeppli->label_number = $maeppli->getOriginal('label_number');
            }

            // Enforce same canton for all Maepplis inside a Box when assigning box_id
            if ($maeppli->isDirty('box_id') && $maeppli->box_id) {
                $box = Box::with('maepplis.commune.canton')->find($maeppli->box_id);
                if ($box) {
                    $existing = $box->maepplis->first();
                    if ($existing && $existing->commune && $maeppli->commune) {
                        $existingCanton = optional(optional($existing->commune)->canton)->label;
                        $newCanton = optional(optional($maeppli->commune)->canton)->label;
                        if ($existingCanton && $newCanton && $existingCanton !== $newCanton) {
                            throw new \Exception('Cannot assign Maeppli to Box: canton mismatch (' . $newCanton . ' vs ' . $existingCanton . ').');
                        }
                    }
                }
            }
        });
    }
}
