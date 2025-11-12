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
            // Drop foreign key first
            $table->dropForeign(['item_instance_id']);
            
            // Make column nullable
            $table->foreignId('item_instance_id')
                ->nullable()
                ->change();
            
            // Re-add foreign key constraint
            $table->foreign('item_instance_id')
                ->references('id')
                ->on('item_instances')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_item_instances', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['item_instance_id']);
            
            // Make column not nullable again
            $table->foreignId('item_instance_id')
                ->nullable(false)
                ->change();
            
            // Re-add foreign key constraint
            $table->foreign('item_instance_id')
                ->references('id')
                ->on('item_instances')
                ->onDelete('cascade');
        });
    }
};
