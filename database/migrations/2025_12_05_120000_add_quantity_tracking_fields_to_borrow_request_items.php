<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('borrow_request_items', 'requested_quantity')) {
            Schema::table('borrow_request_items', function (Blueprint $table) {
                $table->unsignedInteger('requested_quantity')->nullable()->after('quantity');
            });
        }

        if (! Schema::hasColumn('borrow_request_items', 'received_quantity')) {
            Schema::table('borrow_request_items', function (Blueprint $table) {
                $table->unsignedInteger('received_quantity')->nullable()->after('requested_quantity');
            });
        }

        if (Schema::hasColumn('borrow_request_items', 'requested_quantity')) {
            DB::table('borrow_request_items')
                ->whereNull('requested_quantity')
                ->update(['requested_quantity' => DB::raw('quantity')]);
        }
    }

    public function down(): void
    {
        Schema::table('borrow_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_request_items', 'received_quantity')) {
                $table->dropColumn('received_quantity');
            }
        });

        Schema::table('borrow_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_request_items', 'requested_quantity')) {
                $table->dropColumn('requested_quantity');
            }
        });
    }
};
