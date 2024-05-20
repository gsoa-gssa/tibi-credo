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
        Schema::create('sheets', function (Blueprint $table) {
            $table->id();
            $table->integer('label');
            $table->string('source');
            $table->integer('signatureCount');
            $table->integer('verifiedCount')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('commune_id');
            $table->foreignId('batch_id')->nullable()->onDelete('set null');
            $table->enum('status', ["recorded","added2batch","processed","faulty"])->default('recorded');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheets');
    }
};
