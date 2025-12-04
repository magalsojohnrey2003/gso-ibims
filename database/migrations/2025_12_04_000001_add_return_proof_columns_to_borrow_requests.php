<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            $table->string('return_proof_path')->nullable()->after('delivery_reason_details');
            $table->text('return_proof_notes')->nullable()->after('return_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            $table->dropColumn(['return_proof_path', 'return_proof_notes']);
        });
    }
};
