<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;

class CovoiturageSeeder extends Seeder
{
    public function run()
    {
        // Créer des utilisateurs de test
        $user1 = User::create([
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '0123456789',
            'password' => Hash::make('password'),
        ]);

        $user2 = User::create([
            'name' => 'Marie Martin',
            'email' => 'marie@example.com',
            'phone' => '0987654321',
            'password' => Hash::make('password'),
        ]);

        // Créer des trajets de test
        Trip::create([
            'driver_id' => $user1->id,
            'departure_city' => 'Paris',
            'arrival_city' => 'Lyon',
            'departure_time' => now()->addDays(1),
            'total_seats' => 3,
            'available_seats' => 3,
            'price_per_seat' => 25.00,
            'description' => 'Trajet confortable, non-fumeur',
        ]);

        Trip::create([
            'driver_id' => $user2->id,
            'departure_city' => 'Marseille',
            'arrival_city' => 'Nice',
            'departure_time' => now()->addDays(2),
            'total_seats' => 2,
            'available_seats' => 2,
            'price_per_seat' => 15.00,
            'description' => 'Départ tôt le matin',
        ]);
    }
}