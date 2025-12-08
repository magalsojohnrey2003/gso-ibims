<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            $table->string('reduction_reason')->nullable()->after('approved_quantity');
            $table->json('assigned_personnel_names')->nullable()->after('reduction_reason');
        });
    }

    public function down(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            $table->dropColumn(['assigned_personnel_names', 'reduction_reason']);
        });
    }
};
