<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\ValidateFlightTravelersRequest;
use App\Services\Flight\FlightOfferSelectionStore;
use App\Services\Flight\FlightTravelerValidator;
use Illuminate\Http\JsonResponse;

final class FlightTravelerValidationController extends Controller
{
    public function __invoke(
        ValidateFlightTravelersRequest $request,
        FlightOfferSelectionStore $selectionStore,
        FlightTravelerValidator $travelerValidator,
    ): JsonResponse {
        $validated = $request->validated();

        $userId = $request->user()
            ->getAuthIdentifier();

        $selection = $selectionStore->get(
            $userId,
            $validated['selection_token'],
        );

        if ($selection === null) {
            return response()->json(
                [
                    'message' => (
                        'This flight offer selection '
                        . 'has expired or is no longer '
                        . 'available. Please search again.'
                    ),
                ],
                410,
            );
        }

        $travelerValidator->validate(
            $selection['criteria'],
            $validated['travelers'],
        );

        return response()->json([
            'data' => [
                'valid' => true,
                'traveler_count' => count(
                    $validated['travelers']
                ),
            ],
        ]);
    }
}
