<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\JobRoleSeeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\TestUsersSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        User::updateOrCreate(
            ['email' => 'admin@admin.com'], // condition to check existing user
            [
                'name' => 'admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
            ]
        );

        //call BookSeeder
        $this->call(
            [
                StateSeeder::class,
                CountrySeeder::class,
                JobRoleSeeder::class,
                TestUsersSeeder::class,
                ShieldSeeder::class,
                CitySeeder::class,
                // New seeders for the helpstrr system
                CategorySeeder::class,
                SubcategorySeeder::class,
                ChefCuisineSeeder::class,
                DietaryPreferenceSeeder::class,
                OptionalFlagSeeder::class,
                ComprehensiveTestDataSeeder::class,
            ]
        );
    }
}
