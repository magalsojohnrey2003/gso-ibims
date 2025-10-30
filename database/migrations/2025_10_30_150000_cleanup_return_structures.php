<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('return_items')) {
            Schema::drop('return_items');
        }

        if (Schema::hasTable('return_requests')) {
            Schema::drop('return_requests');
        }

        Schema::enableForeignKeyConstraints();

        Schema::table('borrow_item_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_item_instances', 'return_condition')) {
                $table->string('return_condition')->default('pending')->after('returned_at');
            }
            if (! Schema::hasColumn('borrow_item_instances', 'condition_updated_at')) {
                $table->timestamp('condition_updated_at')->nullable()->after('return_condition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrow_item_instances', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_item_instances', 'return_condition')) {
                $table->dropColumn('return_condition');
            }
            if (Schema::hasColumn('borrow_item_instances', 'condition_updated_at')) {
                $table->dropColumn('condition_updated_at');
            }
        });
    }
};
