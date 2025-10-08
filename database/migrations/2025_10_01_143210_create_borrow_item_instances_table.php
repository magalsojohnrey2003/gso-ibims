<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('borrow_item_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_request_id')->constrained('borrow_requests')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('item_instance_id')->constrained('item_instances')->onDelete('cascade');
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('expected_return_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_item_instances');
    }
};
