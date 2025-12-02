<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1️⃣ SP User
        DB::table('s_p_users')->updateOrInsert(
            ['email' => 'sp@helpstrr.com'],
            [
                'first_name' => 'Test SP User',
                'email' => 'sp@helpstrr.com',
                'password' => Hash::make('helpstrr'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2️⃣ Customer
        DB::table('customers')->updateOrInsert(
            ['email' => 'customer@helpstrr.com'],
            [
                'name' => 'Test Customer',
                'email' => 'customer@helpstrr.com',
                'password' => Hash::make('helpstrr'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3️⃣ User
        DB::table('users')->updateOrInsert(
            ['email' => 'user@helpstrr.com'],
            [
                'name' => 'Test User',
                'email' => 'user@helpstrr.com',
                'password' => Hash::make('helpstrr'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
