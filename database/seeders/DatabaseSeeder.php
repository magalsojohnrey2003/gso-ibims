<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run role seeder first
        $this->call(RoleSeeder::class);

        // Create admin user
        $admin = User::factory()->create([
            'role' => 'admin',
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password#123'), 
        ]);
        $admin->assignRole('admin');

        // Create regular user
        $user = User::factory()->create([
            'role' => 'user',
            'first_name' => 'John Rey',
            'middle_name' => 'Laborte',
            'last_name' => 'Magalso',
            'email' => 'user@gmail.com',
            'password' => Hash::make('Magalso#2003'), 
        ]);
        $user->assignRole('user');
    }
}