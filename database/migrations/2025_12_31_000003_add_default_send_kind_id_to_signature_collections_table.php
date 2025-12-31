<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->unsignedBigInteger('default_send_kind_id')->nullable()->after('id');
            $table->foreign('default_send_kind_id')->references('id')->on('batch_kinds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('signature_collections', function (Blueprint $table) {
            $table->dropForeign(['default_send_kind_id']);
            $table->dropColumn('default_send_kind_id');
        });
    }
};
