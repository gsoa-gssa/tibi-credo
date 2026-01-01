<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->string('sheet_pdf_compat')->nullable()->after('sheet_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->dropColumn('sheet_pdf_compat');
        });
    }
};
