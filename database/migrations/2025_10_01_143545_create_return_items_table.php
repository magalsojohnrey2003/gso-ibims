<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->onDelete('cascade');
            $table->foreignId('borrow_request_id')->constrained('borrow_requests')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('item_instance_id')->nullable()->constrained('item_instances')->nullOnDelete();
            $table->enum('condition', ['good','minor_damage','major_damage','missing','needs_cleaning'])->default('good');
            $table->text('remarks')->nullable();
            $table->string('photo')->nullable(); // stored path
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
