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
        Schema::table('maepplis', function (Blueprint $table) {
            $table->integer('sheets_count')->default(0)->after('label');
            $table->integer('sheets_valid_count')->default(0)->after('sheets_count');
            $table->integer('sheets_invalid_count')->default(0)->after('sheets_valid_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->dropColumn(['sheets_count', 'sheets_valid_count', 'sheets_invalid_count']);
        });
    }
};
