<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->text('short_description_de')->nullable();
            $table->text('short_description_fr')->nullable();
            $table->text('short_description_it')->nullable();
        });

        // Migrate data from label JSON to new fields
        DB::table('sources')->select(['id', 'label'])->orderBy('id')->chunk(100, function ($sources) {
            foreach ($sources as $source) {
                $label = json_decode($source->label, true);
                DB::table('sources')->where('id', $source->id)->update([
                    'short_description_de' => $label['de'] ?? null,
                    'short_description_fr' => $label['fr'] ?? null,
                    'short_description_it' => $label['it'] ?? null,
                ]);
            }
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->json('label')->nullable();
        });

        // Restore label JSON from the three fields
        DB::table('sources')->select(['id', 'short_description_de', 'short_description_fr', 'short_description_it'])->orderBy('id')->chunk(100, function ($sources) {
            foreach ($sources as $source) {
                $label = [
                    'de' => $source->short_description_de,
                    'fr' => $source->short_description_fr,
                    'it' => $source->short_description_it,
                ];
                DB::table('sources')->where('id', $source->id)->update([
                    'label' => json_encode($label),
                ]);
            }
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn(['short_description_de', 'short_description_fr', 'short_description_it']);
        });
    }
};
