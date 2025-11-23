<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE manpower_requests MODIFY COLUMN status ENUM('pending','validated','approved','rejected') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE manpower_requests MODIFY COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending'");
    }
};
