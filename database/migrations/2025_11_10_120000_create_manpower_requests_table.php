<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manpower_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('role', 100);
            $table->string('purpose', 255);
            $table->string('location', 255);
            $table->string('office_agency', 255)->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('letter_path')->nullable();
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->string('rejection_reason_subject')->nullable();
            $table->text('rejection_reason_detail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpower_requests');
    }
};
