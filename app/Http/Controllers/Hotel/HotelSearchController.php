<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\SearchHotelsRequest;
use App\Services\Hotel\HotelSearchService;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class HotelSearchController extends Controller
{
    public function __invoke(
        SearchHotelsRequest $request,
        TravelServiceRegistry $registry,
        HotelSearchService $searchService,
    ): View {
        $service = $registry->all()['hotels'];

        if (! $service['available']) {
            throw new ServiceUnavailableHttpException(
                60,
                'Hotel service is not configured.'
            );
        }

        $criteria = $request->validated();

        return view('hotels.results', [
            'criteria' => $criteria,
            'hotels' => $searchService->search($criteria),
        ]);
    }
}
