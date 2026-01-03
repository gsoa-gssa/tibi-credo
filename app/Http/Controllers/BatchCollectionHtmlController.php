<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BatchCollectionHtmlController extends Controller
{
    /**
     * Serve combined HTML for a collection of batches.
     * Expects query param `ids` as comma-separated list of batch ids.
     * Optional query params: `addressPosition` ('left'|'right'), `priority` ('A'|'B1'|'B2').
     */
    public function show(Request $request)
    {
        \Log::debug('BatchCollectionHtmlController@show called', ['query' => $request->query()]);
        $ids = $request->query('ids');
        if (empty($ids)) {
            return response('Missing ids parameter', 400);
        }

        $idsArray = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($idsArray)) {
            return response('No valid ids provided', 400);
        }

        $addressPosition = $request->query('addressPosition', 'right');
        if (!in_array($addressPosition, ['left', 'right'])) {
            return response('Invalid addressPosition', 400);
        }

        $priority = $request->query('priority', 'B1');
        if (!in_array($priority, ['A', 'B1', 'B2'])) {
            return response('Invalid priority', 400);
        }

        $batches = Batch::whereIn('id', $idsArray)
            ->with(['commune', 'signatureCollection'])
            ->get();

        if ($batches->isEmpty()) {
            return response('No batches found', 404);
        }

        // check can access for all batches
        foreach ($batches as $batch) {
            if (!auth()->user()->can('view', $batch)) {
                return response('Unauthorized', 403);
            }
        }

        try {
            $html = Batch::get_letter_html_many($batches, $addressPosition, $priority);
        } catch (\Exception $e) {
            Log::error('Failed to generate combined batch HTML: ' . $e->getMessage(), ['exception' => $e]);
            return response('Failed to generate HTML', 500);
        }

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }
}
