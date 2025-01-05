<?php

namespace Database\Seeders;

use App\Enums\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('1234'),
            'role' => UserType::SuperAdmin,
            'address' => 'Admin Address',
            'phone' => '1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
