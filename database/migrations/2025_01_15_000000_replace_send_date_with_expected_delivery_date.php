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
        Schema::table('batches', function (Blueprint $table) {
            // Drop the old sendDate column and add expectedDeliveryDate
            $table->dropColumn('sendDate');
            $table->date('expectedDeliveryDate')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            // Restore the original sendDate and remove expectedDeliveryDate
            $table->dropColumn('expectedDeliveryDate');
            $table->date('sendDate')->nullable()->after('status');
        });
    }
};
