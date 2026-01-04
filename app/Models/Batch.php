<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\HasActivityComments;


class Batch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasActivityComments;

    protected $fillable = [
        'status',
        'expected_delivery_date',
        'expected_return_date',
        'commune_id',
        'signature_count',
        'sheets_count',
        'open',
        'send_kind',
        'receive_kind',
        'letter_html',
        'priority',
    ];

    protected $casts = [
        'id' => 'integer',
        'expected_delivery_date' => 'date',
        'expected_return_date' => 'date',
        'commune_id' => 'integer',
        'signature_count' => 'integer',
        'sheets_count' => 'integer',
        'open' => 'boolean',
        'send_kind' => 'integer',
        'receive_kind' => 'integer',
        'priority' => 'string',
    ];

    protected $attributes = [
        'open' => true,
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function signatureCollection(): BelongsTo
    {
        return $this->belongsTo(SignatureCollection::class);
    }

    public function sendKind(): BelongsTo
    {
        return $this->belongsTo(BatchKind::class, 'send_kind');
    }

    public function receiveKind(): BelongsTo
    {
        return $this->belongsTo(BatchKind::class, 'receive_kind');
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

    public function get_expected_delivery_date(string $priority): ?\Carbon\Carbon
    {
        if ($priority === 'A') {
            return self::nextWorkday(now(), 1);
        } elseif ($priority === 'B1') {
            return self::nextWorkday(now(), 2);
        } elseif ($priority === 'B2') {
            return self::nextWorkday(now(), 6);
        } else {
            throw new \InvalidArgumentException('Invalid priority: ' . $priority);
        }
    }

    public function get_letter_html(string $addressPosition, string $priority): string
    {
        if ($this->letter_html !== null) {
            return $this->letter_html;
        }

        // check priority is in A, B1, B2. Throw exception if not.
        if (!in_array($priority, ['A', 'B1', 'B2'])) {
            throw new \InvalidArgumentException('Invalid priority: ' . $priority);
        }
        $priorityMail = $priority === 'A' ? 'A' : 'B';

        // check addressPosition is 'left' or 'right'. Throw exception if not.
        if (!in_array($addressPosition, ['left', 'right'])) {
            throw new \InvalidArgumentException("addressPosition must be 'left' or 'right'");
        }

        // calculate expected delivery and return dates
        $this->expected_delivery_date = $this->get_expected_delivery_date($priority);
        $this->expected_return_date = self::nextWorkday($this->expected_delivery_date, $this->signatureCollection->return_workdays);

        // change locale to render in correct language
        $currentLocale = (string) app()->getLocale();
        $lang = $this->commune->lang;
        app()->setLocale($lang);
        \Log::info("Generating letter HTML for batch ID {$this->id} in locale {$lang} with address position {$addressPosition} and priority {$priority}, priorityMail is " . $priorityMail);
        $html = view('batch.partials.letter', [
            'batch' => $this,
            'addressPosition' => $addressPosition,
            'priorityMail' => $priorityMail
        ])->render();

        // restore locale
        app()->setLocale($currentLocale);

        // side effects
        $this->letter_html = $html;
        $this->priority = $priority;
        $this->save();
        return $html;
    }
        
    /**
     * @deprecated This function is deprecated and should not be used in new code.
     *
     * Generates a pdf of the letter using a limited pdf renderer. Not all features are rendered.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function get_letter_pdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if( $this->letter_html === null ) {
            throw new \Exception('Letter HTML not generated yet for batch ID ' . $this->id);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->letter_html);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'batch-letter-ID_' . $this->id . '.pdf');
    }

    public function anyOtherBatchOpen(): bool
    {
        return self::where('commune_id', $this->commune_id)
            ->where('id', '!=', $this->id)
            ->where('open', true)
            ->exists();
    }

    public function datamatrixContent(): string
    {
        $components = "";
        $components .= "756"; // Swiss country code / Post CH AG
        $components .= "80"; // Brief national
        $components .= "21"; // Fixer Wert «21» = Datamatrix-Code Typ 21
        $components .= "00000000"; // RNN, die ersten 8 der 9 Stellen
        $components .= $this->created_at->format('ymd'); // Auftragsnummer, 6 Stellen, YYMMDD
        $components .= str_pad($this->id, 9, '0', STR_PAD_LEFT); // Auftragsnummer, 9 Stellen, Batch ID with leading zeros
        $components .= "1"; // Addressblock
        // 00=B (not specified B1/B2), 01=A, 02=B1, 04=B2.
        if($this->priority === 'A') {
            $components .= "01";
        } else {
            $components .= "00";
        }
        $components .= "0"; // Retouren physisch normal
        $components .= "0"; // freies Feld, nicht verwendet
        $components .= "0"; // Zusatzleistungen: Keine
        $components .= "00000000"; // Adress-ID Rückführadresse - nicht verwendet 
        return $components;
    }

    public function datamatrixSize(): int
    {
        return 11; // in mm
    }

    /**
     * Generate combined HTML for multiple batches.
     * This was moved from the Filament action into the model so callers
     * can reuse the same logic.
     *
     * @param Collection $batches
     * @param string $addressPosition
     * @param string $priority
     * @return string
     */
    public static function get_letter_html_many(Collection $batches, string $addressPosition, string $priority): string
    {
        $individualPages = [];

        foreach ($batches as $batch) {
            try {
                $individualPages[] = $batch->get_letter_html($addressPosition, $priority);
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Batch Export Skipped')
                    ->body('Batch ' . $batch->id . ' could not be rendered and was skipped: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        \Filament\Notifications\Notification::make()
            ->title('Batches Processed')
            ->body(count($individualPages) . ' out of ' . $batches->count() . ' letters were generated with address position ' . $addressPosition . ' and priority ' . $priority . '.')
            ->success()
            ->send();

        return view('contact.contacts-combined', [
            'pages' => $individualPages,
        ])->render();
    }

    /**
     * Hook into the model's boot method.
     */
    protected static function boot()
    {
        parent::boot();

        // If receive_kind is set, automatically set open to false
        static::saving(function (self $batch) {
            if ($batch->receive_kind !== null) {
                $batch->open = false;
            }

            // Prevent changes to letter_html if it's not empty, except for super_admins deleting it
            if (
                $batch->isDirty('letter_html') &&
                !empty($batch->getOriginal('letter_html')) &&
                !(
                    optional(auth()->user())->hasRole('super_admin') &&
                    ($batch->letter_html === null || $batch->letter_html === '')
                )
            ) {
                throw new \Exception('Cannot modify letter_html once it has been generated.');
            }

            // Set send_kind to the user's signatureCollection default if not set
            if (empty($batch->send_kind) && auth()->check() && auth()->user()->signatureCollection) {
                $batch->send_kind = auth()->user()->signatureCollection->default_send_kind_id;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
