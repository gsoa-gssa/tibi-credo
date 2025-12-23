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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commune_id')->constrained()->onDelete('cascade');
            $table->foreignId('zipcode_id')->constrained()->onDelete('cascade');
            $table->string('street_name', 120)->nullable();
            $table->string('street_number', 10)->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('zipcode_id', 'idx_zipcode');
            $table->index('commune_id', 'idx_commune');
            $table->index(['zipcode_id', 'street_name'], 'idx_zip_street');
            $table->index(['commune_id', 'street_name'], 'idx_commune_street');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
