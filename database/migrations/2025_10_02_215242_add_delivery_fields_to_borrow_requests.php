<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            $table->string('delivery_status')->nullable()->default('pending')->after('status');
            $table->timestamp('dispatched_at')->nullable()->after('delivery_status');
            $table->timestamp('delivered_at')->nullable()->after('dispatched_at');
            $table->timestamp('delivery_reported_at')->nullable()->after('delivered_at');
            $table->text('delivery_report_reason')->nullable()->after('delivery_reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_status',
                'dispatched_at',
                'delivered_at',
                'delivery_reported_at',
                'delivery_report_reason'
            ]);
        });
    }
};
