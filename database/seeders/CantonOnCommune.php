<?php

namespace Database\Seeders;

use App\Models\Canton;
use App\Models\Commune;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CantonOnCommune extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate the cantons database table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Canton::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create the 26 swiss cantons with their abbreviations in the «label» columne and names in german, french, italian, and english as a json object in the «name» column
        $cantons = [
            ['label' => 'AG', 'name' => json_encode(['de' => 'Aargau', 'fr' => 'Argovie', 'it' => 'Argovia', 'en' => 'Aargau'])],
            ['label' => 'AI', 'name' => json_encode(['de' => 'Appenzell Innerrhoden', 'fr' => 'Appenzell Rhodes-Intérieures', 'it' => 'Appenzello Interno', 'en' => 'Appenzell Innerrhoden'])],
            ['label' => 'AR', 'name' => json_encode(['de' => 'Appenzell Ausserrhoden', 'fr' => 'Appenzell Rhodes-Extérieures', 'it' => 'Appenzello Esterno', 'en' => 'Appenzell Ausserrhoden'])],
            ['label' => 'BE', 'name' => json_encode(['de' => 'Bern', 'fr' => 'Berne', 'it' => 'Berna', 'en' => 'Bern'])],
            ['label' => 'BL', 'name' => json_encode(['de' => 'Basel-Landschaft', 'fr' => 'Bâle-Campagne', 'it' => 'Basilea Campagna', 'en' => 'Basel-Landschaft'])],
            ['label' => 'BS', 'name' => json_encode(['de' => 'Basel-Stadt', 'fr' => 'Bâle-Ville', 'it' => 'Basilea Città', 'en' => 'Basel-Stadt'])],
            ['label' => 'FR', 'name' => json_encode(['de' => 'Freiburg', 'fr' => "Fribourg",  "it" => "Friburgo", "en" => "Fribourg"])],
            ['label' => 'GE',  "name"  => json_encode(["de"  => "Genf", "fr"  => "Genève", "it"  => "Ginevra", "en"  => "Geneva"])],
            ['label'  =>  "GL",  "name"  =>  json_encode(["de"  =>  "Glarus",  "fr"  =>  "Glaris",  "it"  =>  "Glarona",  "en"  =>  "Glarus"])],
            ['label'=>   "GR",   "name"=>   json_encode(["de"=>   "Graubünden",   "fr"=>   "Grisons",   "it"=>   "Grigioni",   "en"=>   "Grisons"])],
            ['label' => 'JU', 'name' => json_encode(['de' => 'Jura', 'fr' => 'Jura', 'it' => 'Giura', 'en' => 'Jura'])],
            ['label' => 'LU', 'name' => json_encode(['de' => 'Luzern', 'fr' => 'Lucerne', 'it' => 'Lucerna', 'en' => 'Lucerne'])],
            ['label' => 'NE', 'name' => json_encode(['de' => 'Neuenburg', 'fr' => 'Neuchâtel', 'it' => 'Neuchâtel', 'en' => 'Neuchâtel'])],
            ['label' => 'NW', 'name' => json_encode(['de' => 'Nidwalden', 'fr' => 'Nidwald', 'it' => 'Nidvaldo', 'en' => 'Nidwalden'])],
            ['label' => 'OW',  "name"  =>  json_encode(["de"  =>  "Obwalden",  "fr"  =>  "Obwald",  "it"  =>  "Obvaldo",  "en"  =>  "Obwalden"])],
            ['label'=>   "SG",   "name"=>   json_encode(["de"=>   "St. Gallen",   "fr"=>   "Saint-Gall",   "it"=>   "San Gallo",   "en"=>   "St. Gallen"])],
            ['label'=>   "SH",   "name"=>   json_encode(["de"=>   "Schaffhausen",   "fr"=>   "Schaffhouse",   "it"=>   "Sciaffusa",   "en"=>   "Schaffhausen"])],
            ['label'=>   "SO",   "name"=>   json_encode(["de"=>   "Solothurn",   "fr"=>   "Soleure",   "it"=>   "Soletta",   "en"=>   "Solothurn"])],
            ['label'=>   "SZ",   "name"=>   json_encode(["de"=>   "Schwyz",   "fr"=>   "Schwytz",   "it"=>   "Svitto",   "en"=>   "Schwyz"])],
            ['label'=>    "TG",    "name"    =>    json_encode(["de"    =>    "Thurgau",    "fr"    =>    "Thurgovie",    "it"    =>    "Turgovia",    "en"    =>    "Thurgau"])],
            ['label' => 'TI', 'name' => json_encode(['de' => 'Tessin', 'fr' => 'Tessin', 'it' => 'Ticino', 'en' => 'Ticino'])],
            ['label' => 'UR', 'name' => json_encode(['de' => 'Uri', 'fr' => 'Uri', 'it' => 'Uri', 'en' => 'Uri'])],
            ['label' => 'VD', 'name' => json_encode(['de' => 'Waadt', 'fr' => 'Vaud', 'it' => 'Vaud', 'en' => 'Vaud'])],
            ['label' => 'VS', 'name' => json_encode(['de' => 'Wallis', 'fr' => 'Valais', 'it' => 'Vallese', 'en' => 'Valais'])],
            ['label' => 'ZG',  "name"  =>  json_encode(["de"  =>  "Zug",  "fr"  =>  "Zoug",  "it"  =>  "Zugo",  "en"  =>  "Zug"])],
            ['label'=>   "ZH",   "name"=>   json_encode(["de"=>   "Zürich",   "fr"=>   "Zurich",   "it"=>   "Zurigo",   "en"=>   "Zurich"])],
        ];

        // Insert the cantons into the database
        foreach ($cantons as $canton) {
            Canton::create($canton);
        }

        // Read from csv file at ressource_path("data/Gemeindestand.csv") into searchable array called $gemeindestand
        $gemeindestand = collect(
            array_map('str_getcsv', file(resource_path("data/Gemeindestand.csv")))
        )->map(function ($row) {
            return [
                'canton' => $row[1],
                'officialId' => $row[4],
            ];
        })->keyBy('officialId');

        // Get all communes from the database and loop through them
        $communes = Commune::all();
        foreach ($communes as $commune) {
            // Get the canton label from the $gemeindestand array using the commune's officialId
            $cantonLabel = $gemeindestand->get($commune->officialId, null)['canton'] ?? null;
            if ($cantonLabel) {
                // Find the canton by label
                $canton = Canton::where('label', $cantonLabel)->first();
                if ($canton) {
                    // Associate the canton with the commune
                    $commune->canton()->associate($canton);
                    $commune->save();
                } else {
                    // If the canton is not found, you can log an error or handle it as needed
                    Log::error("Canton with label {$cantonLabel} not found for commune with officialId {$commune->officialId}");
                }
            } else {
                // If the canton label is not found in the $gemeindestand array, you can
                // log an error or handle it as needed
                Log::error("Canton label not found for commune with officialId {$commune->officialId}");
            }
        }
    }
}
