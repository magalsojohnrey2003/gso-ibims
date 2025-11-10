<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add 'allocated' to the enum values for item_instances.status
        // MySQL uses raw SQL, SQLite doesn't have ENUMs so we skip
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `item_instances` MODIFY `status` ENUM('available','allocated','borrowed','damaged','under_repair','retired','missing') NOT NULL DEFAULT 'available'");
        }
        // For SQLite, the status column is already a string, so no modification needed
    }

    public function down(): void
    {
        // Remove 'allocated' from enum
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `item_instances` MODIFY `status` ENUM('available','borrowed','damaged','under_repair','retired','missing') NOT NULL DEFAULT 'available'");
        }
    }
};
