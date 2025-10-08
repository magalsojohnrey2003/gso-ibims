<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_instance_id')->constrained('item_instances')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('borrow_request_id')->nullable()->constrained('borrow_requests')->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('description');
            $table->json('photos')->nullable();
            $table->enum('status', ['reported', 'reviewed', 'resolved'])->default('reported');
            $table->timestamps();

            $table->index(['item_instance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_damage_reports');
    }
};
