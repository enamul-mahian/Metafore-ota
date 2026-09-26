<?php

namespace App\Http\Controllers\Visa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visa\CheckVisaRequirementsRequest;
use App\Models\Country;
use App\Services\Travel\TravelServiceRegistry;
use App\Services\Visa\VisaRequirementService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class VisaRequirementController extends Controller
{
    public function __invoke(
        CheckVisaRequirementsRequest $request,
        TravelServiceRegistry $registry,
        VisaRequirementService $requirementService,
    ): View {
        $service = $registry->all()['visa'];

        if (! $service['available']) {
            throw new ServiceUnavailableHttpException(
                60,
                'Visa information service is not configured.'
            );
        }

        $criteria = $request->validated();

        return view('visa.requirements', [
            'criteria' => $criteria,
            'information' => $requirementService->requirements($criteria),
            'countryNames' => Country::query()
                ->whereIn('iso3', [
                    $criteria['nationality'],
                    $criteria['origin_country'],
                    $criteria['destination_country'],
                ])
                ->pluck('name', 'iso3')
                ->all(),
        ]);
    }
}
