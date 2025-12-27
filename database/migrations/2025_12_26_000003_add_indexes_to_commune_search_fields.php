<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->index('name_with_canton');
            $table->index('name_with_canton_and_zipcode');
        });
    }

    public function down(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->dropIndex(['name_with_canton']);
            $table->dropIndex(['name_with_canton_and_zipcode']);
        });
    }
};
