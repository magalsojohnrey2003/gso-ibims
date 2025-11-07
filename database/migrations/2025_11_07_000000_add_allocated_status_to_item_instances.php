<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add 'allocated' to the enum values for item_instances.status
        // We use raw SQL since altering ENUM is easiest this way and avoids extra packages.
        DB::statement("ALTER TABLE `item_instances` MODIFY `status` ENUM('available','allocated','borrowed','damaged','under_repair','retired','missing') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        // Remove 'allocated' from enum. Note: any rows with 'allocated' will be converted to the default.
        DB::statement("ALTER TABLE `item_instances` MODIFY `status` ENUM('available','borrowed','damaged','under_repair','retired','missing') NOT NULL DEFAULT 'available'");
    }
};
