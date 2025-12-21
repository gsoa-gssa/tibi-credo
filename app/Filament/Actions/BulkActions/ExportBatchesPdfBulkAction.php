<?php

namespace App\Filament\Actions\BulkActions;

use App\Models\Batches;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportBatchesPdfBulkAction extends BulkAction
{

    protected bool $priorityMail = false;
    protected string $addressPosition = 'left'; // 'left' or 'right'
    protected bool $massDelivery = false;

    public static function getDefaultName(): ?string
    {
        return 'export_batches_pdf';
    }

    public function priorityMail(bool $priorityMail = true): static
    {
        $this->priorityMail = $priorityMail;
        return $this;
    }

    public function addressPosition(string $addressPosition): static
    {
        if (!in_array($addressPosition, ['left', 'right'])) {
            throw new \InvalidArgumentException("addressPosition must be 'left' or 'right'");
        }
        $this->addressPosition = $addressPosition;
        return $this;
    }

    public function massDelivery(bool $massDelivery = true): static
    {
        $this->massDelivery = $massDelivery;
        $this->priorityMail = false;
        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon('heroicon-o-envelope')
            ->action(function (Collection $records) {
                return $this->exportToPdf($records);
            });
    }

    protected function exportToPdf(Collection $batches): Response|StreamedResponse
    {
        try {
            // Load relationships to avoid N+1 queries
            $batches->load(['commune']);

            // Generate combined HTML from individual templates
            $combinedHtml = $this->generateCombinedHtml($batches);

            // Generate filename
            $filename = 'batches_export_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            // Create PDF from combined HTML
            $pdf = Pdf::loadHTML($combinedHtml)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                    'isPhpEnabled' => false,
                    'debugPng' => false,
                    'debugKeepTemp' => false,
                    'debugCss' => false,
                ]);


            // Stream PDF directly to user
            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]
            );

        } catch (\Exception $e) {
            Log::error('PDF Export failed: ' . $e->getMessage(), [
                'exception' => $e,
                'batches_count' => $batches->count(),
            ]);

            // Return error notification
            \Filament\Notifications\Notification::make()
                ->title('Export Failed')
                ->body('There was an error generating the PDF export.')
                ->danger()
                ->send();

            return response('Export failed', 500);
        }
    }

    protected function generateCombinedHtml(Collection $batches): string
    {
        $individualPages = [];

        // Generate HTML for each contact
        foreach ($batches as $batch) {
            try {
                // change locale to render in correct language
                $currentLocale = (string) app()->getLocale();
                $lang = $batch->commune->lang;
                app()->setLocale($lang);
                $individualPages[] = view('batch.partials.letter-a4-' . $lang, [
                    'batch' => $batch,
                    'addressPosition' => $this->addressPosition,
                    'priorityMail' => $this->priorityMail,
                ])->render();
                // Mark batch with appropriate delivery tier
                if ($this->massDelivery) {
                    $batch->mark_mass_delivery();
                } elseif ($this->priorityMail) {
                    $batch->mark_priority_delivery();
                } else {
                    $batch->mark_standard_delivery();
                }
                // set to sent
                $batch->status = 'sent';
                $batch->save();
                // Reset locale after rendering
                app()->setLocale($currentLocale);
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Contact Export Skipped')
                    ->body('Contact ' . $contact->id . ' could not be rendered and was skipped: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        \Filament\Notifications\Notification::make()
            ->title('Batches Processed')
            ->body(count($individualPages) . ' out of ' . $batches->count() . ' letters were generated with address position ' . $this->addressPosition . ', priority mail ' . ($this->priorityMail ? 'enabled' : 'disabled') . ' and mass delivery ' . ($this->massDelivery ? 'enabled' : 'disabled') . '.')
            ->success()
            ->send();

        // Combine all pages with CSS page breaks
        return view('contact.contacts-combined', [
            'pages' => $individualPages,
        ])->render();
    }
}