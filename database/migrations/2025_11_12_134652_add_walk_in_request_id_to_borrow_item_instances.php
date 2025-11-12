<?php

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
        Schema::table('borrow_item_instances', function (Blueprint $table) {
            $table->foreignId('walk_in_request_id')
                ->nullable()
                ->after('borrow_request_id')
                ->constrained('walk_in_requests')
                ->onDelete('cascade');
            
            $table->unsignedInteger('borrowed_qty')->default(0)->after('item_instance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_item_instances', function (Blueprint $table) {
            $table->dropForeign(['walk_in_request_id']);
            $table->dropColumn(['walk_in_request_id', 'borrowed_qty']);
        });
    }
};
