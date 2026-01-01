<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\SignatureCollectionScope;
use Symfony\Component\Process\Process;
use setasign\Fpdi\Fpdi;

class SignatureSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'signature_collection_id',
        'short_name',
        'description_internal',
        'sheet_pdf',
        'sheet_pdf_compat',
        'source_x',
        'source_y',
        'source_font_size',
    ];

    public function signatureCollection(): BelongsTo
    {
        return $this->belongsTo(SignatureCollection::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class, 'signature_collection_id', 'signature_collection_id');
    }

    public function download($source)
    {
        // check if compatible pdf exists, otherwise call generateCompatPdf
        if (!$this->sheet_pdf_compat) {
            $this->generateCompatPdf();
            $this->save();
        }
        $pdf = new Fpdi();
        $compatPath = \Storage::disk('public')->path($this->sheet_pdf_compat);
        $pageCount = $pdf->setSourceFile($compatPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplId);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            if ($pageNo === $this->source_page_number) {
                $pdf->SetFont('Courier', '', $this->source_font_size);
                $pdf->SetTextColor(0, 0, 0);
                $textWidth = $pdf->GetStringWidth((string) $source->code);
                $pdf->Text($this->source_x - $textWidth / 2, $this->source_y, (string) $source->code);
            }
        }

        $fileName = sprintf('%s-%s.pdf', $this->short_name ?? 'sheet', $source->code ?? 'source');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->Output('S');
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);

        static::saving(function ($model) {
            if ((empty($model->sheet_pdf_compat) || $model->isDirty('sheet_pdf')) && $model->sheet_pdf) {
                $model->generateCompatPdf();
            }
        });

    }

    public function generateCompatPdf()
    {
        \Log::debug('Generating compatible PDF for SignatureSheet ID ' . $this->id);
        $path = \Storage::disk('public')->path($this->sheet_pdf);
        $outPath = \Storage::disk('public')->path('signature-sheets/compat_' . basename($this->sheet_pdf));
        $process = new \Symfony\Component\Process\Process([
            'gs',
            '-q',
            '-dNOPAUSE',
            '-dBATCH',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-sOutputFile=' . $outPath,
            $path,
        ]);
        try {
            $process->mustRun();
            $this->sheet_pdf_compat = 'signature-sheets/compat_' . basename($this->sheet_pdf);
        } catch (\Exception $e) {
            if (class_exists('Filament\\Notifications\\Notification')) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('PDF compatibility file generation failed')
                    ->body($e->getMessage())
                    ->send();
            }
            throw $e;
        }
    }
}
