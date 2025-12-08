<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('walk_in_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users');
            $table->string('manpower_role')->nullable()->after('address');
            $table->unsignedInteger('manpower_quantity')->default(10)->after('manpower_role');
            $table->enum('delivery_status', ['pending', 'dispatched', 'delivered'])->default('pending')->after('status');
            $table->timestamp('dispatched_at')->nullable()->after('delivery_status');
            $table->timestamp('delivered_at')->nullable()->after('dispatched_at');
        });

        // For non-MySQL drivers, ensure status supports delivered/returned without altering enum
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN status ENUM('pending', 'approved', 'delivered', 'returned') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE walk_in_requests MODIFY COLUMN status ENUM('pending', 'approved', 'delivered') DEFAULT 'pending'");
        }

        Schema::table('walk_in_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'manpower_role', 'manpower_quantity', 'delivery_status', 'dispatched_at', 'delivered_at']);
        });
    }
};
