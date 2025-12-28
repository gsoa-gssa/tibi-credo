<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->integer('sheets_count')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('maepplis', function (Blueprint $table) {
            $table->integer('sheets_count')->nullable(false)->change();
        });
    }
};
