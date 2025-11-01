<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_requests', 'time_of_usage')) {
                $table->string('time_of_usage', 50)->nullable()->after('return_date');
            }

            if (! Schema::hasColumn('borrow_requests', 'purpose_office')) {
                $table->string('purpose_office')->nullable()->after('time_of_usage');
            }

            if (! Schema::hasColumn('borrow_requests', 'purpose')) {
                $table->text('purpose')->nullable()->after('purpose_office');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrow_requests', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_requests', 'purpose')) {
                $table->dropColumn('purpose');
            }

            if (Schema::hasColumn('borrow_requests', 'purpose_office')) {
                $table->dropColumn('purpose_office');
            }

            if (Schema::hasColumn('borrow_requests', 'time_of_usage')) {
                $table->dropColumn('time_of_usage');
            }
        });
    }
};

