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
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password#123'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'John Rey',
            'middle_name' => 'Laborte',
            'last_name' => 'Magalso',
            'email' => 'user@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

    }
}