<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_instance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_type')->default('user');
            $table->string('action');
            $table->json('payload')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();

            $table->index(['item_instance_id', 'action']);
            $table->index(['item_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_instance_events');
    }
};
