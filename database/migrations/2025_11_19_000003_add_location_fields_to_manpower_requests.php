<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            $table->string('municipality', 120)->nullable()->after('location');
            $table->string('barangay', 120)->nullable()->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            $table->dropColumn(['municipality', 'barangay']);
        });
    }
};
