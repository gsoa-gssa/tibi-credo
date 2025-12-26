<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;


class Batch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasFilamentComments;

    protected $fillable = [
        'status',
        'expectedDeliveryDate',
        'commune_id',
        'signature_count',
        'sheets_count',
    ];

    protected $casts = [
        'id' => 'integer',
        'expectedDeliveryDate' => 'date',
        'commune_id' => 'integer',
        'signature_count' => 'integer',
        'sheets_count' => 'integer',
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function countSignatures(): int
    {
        return $this->signature_count ?? 0;
    }

    /**
     * If this batch has sheets, return a list of their labels as a string.
     * Consecutive labels are shortened to ranges, ranges of length 1 are shown as single labels, single labels and ranges are separated by commas.
     * @return string
     */
    public function sheetsString(): string
    {
        try {
            if($this->sheets()->count() == 0) {
                return '';
            }
            $labels = $this->sheets()->orderBy('label')->pluck('label')->toArray();
        } catch (\Exception $e) {
            // sheets relationship no longer exists
            return '';
        }
        // if possible cast labels to integers
        $labels = array_map(function ($label) {
            return is_numeric($label) ? (int) $label : $label;
        }, $labels);
        $result = [];
        $start = null;
        $end = null;

        foreach ($labels as $label) {
            if (is_numeric($label) && $start === null) {
                $start = $label;
                $end = $label;
            } elseif (is_numeric($label) && $label == $end + 1) {
                $end = $label;
            } else {
                if ($start == $end) {
                    $result[] = $start;
                } else {
                    $result[] = $start . '-' . $end;
                }
                $start = $label;
                $end = $label;
            }
        }

        if ($start !== null) {
            if ($start == $end) {
                $result[] = $start;
            } else {
                $result[] = $start . '-' . $end;
            }
        }

        $r_string = implode(', ', $result);
        // add thousand separators for better readability
        // use a space to make it more locale-independent
        $r_string = preg_replace_callback('/\d{4,}/', function ($matches) {
            return number_format($matches[0], 0, '', ' ');
        }, $r_string);
        return $r_string;
    }

    /**
     * Return the sheets string with non-breaking space number separators instead of normal spaces.
     */
    public function sheetsHTMLString(): string
    {
        $string = $this->sheetsString();
        return preg_replace('/([0-9]) ([0-9])/', '$1&nbsp;$2', $string);
    }

    /**
     * Calculate the next workday (excluding weekends).
     * Returns a Carbon date object.
     */
    private static function nextWorkday(\Carbon\Carbon $date, int $workdaysToAdd = 1): \Carbon\Carbon
    {
        $date = $date->copy();
        
        while ($workdaysToAdd > 0) {
            $date->addDay();
            // 0 = Sunday, 6 = Saturday
            if (!in_array($date->dayOfWeek, [0, 6])) {
                $workdaysToAdd--;
            }
        }
        
        return $date;
    }

    /**
     * Mark batch for priority delivery: expectedDeliveryDate = created_at + 1 workday
     */
    public function mark_priority_delivery(): void
    {
        $this->expectedDeliveryDate = self::nextWorkday($this->created_at, 1);
        $this->save();
    }

    /**
     * Mark batch for standard delivery: expectedDeliveryDate = created_at + 2 workdays
     */
    public function mark_standard_delivery(): void
    {
        $this->expectedDeliveryDate = self::nextWorkday($this->created_at, 2);
        $this->save();
    }

    /**
     * Mark batch for mass delivery: expectedDeliveryDate = created_at + 6 workdays
     */
    public function mark_mass_delivery(): void
    {
        $this->expectedDeliveryDate = self::nextWorkday($this->created_at, 6);
        $this->save();
    }

    public function pdf($posLeft = true, $priority = false): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // change locale to render in correct language
        $currentLocale = (string) app()->getLocale();
        $lang = $this->commune->lang;
        app()->setLocale($lang);
        $addressPosition = $posLeft ? 'left' : 'right';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('batch.partials.letter-a4-' . $lang, ['batch' => $this, 'addressPosition' => $addressPosition, 'priorityMail' => $priority]);
        // restore locale
        app()->setLocale($currentLocale);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'batch-letter-ID_' . $this->id . '.pdf');
    }

    /**
     * Hook into the model's boot method.
     */
    protected static function boot()
    {
        parent::boot();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
