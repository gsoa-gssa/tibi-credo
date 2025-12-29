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
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->text('return_address_letters')->nullable()->after('return_workdays');
            $table->text('return_address_parcels')->nullable()->after('return_address_letters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->dropColumn('return_address_letters');
            $table->dropColumn('return_address_parcels');
        });
    }
};
