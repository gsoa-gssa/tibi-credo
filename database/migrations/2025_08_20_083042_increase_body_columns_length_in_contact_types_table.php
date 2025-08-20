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
        Schema::table('contact_types', function (Blueprint $table) {
            // were string, limit too low, text seems to have a higher limit
            $table->text('body_de')->nullable()->change();
            $table->text('body_fr')->nullable()->change();
            $table->text('body_it')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_types', function (Blueprint $table) {
            $table->string('body_de')->change();
            $table->string('body_fr')->change();
            $table->string('body_it')->change();
        });
    }
};
