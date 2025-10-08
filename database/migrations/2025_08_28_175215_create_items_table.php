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
    Schema::create('items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('category');
        $table->integer('total_qty');
        $table->integer('available_qty');
        $table->string('photo')->nullable(); // optional image upload
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
