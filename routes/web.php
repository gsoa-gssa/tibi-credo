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
        $pdf = Pdf::loadView('batch.letter', ['batch' => $batch]);
        return $pdf->stream();
    });

    Route::get('/numerator', function () {
        $numerators = App\Models\Numerator::where("id", ">", 1000)->limit(10)->get();
        $pdf = Pdf::loadView('numerator.demovox', ['numerators' => $numerators]);
        return $pdf->stream();
        // return view('numerator.street', ['numerators' => $numerators]);
    });
}
