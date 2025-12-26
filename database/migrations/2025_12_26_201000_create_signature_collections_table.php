<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_collections', function (Blueprint $table) {
            $table->id();
            $table->string('official_name_de');
            $table->string('official_name_fr');
            $table->string('official_name_it');
            $table->date('publication_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('responsible_person_name_de');
            $table->string('responsible_person_email_de');
            $table->string('responsible_person_phone_de');
            $table->string('responsible_person_name_fr');
            $table->string('responsible_person_email_fr');
            $table->string('responsible_person_phone_fr');
            $table->string('responsible_person_name_it');
            $table->string('responsible_person_email_it');
            $table->string('responsible_person_phone_it');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_collections');
    }
};
