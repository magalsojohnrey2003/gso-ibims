<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('walk_in_request_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('walk_in_request_items', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
    }
};
