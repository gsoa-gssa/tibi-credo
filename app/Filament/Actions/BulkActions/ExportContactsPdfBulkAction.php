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

    protected bool $priorityMail = false;
    protected string $addressPosition = 'left'; // 'left' or 'right'

    public static function getDefaultName(): ?string
    {
        return 'export_contacts_pdf';
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('contact.letter.action.label'))
            ->icon('heroicon-o-document-arrow-down')
            ->requiresConfirmation()
            ->modalHeading(__('contact.letter.action.heading'))
            ->modalDescription(__('contact.letter.action.description'))
            ->modalSubmitActionLabel(__('contact.letter.action.confirm'))
            ->action(function (Collection $records) {
                return $this->exportToPdf($records);
            });
    }

    protected function exportToPdf(Collection $contacts): Response|StreamedResponse
    {
        try {
            // Load relationships to avoid N+1 queries
            $contacts->load(['zipcode', 'zipcode.commune', 'sheet', 'contactType']);

            // Generate combined HTML from individual templates
            $combinedHtml = $this->generateCombinedHtml($contacts);

            // Generate filename
            $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.pdf';

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


            // for all contacts, set letter_sent to current timestamp
            foreach ($contacts as $contact) {
                $contact->update(['letter_sent' => now()]);
            }

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
            $replacementDict = [
                '|firstname|' => $contact->firstname,
                '|lastname|' => $contact->lastname,
                '|street_no|' => $contact->street_no,
                '|zipcode|' => $contact->zipcode->code,
                '|place|' => $contact->zipcode->name,
                '|contactType|' => $contact->contactType,
            ];
            try {
                // change locale to render in correct language
                $currentLocale = (string) app()->getLocale();
                app()->setLocale($contact->lang);
                $individualPages[] = view('contact.contact-page', [
                    'contact' => $contact,
                    'language' => $contact->lang,
                    'replacementDict' => $replacementDict,
                    'addressPosition' => $this->addressPosition,
                    'priorityMail' => $this->priorityMail,
                ])->render();
                app()->setLocale($currentLocale); // Reset locale after rendering
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Contact Export Skipped')
                    ->body('Contact ' . $contact->id . ' could not be rendered and was skipped: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        \Filament\Notifications\Notification::make()
            ->title('Contacts Processed')
            ->body(count($individualPages) . ' out of ' . $contacts->count() . ' letters were generated with address position ' . $this->addressPosition . ' and priority mail ' . ($this->priorityMail ? 'enabled' : 'disabled') . '.')
            ->success()
            ->send();

        // Combine all pages with CSS page breaks
        return view('contact.contacts-combined', [
            'pages' => $individualPages,
        ])->render();
    }
}