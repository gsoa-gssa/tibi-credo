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

    protected $casts = [
        'id' => 'integer',
        'sendDate' => 'date',
        'commune_id' => 'integer',
    ];

    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function countSignatures(): int
    {
        return $this->sheets()->sum('signatureCount');
    }

    /**
     * Update the status of the batch: If at least 90% of the sheets have been returned, mark the batch as 'returned'.
     */
    public function updateStatus(): void
    {
        $returnedSheetsCount = $this->sheets()->whereNotNull('maeppli_id')->count();
        $totalSheetsCount = $this->sheets()->count();

        if ($totalSheetsCount > 0 && $returnedSheetsCount / $totalSheetsCount >= 0.9) {
            $this->status = 'returned';
            $this->save();
        }
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

        // static::created(function ($batch) {
        //     $sheets = \App\Models\Sheet::where("commune_id", $batch->commune_id)->where("status", "recorded")->get();
        //     $sheets->each(function ($sheet) use ($batch) {
        //         $sheet->batch_id = $batch->id;
        //         $sheet->status = 'added2batch';
        //         $sheet->save();
        //     });
        // });

        static::deleting(function ($batch) {
            $sheets = \App\Models\Sheet::where("batch_id", $batch->id)->get();
            $sheets->each(function ($sheet) {
                $sheet->batch_id = null;
                $sheet->status = 'recorded';
                $sheet->save();
            });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
