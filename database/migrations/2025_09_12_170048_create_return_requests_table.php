<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_request_id')->constrained('borrow_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('condition', ['good', 'fair', 'damaged']);
            $table->text('damage_reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete(); // Admin who processes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
