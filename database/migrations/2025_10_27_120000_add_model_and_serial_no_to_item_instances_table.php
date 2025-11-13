<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('item_instances', 'serial_no')) {
                $table->string('serial_no', 100)->nullable()->after('serial_int');
            }

            if (! Schema::hasColumn('item_instances', 'model_no')) {
                $table->string('model_no', 100)->nullable()->after('serial_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            if (Schema::hasColumn('item_instances', 'model_no')) {
                $table->dropColumn('model_no');
            }

            if (Schema::hasColumn('item_instances', 'serial_no')) {
                $table->dropColumn('serial_no');
            }
        });
    }
};

