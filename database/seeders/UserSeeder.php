<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            [
                'name' => 'Super Admin',
                'username' => 'super_admin',
                'employee_id' => 'SA001',
                'email' => 'superadmin@gmail.com',
                'password' => bcrypt('12345678'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'employee_id' => 'A001',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('12345678'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rahim',
                'username' => 'rahim',
                'employee_id' => 'rahim',
                'email' => 'rahim@gmail.com',
                'password' => bcrypt('12345678'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Karim',
                'username' => 'karim',
                'employee_id' => 'karim',
                'email' => 'karim@gmail.com',
                'password' => bcrypt('12345678'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Salam',
                'username' => 'salam',
                'employee_id' => 'salam',
                'email' => 'salam@gmail.com',
                'password' => bcrypt('12345678'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        User::factory(1000)->create();
    }
}
