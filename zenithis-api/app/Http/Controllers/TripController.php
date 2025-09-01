<?php

namespace App\Http\Controllers;

use App\Http\Requests\TripRequest;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Trip::with('user');

        // Search by date range
        if ($request->has('start_date') || $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Search by specific date
        if ($request->has('date')) {
            $query->byDateRange($request->date, $request->date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by departure or destination
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('departure', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $trips = $query->orderBy('departure_date', 'asc')->paginate($perPage);

        return response()->json($trips);
    }

    public function store(TripRequest $request): JsonResponse
    {
        $trip = Trip::create([
            'user_id' => auth('api')->id(),
            ...$request->validated()
        ]);

        $trip->load('user');

        return response()->json([
            'message' => 'Trip created successfully',
            'trip' => $trip
        ], 201);
    }

    public function show(Trip $trip): JsonResponse
    {
        $trip->load('user');
        return response()->json($trip);
    }

    public function update(TripRequest $request, Trip $trip): JsonResponse
    {
        if (auth('api')->id() !== $trip->user_id) {
            return response()->json(['error' => 'Unauthorized. You can only modify your own trips.'], 403);
        }

        $trip->update($request->validated());
        $trip->load('user');

        return response()->json([
            'message' => 'Trip updated successfully',
            'trip' => $trip
        ]);
    }

    public function destroy(Trip $trip): JsonResponse
    {
        if (auth('api')->id() !== $trip->user_id) {
            return response()->json(['error' => 'Unauthorized. You can only delete your own trips.'], 403);
        }

        $trip->delete();
        return response()->json(['message' => 'Trip deleted successfully']);
    }

    public function myTrips(Request $request): JsonResponse
    {
        $query = Trip::where('user_id', auth('api')->id());

        if ($request->has('start_date') || $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('date')) {
            $query->byDateRange($request->date, $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $trips = $query->orderBy('departure_date', 'asc')->paginate($perPage);

        return response()->json($trips);
    }
}