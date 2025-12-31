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
    protected string $priority = 'B1'; // 'A', 'B1', 'B2' or null
    protected string $addressPosition = 'right'; // 'left' or 'right'

    public static function getDefaultName(): ?string
    {
        return 'export_batches_pdf';
    }

    public function mailPriority(string $priority): static
    {
        if (!in_array($priority, ['A', 'B1', 'B2'])) {
            throw new \InvalidArgumentException('Invalid priority: ' . $priority);
        }
        $this->priority = $priority;
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
            $batches->load(['commune', 'signatureCollection']);

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

        foreach ($batches as $batch) {
            try {
                $individualPages[] = $batch->get_letter_html($this->addressPosition, $this->priority);
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
            ->body(count($individualPages) . ' out of ' . $batches->count() . ' letters were generated with address position ' . $this->addressPosition . ' and priority ' . $this->priority . '.')
            ->success()
            ->send();

        // Combine all pages with CSS page breaks
        return view('contact.contacts-combined', [
            'pages' => $individualPages,
        ])->render();
    }
}