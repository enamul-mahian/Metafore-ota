<?php

namespace App\Http\Controllers\Tour;

use App\Http\Controllers\Controller;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Contracts\View\View;

class TourController extends Controller
{
    public function __invoke(
        TravelServiceRegistry $registry
    ): View {
        return view('tours.index', [
            'service' => $registry->all()['tours'],
        ]);
    }
}
