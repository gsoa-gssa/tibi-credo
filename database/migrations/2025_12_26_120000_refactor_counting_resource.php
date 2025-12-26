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
        Schema::table('countings', function (Blueprint $table) {
            // Drop the source column (using source_id foreign key instead)
            $table->dropColumn('source');
            
            // Add paper_format boolean (true = A5, false = A4)
            $table->boolean('paper_format')->default(false)->after('source_id');
            
            // Change date from datetime to date
            $table->date('date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countings', function (Blueprint $table) {
            $table->string('source')->nullable();
            $table->dropColumn('paper_format');
            $table->dateTime('date')->change();
        });
    }
};
