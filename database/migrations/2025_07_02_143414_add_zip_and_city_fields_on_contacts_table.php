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
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('zipcode_id')
                ->nullable()
                ->after('street_no')
                ->constrained('zipcodes')
                ->nullOnDelete();
            $table->string('city')
                ->nullable()
                ->after('zipcode_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['zipcode_id']);
            $table->dropColumn('zipcode_id');
            $table->dropColumn('city');
        });
    }
};
