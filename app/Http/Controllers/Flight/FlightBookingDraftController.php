<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\ValidateFlightTravelersRequest;
use App\Services\Flight\FlightBookingDraftStore;
use App\Services\Flight\FlightOfferSelectionStore;
use App\Services\Flight\FlightTravelerValidator;
use Illuminate\Http\JsonResponse;

final class FlightBookingDraftController extends Controller
{
    public function __construct(
        private readonly FlightOfferSelectionStore $selectionStore,
        private readonly FlightTravelerValidator $travelerValidator,
        private readonly FlightBookingDraftStore $bookingDraftStore,
    ) {
    }

    public function store(
        ValidateFlightTravelersRequest $request,
    ): JsonResponse {
        $validated = $request->validated();

        $userId = (int) $request
            ->user()
            ->getAuthIdentifier();

        $selection = $this->selectionStore->get(
            $userId,
            $validated['selection_token'],
        );

        if (
            ! is_array($selection)
            || ! isset(
                $selection['criteria'],
                $selection['offer'],
            )
            || ! is_array($selection['criteria'])
            || ! is_array($selection['offer'])
        ) {
            return $this->selectionGoneResponse();
        }

        $criteria = $selection['criteria'];
        $offer = $selection['offer'];
        $travelers = $validated['travelers'];

        $this->travelerValidator->validate($criteria, $travelers);

        $draftToken = $this->bookingDraftStore->put(
            $userId,
            $criteria,
            $offer,
            $travelers,
        );

        $response = response()->json([
            'data' => [
                'status' => 'draft',
                'booking_draft_token' => $draftToken,
                'traveler_count' => count($travelers),
                'expires_in_seconds' =>
                    $this->bookingDraftStore
                        ->expiresInSeconds(),
            ],
        ], 201);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    private function selectionGoneResponse(): JsonResponse
    {
        $response = response()->json([
            'message' => 'available. Please search again.',
        ], 410);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }
}
