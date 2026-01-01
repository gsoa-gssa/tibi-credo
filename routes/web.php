<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Filament\Pages\PublicSignatureSheetList;
use App\Filament\Pages\PublicSignatureSheetShow;
use App\Http\Controllers\PublicSignatureSheetController;

Route::get('/communes/{commune}/label', [App\Filament\Resources\CommuneResource\Pages\LabelCommune::class, '__invoke'])->name('communes.label');

// Combined labels view for multiple communes selected via bulk action
Route::get('/labels/communes', function (Request $request) {
    $idsParam = (string) $request->query('ids');
    $ids = collect(explode(',', $idsParam))
        ->filter()
        ->map(fn($id) => (int) $id)
        ->all();

    $communes = App\Models\Commune::with('zipcodes')
        ->whereIn('id', $ids)
        ->get();

    return response()->view('filament.pages.labels-communes', [
        'communes' => $communes,
    ]);
})->name('labels.communes');

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

// Code login route (guest accessible)
Route::get('/code-login', \App\Livewire\Auth\CodeLogin::class)->name('code-login');

Route::get('/public-signature-sheets', PublicSignatureSheetList::class)
    ->middleware('signed')
    ->name('public.signature-sheets');

Route::get('/public-signature-sheets/{sheet}', PublicSignatureSheetShow::class)
    ->middleware('signed')
    ->name('public.signature-sheets.show');

Route::get('/public-signature-sheets/{sheet}/source/{source}/download', [PublicSignatureSheetController::class, 'download'])
    ->middleware('signed')
    ->name('public.signature-sheets.download');
