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
        Schema::create('contact_types', function (Blueprint $table) {
            $table->id();
            $table
                ->string('name')
                ->unique();
            $table
                ->string('description')
                ->nullable();
            $table
                ->string('subject_de');
            $table
                ->string('subject_fr');
            $table
                ->string('subject_it');
            $table
                ->string('body_de');
            $table
                ->string('body_fr');
            $table
                ->string('body_it');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_types');
    }
};
