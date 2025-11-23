<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('manpower_requests', 'qr_verified_form_path')) {
            Schema::table('manpower_requests', function (Blueprint $table) {
                $table->string('qr_verified_form_path')->nullable()->after('public_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('manpower_requests', 'qr_verified_form_path')) {
            Schema::table('manpower_requests', function (Blueprint $table) {
                $table->dropColumn('qr_verified_form_path');
            });
        }
    }
};
