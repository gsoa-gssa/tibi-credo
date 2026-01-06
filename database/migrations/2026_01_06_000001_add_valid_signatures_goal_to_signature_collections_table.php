<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->unsignedInteger('valid_signatures_goal')->default(103000)->after('color');
        });
    }

    public function down() {
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->dropColumn('valid_signatures_goal');
        });
    }
};
