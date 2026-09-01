<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\SelectFlightOfferRequest;
use App\Services\Flight\FlightOfferSelectionStore;
use Illuminate\Http\JsonResponse;

final class FlightOfferSelectionController extends Controller
{
    public function __invoke(
        SelectFlightOfferRequest $request,
        FlightOfferSelectionStore $selectionStore,
    ): JsonResponse {
        $userId = $request->user()
            ->getAuthIdentifier();

        $selection = $selectionStore->get(
            $userId,
            $request->validated(
                'selection_token'
            ),
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

        return response()->json([
            'data' => [
                'criteria' => $selection['criteria'],
                'offer' => $selection['offer'],
            ],
        ]);
    }
}
