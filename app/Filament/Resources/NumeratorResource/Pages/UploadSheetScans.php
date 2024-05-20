<?php

namespace App\Filament\Resources\NumeratorResource\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\NumeratorResource;
use Filament\Forms\Concerns\InteractsWithForms;
use setasign\Fpdi\Fpdi;

class UploadSheetScans extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = NumeratorResource::class;

    protected static string $view = 'filament.resources.numerator-resource.pages.upload-sheet-scans';

    public $sheetScans;

    public ?array $data = [
        "sheetScans" => null
    ];

    public function mount($data = [])
    {
        $this->data = $data;
        if (isset($data["sheetScans"])) {
            $this->data["sheetScans"] = $data["sheetScans"];
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('sheetScans')
                ->label('Sheet Scans')
                ->required()
                ->acceptedFileTypes(['application/pdf'])
                ->disk('public')
                ->directory('sheet-scans')
        ])->statePath("data");
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload')
                ->submit('uploadSheetScans')
        ];
    }

    public function uploadSheetScans()
    {
        $this->data = $this->form->getState();
        $sheetScansFileName = $this->data['sheetScans'];
        $sheetScans = $this->splitPDF(storage_path('app/public/' . $sheetScansFileName));
        // Delete the original file
        unlink(storage_path('app/public/' . $sheetScansFileName));
        \Filament\Notifications\Notification::make()
            ->success()
            ->seconds(15)
            ->title(
                'Sheet Scans Uploaded',
            )
            ->send();
        $this->redirect(NumeratorResource::getUrl("index"));
    }


    public function splitPDF($filename)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filename);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $newPdf = new Fpdi();
            $newPdf->AddPage();
            $newPdf->setSourceFile($filename);
            $newPdf->useTemplate($newPdf->importPage($pageNo));
            $newFilename = str_replace('.pdf', '', $filename) . '-' . $pageNo . '.pdf';
            $newPdf->Output($newFilename, 'F');
            // Move file from current folder to sheet-scans/unassigned folder
            rename($newFilename, storage_path('app/public/sheet-scans/unassigned/' . basename($newFilename)));
        }
    }
}
