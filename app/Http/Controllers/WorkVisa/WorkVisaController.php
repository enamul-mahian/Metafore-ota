<?php

namespace App\Http\Controllers\WorkVisa;

use App\Http\Controllers\Controller;
use App\Models\WorkVisaDestination;
use Illuminate\Contracts\View\View;

class WorkVisaController extends Controller
{
    public function __invoke(): View
    {
        $destinations = WorkVisaDestination::query()
            ->active()
            ->ordered()
            ->with('country:id,name,iso2')
            ->get();

        return view('work-visa.index', [
            'destinations' => $destinations,
            'jobCategories' => $destinations
                ->flatMap(fn (WorkVisaDestination $destination): array => $destination->jobCategoryList())
                ->unique()
                ->values(),
            'hasFees' => $destinations->contains(fn (WorkVisaDestination $destination): bool => $destination->service_fee !== null),
        ]);
    }
}
