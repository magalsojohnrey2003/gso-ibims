<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('item_instances', 'item_instances_serial_unique')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->dropUnique('item_instances_serial_unique');
            });
        }

        Schema::table('item_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('item_instances', 'property_number')) {
                $table->string('property_number')->nullable()->after('item_id');
            }
            if (! Schema::hasColumn('item_instances', 'year_procured')) {
                $table->unsignedSmallInteger('year_procured')->nullable()->after('property_number');
            }
            if (! Schema::hasColumn('item_instances', 'category_code')) {
                $table->string('category_code', 20)->nullable()->after('year_procured')->comment('Optional category-derived code (1-4 chars)');
            }
            if (! Schema::hasColumn('item_instances', 'gla')) {
                $table->string('gla', 4)->nullable()->after('category_code')->comment('GLA segment (1-4 digits)');
            }
            if (! Schema::hasColumn('item_instances', 'serial_int')) {
                $table->unsignedInteger('serial_int')->nullable()->after('serial');
            }
            if (! Schema::hasColumn('item_instances', 'office_code')) {
                $table->string('office_code', 50)->nullable()->after('serial_int');
            }
        });

        if (! $this->indexExists('item_instances', 'item_instances_property_number_unique')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->unique('property_number', 'item_instances_property_number_unique');
            });
        }

        if (! $this->indexExists('item_instances', 'item_instances_serial_index')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->index('serial', 'item_instances_serial_index');
            });
        }

        if (! $this->indexExists('item_instances', 'item_instances_serial_int_index')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->index('serial_int', 'item_instances_serial_int_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('item_instances', 'item_instances_property_number_unique')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->dropUnique('item_instances_property_number_unique');
            });
        }

        if ($this->indexExists('item_instances', 'item_instances_serial_index')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->dropIndex('item_instances_serial_index');
            });
        }

        if ($this->indexExists('item_instances', 'item_instances_serial_int_index')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->dropIndex('item_instances_serial_int_index');
            });
        }

        Schema::table('item_instances', function (Blueprint $table) {
            if (Schema::hasColumn('item_instances', 'office_code')) {
                $table->dropColumn('office_code');
            }
            if (Schema::hasColumn('item_instances', 'serial_int')) {
                $table->dropColumn('serial_int');
            }
            if (Schema::hasColumn('item_instances', 'gla')) {
                $table->dropColumn('gla');
            }
            if (Schema::hasColumn('item_instances', 'category_code')) {
                $table->dropColumn('category_code');
            }
            if (Schema::hasColumn('item_instances', 'year_procured')) {
                $table->dropColumn('year_procured');
            }
            if (Schema::hasColumn('item_instances', 'property_number')) {
                $table->dropColumn('property_number');
            }
        });

        if (! $this->indexExists('item_instances', 'item_instances_serial_unique')) {
            Schema::table('item_instances', function (Blueprint $table) {
                $table->unique('serial', 'item_instances_serial_unique');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $tableName = $connection->getTablePrefix() . $table;

        $result = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$index]);

        return ! empty($result);
    }
};
