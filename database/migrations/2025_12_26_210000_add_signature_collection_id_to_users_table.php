<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('signature_collection_id')->default(2)->after('id');
            $table->foreign('signature_collection_id')->references('id')->on('signature_collections');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['signature_collection_id']);
            $table->dropColumn('signature_collection_id');
        });
    }
};
