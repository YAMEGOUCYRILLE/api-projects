<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    public function index(): JsonResponse
    {
        $trips = Trip::with('driver')
            ->active()
            ->available()
            ->orderBy('departure_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trips
        ]);
    }

    public function show(Trip $trip): JsonResponse
    {
        $trip->load('driver', 'bookings.user');

        return response()->json([
            'success' => true,
            'data' => $trip
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'departure_city' => 'required|string|max:255',
            'arrival_city' => 'required|string|max:255',
            'departure_time' => 'required|date|after:now',
            'total_seats' => 'required|integer|min:1|max:8',
            'price_per_seat' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $trip = Trip::create([
            'driver_id' => auth()->id(),
            'departure_city' => $request->departure_city,
            'arrival_city' => $request->arrival_city,
            'departure_time' => $request->departure_time,
            'total_seats' => $request->total_seats,
            'available_seats' => $request->total_seats,
            'price_per_seat' => $request->price_per_seat,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trajet créé avec succès',
            'data' => $trip->load('driver')
        ], 201);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Trip::with('driver')->active()->available();

        if ($request->has('departure_city')) {
            $query->where('departure_city', 'like', '%' . $request->departure_city . '%');
        }

        if ($request->has('arrival_city')) {
            $query->where('arrival_city', 'like', '%' . $request->arrival_city . '%');
        }

        if ($request->has('departure_date')) {
            $query->whereDate('departure_time', $request->departure_date);
        }

        $trips = $query->orderBy('departure_time')->get();

        return response()->json([
            'success' => true,
            'data' => $trips
        ]);
    }
}