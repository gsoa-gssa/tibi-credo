<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SVEGeneve extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find all communes in the canton of Geneva
        $communes = \App\Models\Commune::where('canton_id', \App\Models\Canton::where('label', 'GE')->first()->id)
            ->get();

        // Loop through each commune and set the address type to 'svegeneve' except for the communes «Chancy» and «Laconnex»
        foreach ($communes as $commune) {
            if (!in_array($commune->name, ['Chancy', 'Laconnex'])) {
                $commune->addressgroup = 'svegeneve';
                $commune->save();
            }
        }
    }
}
