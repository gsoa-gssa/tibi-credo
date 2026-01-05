<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->dropColumn('sheet_pdf_hash');
        });
    }

    public function down()
    {
        Schema::table('signature_sheets', function (Blueprint $table) {
            $table->string('sheet_pdf_hash')->nullable()->after('sheet_pdf_compat');
        });
    }
};
