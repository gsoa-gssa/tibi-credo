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
        Schema::table('communes', function (Blueprint $table) {
            $table->foreignId('canton_id')
                ->nullable()
                ->constrained('cantons')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->dropForeign(['canton_id']);
            $table->dropColumn('canton_id');
        });
    }
};
