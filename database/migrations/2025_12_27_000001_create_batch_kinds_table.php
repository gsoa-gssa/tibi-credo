<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_kinds', function (Blueprint $table) {
            $table->id();
            $table->string('short_name_de');
            $table->string('short_name_fr');
            $table->string('short_name_it');
            $table->text('subject_de')->nullable();
            $table->text('subject_fr')->nullable();
            $table->text('subject_it')->nullable();
            $table->longText('body_de')->nullable();
            $table->longText('body_fr')->nullable();
            $table->longText('body_it')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_kinds');
    }
};
