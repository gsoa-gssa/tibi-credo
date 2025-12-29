<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->integer('source_x')->nullable();
            $table->integer('source_y')->nullable();
            $table->integer('source_font_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->dropColumn(['source_x', 'source_y', 'source_font_size']);
        });
    }
};
