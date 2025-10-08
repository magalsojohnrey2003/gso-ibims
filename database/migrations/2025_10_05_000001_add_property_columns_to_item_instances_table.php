<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            if (Schema::hasColumn('item_instances', 'serial')) {
                $table->dropUnique('item_instances_serial_unique');
            }
        });

        Schema::table('item_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('item_instances', 'property_number')) {
                $table->string('property_number')->nullable()->after('item_id');
            }
            if (! Schema::hasColumn('item_instances', 'year_procured')) {
                $table->unsignedSmallInteger('year_procured')->nullable()->after('property_number');
            }
            if (! Schema::hasColumn('item_instances', 'ppe_code')) {
                $table->string('ppe_code', 20)->nullable()->after('year_procured');
            }
            if (! Schema::hasColumn('item_instances', 'serial_int')) {
                $table->unsignedInteger('serial_int')->nullable()->after('serial');
            }
            if (! Schema::hasColumn('item_instances', 'office_code')) {
                $table->string('office_code', 50)->nullable()->after('serial_int');
            }
        });

        Schema::table('item_instances', function (Blueprint $table) {
            $table->unique('property_number', 'item_instances_property_number_unique');
            $table->index('serial', 'item_instances_serial_index');
            $table->index('serial_int', 'item_instances_serial_int_index');
        });
    }

    public function down(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            $table->dropUnique('item_instances_property_number_unique');
            $table->dropIndex('item_instances_serial_index');
            $table->dropIndex('item_instances_serial_int_index');
        });

        Schema::table('item_instances', function (Blueprint $table) {
            if (Schema::hasColumn('item_instances', 'office_code')) {
                $table->dropColumn('office_code');
            }
            if (Schema::hasColumn('item_instances', 'serial_int')) {
                $table->dropColumn('serial_int');
            }
            if (Schema::hasColumn('item_instances', 'ppe_code')) {
                $table->dropColumn('ppe_code');
            }
            if (Schema::hasColumn('item_instances', 'year_procured')) {
                $table->dropColumn('year_procured');
            }
            if (Schema::hasColumn('item_instances', 'property_number')) {
                $table->dropColumn('property_number');
            }
        });

        Schema::table('item_instances', function (Blueprint $table) {
            $table->unique('serial', 'item_instances_serial_unique');
        });
    }
};
