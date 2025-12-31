<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->integer('sheets_printed')->nullable(true);
            $table->float('addition_cost')->nullable(true);
            $table->text('comments')->nullable(true);
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn('sheets_printed');
            $table->dropColumn('addition_cost');
            $table->dropColumn('comments');
        });
    }
};
