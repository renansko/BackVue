<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        // Create random user with factory
        User::factory()->create([
            'name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
        ]);
    }
}