<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->string('authority_address_name')->nullable();
            $table->string('authority_address_street')->nullable();
            $table->string('authority_address_house_number')->nullable();
            $table->string('authority_address_extra')->nullable();
            $table->string('authority_address_postcode', 4)->nullable();
            $table->string('authority_address_place')->nullable();
            $table->boolean('address_checked')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->dropColumn([
                'authority_address_name',
                'authority_address_street',
                'authority_address_house_number',
                'authority_address_extra',
                'authority_address_postcode',
                'authority_address_place',
                'address_checked',
            ]);
        });
    }
};
