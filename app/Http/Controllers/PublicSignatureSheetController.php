<?php

namespace App\Http\Controllers;

use App\Models\SignatureSheet;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicSignatureSheetController extends Controller
{
    public function download(Request $request, SignatureSheet $sheet, Source $source)
    {
        $scopeId = $request->query('signature_collection_id');
        abort_unless($request->hasValidSignature() && $sheet->signature_collection_id == $scopeId, 403);
        return $sheet->download($source);
    }
}
