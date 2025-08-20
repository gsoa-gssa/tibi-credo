<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sheets', function (Blueprint $table) {
            // Add composite index for batch_id and label columns
            $table->index(['batch_id', 'label'], 'sheets_batch_id_label_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sheets', function (Blueprint $table) {
            $table->dropIndex('sheets_batch_id_label_index');
        });
    }
};
