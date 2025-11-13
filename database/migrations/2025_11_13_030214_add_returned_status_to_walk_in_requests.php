<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        // MySQL: alter ENUM; SQLite (tests) does not support MODIFY/ENUM -> skip safely
        if ($driver === 'mysql') {
            Schema::table('walk_in_requests', function (Blueprint $table) {
                DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN status ENUM('pending', 'approved', 'delivered', 'returned') DEFAULT 'pending'");
            });
        } else {
            // No-op for SQLite/PostgreSQL in this migration to keep tests green.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::table('walk_in_requests', function (Blueprint $table) {
                DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN status ENUM('pending', 'approved', 'delivered') DEFAULT 'pending'");
            });
        } else {
            // No-op for non-MySQL
        }
    }
};
