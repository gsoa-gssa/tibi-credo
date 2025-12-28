<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->renameColumn('sheets_valid_count', 'signatures_valid_count');
            $table->renameColumn('sheets_invalid_count', 'signatures_invalid_count');
        });
    }

    public function down(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->renameColumn('signatures_valid_count', 'sheets_valid_count');
            $table->renameColumn('signatures_invalid_count', 'sheets_invalid_count');
        });
    }
};
