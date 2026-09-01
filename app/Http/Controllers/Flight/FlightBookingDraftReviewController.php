<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\ReviewFlightBookingDraftRequest;
use App\Services\Flight\FlightBookingDraftStore;
use Illuminate\Http\JsonResponse;

final class FlightBookingDraftReviewController extends Controller
{
    public function __construct(
        private readonly FlightBookingDraftStore $bookingDraftStore,
    ) {
    }

    public function store(
        ReviewFlightBookingDraftRequest $request,
    ): JsonResponse {
        $validated = $request->validated();

        $userId = (int) $request
            ->user()
            ->getAuthIdentifier();

        $draft = $this->bookingDraftStore->get(
            $userId,
            $validated['booking_draft_token'],
        );

        if ($draft === null) {
            return $this->draftGoneResponse();
        }

        $criteria = $draft['criteria'] ?? null;
        $offer = $draft['offer'] ?? null;
        $travelers = $draft['travelers'] ?? null;
        $createdAt = $draft['created_at'] ?? null;

        if (
            ! is_array($criteria)
            || ! is_array($offer)
            || ! is_array($travelers)
            || ! is_string($createdAt)
            || $createdAt === ''
        ) {
            return $this->draftGoneResponse();
        }

        $response = response()->json([
            'data' => [
                'status' => 'draft_review',

                'traveler_count' => count(
                    $travelers
                ),

                'criteria' => [
                    'trip_type' => data_get(
                        $criteria,
                        'trip_type'
                    ),

                    'origin' => data_get(
                        $criteria,
                        'origin'
                    ),

                    'destination' => data_get(
                        $criteria,
                        'destination'
                    ),

                    'departure_date' => data_get(
                        $criteria,
                        'departure_date'
                    ),

                    'return_date' => data_get(
                        $criteria,
                        'return_date'
                    ),

                    'adults' => data_get(
                        $criteria,
                        'adults'
                    ),

                    'children' => data_get(
                        $criteria,
                        'children'
                    ),

                    'infants' => data_get(
                        $criteria,
                        'infants'
                    ),

                    'cabin_class' => data_get(
                        $criteria,
                        'cabin_class'
                    ),
                ],

                'offer' => [
                    'id' => data_get(
                        $offer,
                        'id'
                    ),

                    'provider' => data_get(
                        $offer,
                        'provider'
                    ),

                    'total_amount' => data_get(
                        $offer,
                        'total_amount'
                    ),

                    'currency' => data_get(
                        $offer,
                        'currency'
                    ),

                    'owner' => [
                        'code' => data_get(
                            $offer,
                            'owner.code'
                        ),

                        'name' => data_get(
                            $offer,
                            'owner.name'
                        ),
                    ],

                    'origin' => data_get(
                        $offer,
                        'origin'
                    ),

                    'destination' => data_get(
                        $offer,
                        'destination'
                    ),
                ],

                'created_at' => $createdAt,

                'expires_in_seconds' =>
                    $this->bookingDraftStore
                        ->expiresInSeconds(),
            ],
        ]);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    private function draftGoneResponse(): JsonResponse
    {
        $response = response()->json([
            'message' =>
                'Booking draft is no longer available. Please create a new draft.',
        ], 410);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }
}
