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
            $table->string('pp_sender_zipcode', 16)->nullable()->after('return_address_parcels');
            $table->string('pp_sender_place_de')->nullable()->after('pp_sender_zipcode');
            $table->string('pp_sender_place_fr')->nullable()->after('pp_sender_place_de');
            $table->string('pp_sender_place_it')->nullable()->after('pp_sender_place_fr');
            $table->string('pp_sender_name_de')->nullable()->after('pp_sender_place_it');
            $table->string('pp_sender_name_fr')->nullable()->after('pp_sender_name_de');
            $table->string('pp_sender_name_it')->nullable()->after('pp_sender_name_fr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->dropColumn([
                'pp_sender_zipcode',
                'pp_sender_place_de',
                'pp_sender_place_fr',
                'pp_sender_place_it',
                'pp_sender_name_de',
                'pp_sender_name_fr',
                'pp_sender_name_it',
            ]);
        });
    }
};
