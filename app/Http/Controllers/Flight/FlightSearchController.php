<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\SearchFlightRequest;
use App\Services\Flight\FlightOfferSelectionStore;
use App\Services\Flight\FlightSearchService;
use Illuminate\Http\JsonResponse;

final class FlightSearchController extends Controller
{
    public function __invoke(
        SearchFlightRequest $request,
        FlightSearchService $flightSearch,
        FlightOfferSelectionStore $selectionStore,
    ): JsonResponse {
        $criteria = $request->validated();

        $offers = $flightSearch->search(
            $criteria
        );

        $user = $request->user();

        /*
         * Guests may browse offers, but selection tokens are bound to a
         * signed-in customer, so guests never receive one.
         */
        $offers = $user === null
            ? array_values(array_map(
                static function (array $offer): array {
                    unset($offer['selection_token']);

                    return $offer;
                },
                $offers,
            ))
            : $selectionStore
                ->attachSelectionTokens(
                    $user->getAuthIdentifier(),
                    $criteria,
                    $offers,
                );

        return response()->json([
            'data' => [
                'offers' => $offers,
            ],
        ]);
    }
}
