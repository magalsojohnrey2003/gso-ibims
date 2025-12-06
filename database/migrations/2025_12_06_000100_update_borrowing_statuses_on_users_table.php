<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'borrowing_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('borrowing_status', 20)->default('good')->after('creation_source');
            });
        }

        DB::table('users')
            ->whereNull('borrowing_status')
            ->update(['borrowing_status' => 'good']);

        $statusMap = [
            'in_good_standing' => 'good',
            'under_review' => 'fair',
            'restricted' => 'risk',
            'suspended' => 'risk',
        ];

        foreach ($statusMap as $from => $to) {
            DB::table('users')
                ->where('borrowing_status', $from)
                ->update(['borrowing_status' => $to]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $statusMap = [
            'good' => 'in_good_standing',
            'fair' => 'under_review',
            'risk' => 'restricted',
        ];

        foreach ($statusMap as $from => $to) {
            DB::table('users')
                ->where('borrowing_status', $from)
                ->update(['borrowing_status' => $to]);
        }

        if (Schema::hasColumn('users', 'borrowing_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('borrowing_status');
            });
        }
    }
};
