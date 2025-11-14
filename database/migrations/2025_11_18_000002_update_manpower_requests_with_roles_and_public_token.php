<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            $table->unsignedInteger('approved_quantity')->nullable()->after('quantity');
            $table->foreignId('manpower_role_id')
                ->nullable()
                ->after('role')
                ->constrained('manpower_roles')
                ->nullOnDelete();
            $table->string('public_token', 64)->nullable()->unique()->after('status');
        });

        DB::table('manpower_requests')
            ->whereNull('public_token')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $token = (string) Str::uuid();
                    DB::table('manpower_requests')
                        ->where('id', $row->id)
                        ->update(['public_token' => $token]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            $table->dropForeign(['manpower_role_id']);
            $table->dropColumn(['approved_quantity', 'manpower_role_id', 'public_token']);
        });
    }
};
