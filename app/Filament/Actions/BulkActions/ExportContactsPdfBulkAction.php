<?php

namespace App\Filament\Actions\BulkActions;

use App\Models\Contact;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportContactsPdfBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'export_contacts_pdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Export to PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Export Selected Contacts to PDF')
            ->modalDescription('This will generate a PDF containing all selected contacts.')
            ->modalSubmitActionLabel('Export PDF')
            ->action(function (Collection $records) {
                return $this->exportToPdf($records);
            });
    }

    protected function exportToPdf(Collection $contacts): Response|StreamedResponse
    {
        try {
            // Load relationships to avoid N+1 queries
            $contacts->load(['zipcode.commune', 'sheet', 'contactType']);

            // Generate combined HTML from individual templates
            $combinedHtml = $this->generateCombinedHtml($contacts);

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

            // Generate filename
            $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.pdf';

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
                'contacts_count' => $contacts->count(),
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

    protected function generateCombinedHtml(Collection $contacts): string
    {
        $individualPages = [];

        // Generate HTML for each contact
        foreach ($contacts as $contact) {
            $individualPages[] = view('contact.contact-page', [
                'contact' => $contact,
            ])->render();
        }

        // Combine all pages with CSS page breaks
        return view('contact.contacts-combined', [
            'pages' => $individualPages,
            'totalContacts' => $contacts->count(),
            'exportDate' => now(),
        ])->render();
    }
}