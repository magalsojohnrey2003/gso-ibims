<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManpowerToBorrowRequestItems extends Migration
{
    public function up()
    {
        Schema::table('borrow_request_items', function (Blueprint $table) {
            $table->integer('assigned_manpower')->nullable()->default(0)->after('quantity');
            $table->string('manpower_role')->nullable()->after('assigned_manpower');
            $table->text('manpower_notes')->nullable()->after('manpower_role');
            $table->unsignedBigInteger('assigned_by')->nullable()->after('manpower_notes');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
        });
    }

    public function down()
    {
        Schema::table('borrow_request_items', function (Blueprint $table) {
            $table->dropColumn([
                'assigned_manpower',
                'manpower_role',
                'manpower_notes',
                'assigned_by',
                'assigned_at'
            ]);
        });
    }
}
