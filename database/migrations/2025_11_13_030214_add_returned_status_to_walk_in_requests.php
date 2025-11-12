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
        Schema::table('walk_in_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN status ENUM('pending', 'approved', 'delivered', 'returned') DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('walk_in_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN status ENUM('pending', 'approved', 'delivered') DEFAULT 'pending'");
        });
    }
};
