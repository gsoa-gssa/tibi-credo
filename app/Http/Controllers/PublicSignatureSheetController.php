<?php

namespace App\Http\Controllers;

use App\Models\SignatureSheet;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicSignatureSheetController extends Controller
{
    public function index(Request $request)
    {
        $scopeId = $request->query('signature_collection_id');
        abort_unless($request->hasValidSignature() && $scopeId, 403);

        $sheets = SignatureSheet::where('signature_collection_id', $scopeId)->get();

        return view('public.signature-sheets.index', compact('sheets', 'scopeId'));
    }

    public function show(Request $request, SignatureSheet $sheet)
    {
        $scopeId = $request->query('signature_collection_id');
        abort_unless($request->hasValidSignature() && $sheet->signature_collection_id == $scopeId, 403);

        return view('public.signature-sheets.show', compact('sheet', 'scopeId'));
    }

    public function download(Request $request, SignatureSheet $sheet, Source $source)
    {
        $scopeId = $request->query('signature_collection_id');
        abort_unless($request->hasValidSignature() && $sheet->signature_collection_id == $scopeId, 403);

        // Replicate the download logic from SourcesRelationManager
        if (!$sheet->sheet_pdf) {
            abort(404, 'No PDF found on this sheet');
        }

        $path = \Storage::disk('public')->path($sheet->sheet_pdf);
        if (!file_exists($path)) {
            abort(404, 'PDF file is missing in storage');
        }

        $tempDecompressedPath = tempnam(sys_get_temp_dir(), 'pdf_decomp_');
        try {
            $process = new \Symfony\Component\Process\Process([
                'gs',
                '-q',
                '-dNOPAUSE',
                '-dBATCH',
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-sOutputFile=' . $tempDecompressedPath,
                $path,
            ]);
            $process->mustRun();
            if (!file_exists($tempDecompressedPath) || filesize($tempDecompressedPath) === 0) {
                abort(500, 'Failed to decompress PDF');
            }
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($tempDecompressedPath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
                if ($pageNo === $sheet->source_page_number) {
                    $pdf->SetFont('Courier', '', $sheet->source_font_size);
                    $pdf->SetTextColor(0, 0, 0);
                    $textWidth = $pdf->GetStringWidth((string) $source->code);
                    $pdf->Text($sheet->source_x - $textWidth / 2, $sheet->source_y, (string) $source->code);
                }
            }
            $fileName = sprintf('%s-%s.pdf', $sheet->short_name ?? 'sheet', $source->code ?? 'source');
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->Output('S');
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            @unlink($tempDecompressedPath);
            abort(500, 'Error processing PDF: ' . $e->getMessage());
        }
    }
}
