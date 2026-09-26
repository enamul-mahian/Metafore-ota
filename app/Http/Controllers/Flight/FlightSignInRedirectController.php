<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Services\Flight\FlightSearchPrefill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guests who want to book a searched flight are sent here: the auth
 * middleware remembers this URL as the "intended" page, and after login (or
 * registration and email verification) the customer returns to flight search
 * with the same sanitised criteria, which re-runs the search.
 */
final class FlightSignInRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route(
            'flights.index',
            FlightSearchPrefill::fromInput($request->query()),
        );
    }
}
