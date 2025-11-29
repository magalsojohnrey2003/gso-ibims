<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manpower_request_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manpower_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('manpower_role_id')
                ->nullable()
                ->constrained('manpower_roles')
                ->nullOnDelete();
            $table->string('role_name', 150);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->timestamps();
        });

        DB::table('manpower_requests')
            ->select('id', 'manpower_role_id', 'role', 'quantity', 'approved_quantity')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                $now = now();
                foreach ($rows as $row) {
                    $quantity = max((int) ($row->quantity ?? 0), 0);
                    if ($quantity <= 0) {
                        $quantity = 1;
                    }

                    DB::table('manpower_request_roles')->insert([
                        'manpower_request_id' => $row->id,
                        'manpower_role_id' => $row->manpower_role_id,
                        'role_name' => $row->role ?: 'Manpower',
                        'quantity' => $quantity,
                        'approved_quantity' => $row->approved_quantity,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpower_request_roles');
    }
};
