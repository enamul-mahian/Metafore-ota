<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\SearchFlightRequest;
use App\Services\Flight\FlightSearchService;
use Illuminate\Http\JsonResponse;

class FlightSearchController extends Controller
{
    /**
     * Search available flight offers.
     */
    public function __invoke(
        SearchFlightRequest $request,
        FlightSearchService $flightSearch
    ): JsonResponse {
        return response()->json([
            'data' => [
                'offers' => $flightSearch->search(
                    $request->validated()
                ),
            ],
        ]);
    }
}
