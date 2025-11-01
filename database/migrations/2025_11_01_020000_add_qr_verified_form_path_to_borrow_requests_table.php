<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_requests', 'qr_verified_form_path')) {
                $table->string('qr_verified_form_path')->nullable()->after('letter_path');
            }
        });

        // Normalize historical statuses.
        DB::table('borrow_requests')
            ->where('status', 'qr_verified')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_requests', 'qr_verified_form_path')) {
                $table->dropColumn('qr_verified_form_path');
            }
        });
    }
};

