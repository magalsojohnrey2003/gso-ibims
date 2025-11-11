<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('walk_in_requests', function (Blueprint $table) {
            $table->id();
            $table->string('borrower_name');
            $table->string('office_agency')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();
            $table->text('purpose');
            $table->dateTime('borrowed_at');
            $table->dateTime('returned_at');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('walk_in_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('walk_in_request_id')->constrained('walk_in_requests')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walk_in_request_items');
        Schema::dropIfExists('walk_in_requests');
    }
};
