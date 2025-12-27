<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal for change()
        DB::statement("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL");
        DB::statement("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL");
    }
};
