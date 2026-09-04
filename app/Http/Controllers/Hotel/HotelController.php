<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Contracts\View\View;

class HotelController extends Controller
{
    public function __invoke(
        TravelServiceRegistry $registry
    ): View {
        return view('hotels.index', [
            'service' => $registry->all()['hotels'],
        ]);
    }
}
