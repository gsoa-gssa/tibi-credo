<?php

use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade\Pdf;

Route::prefix("stats")->group(function () {
    Route::get('/signatures/count', [App\Http\Controllers\StatsController::class, 'viewSignatureCount']);
});


Route::prefix("api")->group(function () {
    Route::prefix("stats")->group(function () {
        Route::get('/signatures/count', [App\Http\Controllers\StatsController::class, 'signatureCount']);
    });
});


if (env('APP_ENV') === 'local') {
    Route::get('/batch-letter', function () {
        $batch = App\Models\Batch::with("sheets")->get()->first();
        $pdf = Pdf::loadView('batch.letter-de', ['batch' => $batch]);
        return $pdf->stream();
    });
}
