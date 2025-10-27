<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            // serial used for human display/seed — do not enforce unique at base level (property_number is the canonical unique key)
            $table->string('serial')->index(); // e.g. CHR-0001
            $table->enum('status', ['available','borrowed','damaged','under_repair','retired','missing'])
                  ->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_instances');
    }
};
