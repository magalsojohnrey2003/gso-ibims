<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_request_items', 'is_manpower')) {
                $table->boolean('is_manpower')->default(false)->after('quantity');
            }
        });

        Schema::table('borrow_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_request_items', 'manpower_role_id')) {
                $table->foreignId('manpower_role_id')
                    ->nullable()
                    ->after('manpower_role')
                    ->constrained('manpower_roles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrow_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_request_items', 'manpower_role_id')) {
                $table->dropForeign(['manpower_role_id']);
                $table->dropColumn('manpower_role_id');
            }
        });

        Schema::table('borrow_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_request_items', 'is_manpower')) {
                $table->dropColumn('is_manpower');
            }
        });
    }
};
