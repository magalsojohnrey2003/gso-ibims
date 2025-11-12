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
            $table->dropForeign(['borrow_request_id']);
            
            // Make column nullable
            $table->foreignId('borrow_request_id')
                ->nullable()
                ->change();
            
            // Re-add foreign key constraint
            $table->foreign('borrow_request_id')
                ->references('id')
                ->on('borrow_requests')
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
            $table->dropForeign(['borrow_request_id']);
            
            // Make column not nullable again
            $table->foreignId('borrow_request_id')
                ->nullable(false)
                ->change();
            
            // Re-add foreign key constraint
            $table->foreign('borrow_request_id')
                ->references('id')
                ->on('borrow_requests')
                ->onDelete('cascade');
        });
    }
};
