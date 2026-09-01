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

        $offers = $selectionStore
            ->attachSelectionTokens(
                $request->user()
                    ->getAuthIdentifier(),
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
