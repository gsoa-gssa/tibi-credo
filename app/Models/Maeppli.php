<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
use App\Exceptions\MatchBatchException;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasActivityComments;

class Maeppli extends Model
{
    use SoftDeletes, LogsActivity, HasFilamentComments, HasActivityComments;

    protected $casts = [
        'no_matching' => 'boolean',
    ];

    protected $attributes = [
        'no_matching' => false,
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);

        static::creating(function ($maeppli) {
            if (!$maeppli->no_matching) {
                if (!$maeppli->matchBatch(save: false)) {
                    throw new MatchBatchException("MatchBatch failed.");
                }
            }
        });

        static::created(function ($maeppli) {
            if (!$maeppli->no_matching) {
                $maeppli->matchBatch(save: true);
            }
        });
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
     * If this returns true, the maeppli can be matched to a batch.
     */
    public function matchBatch($save = false): bool
    {
        \Log::debug('Attempting to match Maeppli ID ' . $this->id . ' to a Batch, save=' . ($save ? 'true' : 'false') . '.');
        $batches = Batch::where('commune_id', $this->commune_id)
            ->where('open', true)
            ->oldest()
            ->get();

        // exact match total sigs and sheets
        foreach ($batches as $batch) {
            if ($batch->signature_count === $this->signatures_valid_count + $this->signatures_invalid_count &&
                ($batch->sheets_count === $this->sheets_count || $this->sheets_count === null)) {
                if ($save) {
                    $batch->open = false;
                    $batch->save();
                }
                return true;
            }
        }
        \Log::debug('No exact match found for Maeppli ID ' . $this->id . '. Trying close match.');

        // close match: total sigs and sheets within 5%
        foreach ($batches as $batch) {
            $totalSigs = $this->signatures_valid_count + $this->signatures_invalid_count;
            $sigDiff = abs($batch->signature_count - $totalSigs);
            $sheetsDiff = $this->sheets_count ? abs($batch->sheets_count - $this->sheets_count) : 0;
            $sigDiffRelative = $sigDiff / max(1, $batch->signature_count);
            $sheetsDiffRelative = $sheetsDiff / max(1, $batch->sheets_count);

            if (($sigDiffRelative <= 0.05 && $sheetsDiffRelative <= 0.05) || ($sigDiff <= 5 && $sheetsDiff <= 2)) {
                if ($save) {
                    $batch->open = false;
                    $batch->save();
                }
                return true;
            }
        }
        \Log::debug('No close match found for Maeppli ID ' . $this->id . '. Trying low count match.');

        // low counts: less than 10 signatures, do not match
        if (($this->signatures_valid_count + $this->signatures_invalid_count) < 10) {
            \Log::debug('Maeppli ID ' . $this->id . ' has less than 10 signatures, skipping matching.');
            return true;
        }

        // no match found
        \Log::debug('No match found for Maeppli ID ' . $this->id . '.');
        return false;
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
            // set signature_collection_id from user if empty
            if (empty($maeppli->signature_collection_id)) {
                $maeppli->signature_collection_id = auth()->user()?->signature_collection_id;
            }

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
