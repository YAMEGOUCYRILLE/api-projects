<?php
require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

// Configuration
$baseUrl = 'http://localhost:8000/api';
$token = 'YOUR_AUTH_TOKEN'; // Remplace par un vrai token
$tripId = 1; // ID du trajet à tester
$concurrentRequests = 10; // Nombre de requêtes simultanées

$client = new Client();
$promises = [];

echo "🧪 Test de stress - Tentative de surréservation...\n";
echo "Envoi de {$concurrentRequests} requêtes simultanées pour réserver 1 place chacune\n\n";

// Créer des promesses pour des requêtes simultanées
for ($i = 0; $i < $concurrentRequests; $i++) {
    $promises[] = $client->postAsync("{$baseUrl}/bookings", [
        'headers' => [
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'trip_id' => $tripId,
            'seats_booked' => 1
        ]
    ]);
}

// Exécuter toutes les requêtes en parallèle
$responses = Promise\settle($promises)->wait();

$successful = 0;
$failed = 0;

foreach ($responses as $index => $response) {
    if ($response['state'] === 'fulfilled') {
        $statusCode = $response['value']->getStatusCode();
        $body = json_decode($response['value']->getBody(), true);
        
        if ($statusCode === 201 && $body['success']) {
            $successful++;
            echo "✅ Requête {$index}: SUCCÈS - Réservation créée\n";
        } else {
            $failed++;
            echo "❌ Requête {$index}: ÉCHEC - {$body['message']}\n";
        }
    } else {
        $failed++;
        echo "💥 Requête {$index}: ERREUR - {$response['reason']}\n";
    }
}

echo "\n📊 RÉSULTATS:\n";
echo "✅ Réussies: {$successful}\n";
echo "❌ Échouées: {$failed}\n";
echo "\n🎯 Test " . ($successful <= 3 ? "RÉUSSI" : "ÉCHOUÉ") . 
     " (Max 3 réservations attendues pour un trajet de 3 places)\n";