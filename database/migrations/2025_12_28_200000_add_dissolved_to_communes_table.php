<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->boolean('dissolved')->default(false)->after('checked_on');
        });

        // Set dissolved = true when checked_on is NULL, otherwise false
        DB::table('communes')->update([
            'dissolved' => DB::raw('checked_on IS NULL'),
        ]);
    }

    public function down(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->dropColumn('dissolved');
        });
    }
};
