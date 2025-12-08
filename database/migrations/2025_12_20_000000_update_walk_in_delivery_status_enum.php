<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN delivery_status ENUM('pending','dispatched','delivered','not_received','returned') DEFAULT 'pending'");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN delivery_status ENUM('pending','dispatched','delivered') DEFAULT 'pending'");
    }
};
