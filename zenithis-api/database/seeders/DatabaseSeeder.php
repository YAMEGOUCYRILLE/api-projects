<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $user1 = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        $user2 = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password')
        ]);

        // Create test trips
        for ($i = 1; $i <= 10; $i++) {
            Trip::create([
                'user_id' => $user1->id,
                'title' => "Trip $i - Paris to Lyon",
                'description' => "Description for trip $i",
                'departure' => 'Paris',
                'destination' => 'Lyon',
                'departure_date' => now()->addDays($i),
                'arrival_date' => now()->addDays($i)->addHours(3),
                'price' => rand(30, 100),
                'available_seats' => rand(1, 4),
                'status' => 'active'
            ]);
        }

        // Create trips for user 2
        for ($i = 1; $i <= 5; $i++) {
            Trip::create([
                'user_id' => $user2->id,
                'title' => "Trip $i - Lyon to Marseille",
                'description' => "Description for trip $i",
                'departure' => 'Lyon',
                'destination' => 'Marseille',
                'departure_date' => now()->addDays($i * 2),
                'arrival_date' => now()->addDays($i * 2)->addHours(2),
                'price' => rand(25, 80),
                'available_seats' => rand(1, 3),
                'status' => 'active'
            ]);
        }
    }
}