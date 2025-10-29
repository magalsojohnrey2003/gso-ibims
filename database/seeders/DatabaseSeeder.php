<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'User',
            'email' => 'user@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);
    }
}