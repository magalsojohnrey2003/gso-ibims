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
        Schema::table('borrow_requests', function (Blueprint $table) {
            $table->foreignId('rejection_reason_id')
                ->nullable()
                ->constrained('rejection_reasons')
                ->nullOnDelete();
            $table->string('reject_category')->nullable();
            $table->text('reject_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejection_reason_id');
            $table->dropColumn(['reject_category', 'reject_reason']);
        });
    }
};
