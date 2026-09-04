<?php

namespace App\Http\Controllers\Visa;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Contracts\View\View;

class VisaController extends Controller
{
    public function __invoke(
        TravelServiceRegistry $registry
    ): View {
        $countries = Country::query()
            ->where('is_active', true)
            ->whereNotNull('iso3')
            ->orderBy('name')
            ->get([
                'name',
                'iso3',
            ]);

        return view('visa.index', [
            'service' => $registry->all()['visa'],
            'countries' => $countries,
        ]);
    }
}
