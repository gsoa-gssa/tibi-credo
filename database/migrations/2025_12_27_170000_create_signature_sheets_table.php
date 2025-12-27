<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signature_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_collection_id')->constrained()->cascadeOnDelete();
            $table->string('short_name');
            $table->text('description_internal')->nullable();
            $table->string('sheet_pdf');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_sheets');
    }
};
