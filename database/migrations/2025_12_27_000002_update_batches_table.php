<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename expectedDeliveryDate to expected_delivery_date if it exists
        if (Schema::hasColumn('batches', 'expectedDeliveryDate')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->renameColumn('expectedDeliveryDate', 'expected_delivery_date');
            });
        }

        Schema::table('batches', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('batches', 'expected_return_date')) {
                $table->dateTime('expected_return_date')->nullable()->after('expected_delivery_date');
            }
            if (!Schema::hasColumn('batches', 'open')) {
                $table->boolean('open')->default(false)->after('expected_return_date');
            }
            if (!Schema::hasColumn('batches', 'send_kind')) {
                $table->unsignedBigInteger('send_kind')->nullable()->after('open');
            }
            if (!Schema::hasColumn('batches', 'receive_kind')) {
                $table->unsignedBigInteger('receive_kind')->nullable()->after('send_kind');
            }
            if (!Schema::hasColumn('batches', 'letter_html')) {
                $table->longText('letter_html')->nullable()->after('receive_kind');
            }
        });

        // Add foreign keys with error handling
        Schema::table('batches', function (Blueprint $table) {
            try {
                $table->foreign('send_kind')->references('id')->on('batch_kinds')->cascadeOnDelete();
            } catch (\Exception $e) {
                // Foreign key may already exist
            }
        });

        Schema::table('batches', function (Blueprint $table) {
            try {
                $table->foreign('receive_kind')->references('id')->on('batch_kinds')->cascadeOnDelete();
            } catch (\Exception $e) {
                // Foreign key may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeignKey(['send_kind']);
            $table->dropForeignKey(['receive_kind']);
            $table->dropColumn(['expected_return_date', 'open', 'send_kind', 'receive_kind', 'letter_html']);
        });

        Schema::table('batches', function (Blueprint $table) {
            // Restore original column name
            $table->renameColumn('expected_delivery_date', 'expectedDeliveryDate');
        });
    }
};
