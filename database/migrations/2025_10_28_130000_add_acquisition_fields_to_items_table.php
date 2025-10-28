<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'acquisition_date')) {
                $table->date('acquisition_date')->nullable()->after('photo');
            }
            if (!Schema::hasColumn('items', 'acquisition_cost')) {
                $table->unsignedBigInteger('acquisition_cost')->nullable()->after('acquisition_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'acquisition_cost')) {
                $table->dropColumn('acquisition_cost');
            }
            if (Schema::hasColumn('items', 'acquisition_date')) {
                $table->dropColumn('acquisition_date');
            }
        });
    }
};
