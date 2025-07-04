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
            $table->foreignId('maeppli_id')
                ->nullable()
                ->constrained('maepplis')
                ->nullOnDelete()
                ->after('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sheets', function (Blueprint $table) {
            $table->dropForeign(['maeppli_id']);
            $table->dropColumn('maeppli_id');
        });
    }
};
