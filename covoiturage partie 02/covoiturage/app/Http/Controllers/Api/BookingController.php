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

        // Démarrer une transaction avec verrouillage pessimiste
        return DB::transaction(function () use ($request) {
            try {
                // VERROU PESSIMISTE : Verrouiller le trajet pour éviter les conditions de course
                $trip = Trip::lockForUpdate()
                    ->where('id', $request->trip_id)
                    ->where('status', 'active')
                    ->first();

                if (!$trip) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Trajet non trouvé ou inactif'
                    ], 404);
                }

                // Vérification : Le conducteur ne peut pas réserver son propre trajet
                if ($trip->driver_id === auth()->id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous ne pouvez pas réserver votre propre trajet'
                    ], 400);
                }

                // Vérification : Trajet complet
                if ($trip->available_seats === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce trajet est complet, aucune place disponible'
                    ], 400);
                }

                // Vérification : Places insuffisantes
                if ($trip->available_seats < $request->seats_booked) {
                    return response()->json([
                        'success' => false,
                        'message' => "Places insuffisantes disponibles. Il reste seulement {$trip->available_seats} place(s)"
                    ], 400);
                }

                // Vérification : Double réservation (avec verrouillage)
                $existingBooking = Booking::lockForUpdate()
                    ->where('user_id', auth()->id())
                    ->where('trip_id', $request->trip_id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->first();

                if ($existingBooking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous avez déjà une réservation active pour ce trajet'
                    ], 400);
                }

                // Vérification finale : S'assurer que le trajet n'est pas parti
                if ($trip->departure_time <= now()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce trajet est déjà parti ou sur le point de partir'
                    ], 400);
                }

                // CRÉATION ATOMIQUE : Réservation + Mise à jour des places
                $booking = Booking::create([
                    'user_id' => auth()->id(),
                    'trip_id' => $request->trip_id,
                    'seats_booked' => $request->seats_booked,
                    'total_price' => $trip->price_per_seat * $request->seats_booked,
                    'status' => 'confirmed',
                ]);

                // Mise à jour atomique des places disponibles
                $affected = Trip::where('id', $trip->id)
                    ->where('available_seats', '>=', $request->seats_booked)
                    ->update([
                        'available_seats' => DB::raw('available_seats - ' . $request->seats_booked)
                    ]);

                // Vérification de sécurité : La mise à jour a-t-elle réussi ?
                if ($affected === 0) {
                    throw new \Exception('Échec de la mise à jour des places - condition de course détectée');
                }

                // Recharger le trajet mis à jour
                $trip->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'Réservation effectuée avec succès',
                    'data' => $booking->load('trip.driver'),
                    'remaining_seats' => $trip->available_seats
                ], 201);

            } catch (\Illuminate\Database\QueryException $e) {
                // Gestion des erreurs de contraintes de base de données
                if ($e->errorInfo[1] == 1062) { // Duplicate entry
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous avez déjà une réservation pour ce trajet'
                    ], 400);
                }
                
                throw $e;
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la réservation', [
                    'user_id' => auth()->id(),
                    'trip_id' => $request->trip_id,
                    'seats_booked' => $request->seats_booked,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réservation. Veuillez réessayer.',
                    'error_code' => 'BOOKING_FAILED'
                ], 500);
            }
        });
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

        // Transaction avec verrouillage pour éviter les conditions de course
        return DB::transaction(function () use ($booking) {
            try {
                // Verrouiller la réservation et le trajet
                $lockedBooking = Booking::lockForUpdate()
                    ->where('id', $booking->id)
                    ->where('status', '!=', 'cancelled')
                    ->first();

                if (!$lockedBooking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Réservation introuvable ou déjà annulée'
                    ], 404);
                }

                $trip = Trip::lockForUpdate()->find($lockedBooking->trip_id);

                // Vérifier si le trajet n'est pas déjà parti (politique d'annulation)
                if ($trip->departure_time <= now()->addHours(2)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible d\'annuler : le trajet part dans moins de 2 heures'
                    ], 400);
                }

                // ANNULATION ATOMIQUE
                $lockedBooking->update(['status' => 'cancelled']);

                // Remettre les places disponibles de manière atomique
                Trip::where('id', $trip->id)
                    ->update([
                        'available_seats' => DB::raw('available_seats + ' . $lockedBooking->seats_booked)
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Réservation annulée avec succès',
                    'seats_returned' => $lockedBooking->seats_booked
                ]);

            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'annulation', [
                    'booking_id' => $booking->id,
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'annulation. Veuillez réessayer.'
                ], 500);
            }
        });
    }
}