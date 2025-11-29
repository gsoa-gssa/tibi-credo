<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->foreignId('box_id')
                ->nullable()
                ->constrained('boxes')
                ->nullOnDelete()
                ->after('commune_id');
        });
    }

    public function down(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->dropForeign(['box_id']);
            $table->dropColumn('box_id');
        });
    }
};
