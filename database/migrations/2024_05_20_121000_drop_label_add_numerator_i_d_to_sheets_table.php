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
            $table->dropColumn('label');
            $table->foreignId('numerator_id')->constrained("numerators")->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sheets', function (Blueprint $table) {
            $table->string('label');
            $table->dropForeign(['numerator_id']);
            $table->dropColumn('numerator_id');
        });
    }
};
