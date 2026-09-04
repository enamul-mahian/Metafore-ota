<?php

namespace App\Http\Controllers\Tour;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tour\SearchToursRequest;
use App\Services\Tour\TourSearchService;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TourSearchController extends Controller
{
    public function __invoke(
        SearchToursRequest $request,
        TravelServiceRegistry $registry,
        TourSearchService $searchService,
    ): View {
        $service = $registry->all()['tours'];

        if (! $service['available']) {
            throw new ServiceUnavailableHttpException(
                60,
                'Tour service is not configured.'
            );
        }

        $criteria = $request->validated();

        return view('tours.results', [
            'criteria' => $criteria,
            'tours' => $searchService->search($criteria),
        ]);
    }
}
