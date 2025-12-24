<?php

namespace App\Filament\Resources\CommuneResource\Pages;

use App\Models\Commune;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class LabelCommune extends Controller
{
    public function __invoke(Commune $commune): View
    {
        return view('filament.pages.label-commune', [
            'record' => $commune,
        ]);
    }
}
