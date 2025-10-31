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
            'first_name' => 'TestAdmin',
            'middle_name' => null,
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser1',
            'middle_name' => null,
            'last_name' => 'User1',
            'email' => 'user1@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser2',
            'middle_name' => null,
            'last_name' => 'User2',
            'email' => 'user2@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

                User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser3',
            'middle_name' => null,
            'last_name' => 'User3',
            'email' => 'user3@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser4',
            'middle_name' => null,
            'last_name' => 'User4',
            'email' => 'user4@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

                User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser5',
            'middle_name' => null,
            'last_name' => 'User5',
            'email' => 'user5@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser6',
            'middle_name' => null,
            'last_name' => 'User6',
            'email' => 'user6@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

                User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser7',
            'middle_name' => null,
            'last_name' => 'User7',
            'email' => 'user7@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser8',
            'middle_name' => null,
            'last_name' => 'User8',
            'email' => 'user8@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

                User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser9',
            'middle_name' => null,
            'last_name' => 'User9',
            'email' => 'user9@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

        User::factory()->create([
            'role' => 'user',
            'first_name' => 'TestUser10',
            'middle_name' => null,
            'last_name' => 'User10',
            'email' => 'user10@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);

    }
}