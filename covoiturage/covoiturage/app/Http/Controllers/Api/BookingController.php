<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(): JsonResponse
    {
        $bookings = Booking::with(['trip.driver', 'trip'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'seats_booked' => 'required|integer|min:1',
        ]);

        $trip = Trip::find($request->trip_id);

        // Vérifications
        if ($trip->driver_id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas réserver votre propre trajet'
            ], 400);
        }

        if ($trip->available_seats < $request->seats_booked) {
            return response()->json([
                'success' => false,
                'message' => 'Places insuffisantes disponibles'
            ], 400);
        }

        // Vérifier si l'utilisateur a déjà réservé ce trajet
        $existingBooking = Booking::where('user_id', auth()->id())
            ->where('trip_id', $request->trip_id)
            ->first();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà réservé ce trajet'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Créer la réservation
            $booking = Booking::create([
                'user_id' => auth()->id(),
                'trip_id' => $request->trip_id,
                'seats_booked' => $request->seats_booked,
                'total_price' => $trip->price_per_seat * $request->seats_booked,
                'status' => 'confirmed',
            ]);

            // Mettre à jour les places disponibles
            $trip->decrement('available_seats', $request->seats_booked);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réservation effectuée avec succès',
                'data' => $booking->load('trip.driver')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réservation'
            ], 500);
        }
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Réservation déjà annulée'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Annuler la réservation
            $booking->update(['status' => 'cancelled']);

            // Remettre les places disponibles
            $booking->trip->increment('available_seats', $booking->seats_booked);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }
}