<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->float('source_x', 8, 2)->change();
            $table->float('source_y', 8, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->integer('source_x')->change();
            $table->integer('source_y')->change();
        });
    }
};
