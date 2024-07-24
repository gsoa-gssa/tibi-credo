<?php

use App\Models\Sheet;
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
        Sheet::truncate();
        Schema::table('sheets', function (Blueprint $table) {
            $table->dropColumn('numerator_id');
            $table->string('label')->after('id');
        });
        Schema::dropIfExists('numerators');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('numerators', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['street', 'A5', 'demovox']);
        });
        Schema::table('sheets', function (Blueprint $table) {
            $table->dropColumn('label');
            $table->foreignId('numerator_id')->nullable();
        });
    }
};
