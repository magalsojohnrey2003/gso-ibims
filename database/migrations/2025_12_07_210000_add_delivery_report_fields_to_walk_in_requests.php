<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('walk_in_requests')) {
            return;
        }

        Schema::table('walk_in_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('walk_in_requests', 'delivery_reported_at')) {
                $table->timestamp('delivery_reported_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('walk_in_requests', 'delivery_report_reason')) {
                $table->text('delivery_report_reason')->nullable()->after('delivery_reported_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('walk_in_requests')) {
            return;
        }

        Schema::table('walk_in_requests', function (Blueprint $table) {
            if (Schema::hasColumn('walk_in_requests', 'delivery_report_reason')) {
                $table->dropColumn('delivery_report_reason');
            }
            if (Schema::hasColumn('walk_in_requests', 'delivery_reported_at')) {
                $table->dropColumn('delivery_reported_at');
            }
        });
    }
};
