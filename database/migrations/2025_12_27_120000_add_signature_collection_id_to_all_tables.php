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
        $tables = [
            'users',
            'batches',
            'maepplis',
            'boxes',
            'contacts',
            'contact_types',
            'countings',
        ];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'signature_collection_id')) {
                    $table->unsignedBigInteger('signature_collection_id')->default(2);
                    $table->index('signature_collection_id');
                    $table->foreign('signature_collection_id')->references('id')->on('signature_collections');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'batches',
            'maepplis',
            'boxes',
            'contacts',
            'contact_types',
            'countings',
        ];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'signature_collection_id')) {
                    $table->dropForeign([$tableName.'_signature_collection_id_foreign']);
                    $table->dropIndex([$tableName.'_signature_collection_id_index']);
                    $table->dropColumn('signature_collection_id');
                }
            });
        }
    }
};
