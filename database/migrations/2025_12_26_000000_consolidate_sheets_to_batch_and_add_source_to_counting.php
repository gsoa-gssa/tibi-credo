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
        // Add signature_count to batches
        Schema::table('batches', function (Blueprint $table) {
            $table->integer('signature_count')->default(0)->after('commune_id');
        });

        // Add source_id foreign key to countings
        Schema::table('countings', function (Blueprint $table) {
            $table->foreignId('source_id')->nullable()->after('id')->constrained()->onDelete('set null');
        });

        // Migrate sheets data to batches: sum all signatures per batch
        \DB::statement('
            UPDATE batches
            SET signature_count = (
                SELECT COALESCE(SUM(sheets.signatureCount), 0)
                FROM sheets
                WHERE sheets.batch_id = batches.id
                AND sheets.deleted_at IS NULL
            )
            WHERE id IN (
                SELECT DISTINCT batch_id FROM sheets WHERE batch_id IS NOT NULL
            )
        ');

        // Disable foreign key checks to drop sheets table
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('sheets');
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore sheets table
        Schema::create('sheets', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->integer('signatureCount')->default(0);
            $table->boolean('vox')->default(false);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('commune_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('source_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('maeppli_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('recorded');
            $table->integer('numerator_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Remove source_id from countings
        Schema::table('countings', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
            $table->dropColumn('source_id');
        });

        // Remove signature_count from batches
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('signature_count');
        });
    }
};
