<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            // Add quantity after item_instance_id for clarity
            if (! Schema::hasColumn('return_items', 'quantity')) {
                $table->integer('quantity')->default(1)->after('item_instance_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            if (Schema::hasColumn('return_items', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
