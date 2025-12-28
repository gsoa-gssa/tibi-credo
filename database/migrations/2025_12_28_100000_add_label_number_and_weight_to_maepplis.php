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
        // Step 1: Validate all existing labels BEFORE making any changes (including soft-deleted)
        $maepplis = DB::table('maepplis')
            ->leftJoin('communes', 'maepplis.commune_id', '=', 'communes.id')
            ->leftJoin('cantons', 'communes.canton_id', '=', 'cantons.id')
            ->select('maepplis.id', 'maepplis.label', 'cantons.label as canton_label')
            ->get();

        $errors = [];
        $labelPattern = '/^([A-Z]{2})\s*–\s*(\d{4})$/';

        foreach ($maepplis as $maeppli) {
            // Check if label matches the expected format
            if (!preg_match($labelPattern, $maeppli->label, $matches)) {
                $errors[] = "Maeppli ID {$maeppli->id}: Invalid label format '{$maeppli->label}' (expected format: 'XX – 0001')";
                continue;
            }

            $labelCanton = $matches[1];
            $labelNumber = $matches[2];

            // Check if canton code matches the actual canton (only if canton is set)
            if ($maeppli->canton_label && $labelCanton !== $maeppli->canton_label) {
                $errors[] = "Maeppli ID {$maeppli->id}: Label canton '{$labelCanton}' does not match actual canton '{$maeppli->canton_label}' (label: '{$maeppli->label}')";
            }
        }

        // If there are any validation errors, abort the migration
        if (!empty($errors)) {
            $errorMessage = "Migration aborted due to label validation errors:\n\n" . implode("\n", $errors);
            throw new \Exception($errorMessage);
        }

        // Step 2: Add new columns
        Schema::table('maepplis', function (Blueprint $table) {
            $table->integer('label_number')->nullable()->after('label');
            $table->integer('weight_grams')->nullable()->after('signatures_invalid_count');
        });

        // Step 3: Populate label_number from existing labels (including soft-deleted records)
        foreach ($maepplis as $maeppli) {
            preg_match($labelPattern, $maeppli->label, $matches);
            $labelNumber = (int) $matches[2];
            
            DB::table('maepplis')
                ->where('id', $maeppli->id)
                ->update(['label_number' => $labelNumber]);
        }

        // Step 4: Make label_number NOT NULL after populating
        Schema::table('maepplis', function (Blueprint $table) {
            $table->integer('label_number')->nullable(false)->change();
        });

        // Step 5: Drop the old label column
        Schema::table('maepplis', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Re-add the label column
        Schema::table('maepplis', function (Blueprint $table) {
            $table->string('label')->after('commune_id');
        });

        // Step 2: Reconstruct labels from label_number and canton
        $maepplis = DB::table('maepplis')
            ->join('communes', 'maepplis.commune_id', '=', 'communes.id')
            ->join('cantons', 'communes.canton_id', '=', 'cantons.id')
            ->select('maepplis.id', 'maepplis.label_number', 'cantons.label as canton_label')
            ->whereNull('maepplis.deleted_at')
            ->get();

        foreach ($maepplis as $maeppli) {
            $label = $maeppli->canton_label . ' – ' . str_pad($maeppli->label_number, 4, '0', STR_PAD_LEFT);
            DB::table('maepplis')
                ->where('id', $maeppli->id)
                ->update(['label' => $label]);
        }

        // Step 3: Drop the new columns
        Schema::table('maepplis', function (Blueprint $table) {
            $table->dropColumn(['label_number', 'weight_grams']);
        });
    }
};
