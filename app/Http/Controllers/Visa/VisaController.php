<?php

namespace App\Http\Controllers\Visa;

use App\Http\Controllers\Controller;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Contracts\View\View;

class VisaController extends Controller
{
    public function __invoke(
        TravelServiceRegistry $registry
    ): View {
        return view('visa.index', [
            'service' => $registry->all()['visa'],
        ]);
    }
}
