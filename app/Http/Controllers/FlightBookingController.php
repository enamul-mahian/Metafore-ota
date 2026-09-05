<?php

namespace App\Http\Controllers;

use App\Models\FlightOrderAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FlightBookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings =
            FlightOrderAttempt::query()
                ->with('paymentAttempt')
                ->where(
                    'user_id',
                    (int) $request->user()->getAuthIdentifier(),
                )
                ->latest()
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

        return view(
            'bookings.index',
            [
                'bookings' => $bookings,
            ],
        );
    }

    public function show(
        Request $request,
        FlightOrderAttempt $booking,
    ): View {
        abort_unless(
            (int) $booking->user_id ===
                (int) $request->user()->getAuthIdentifier(),
            404,
        );

        $booking->load('paymentAttempt');

        return view(
            'bookings.show',
            [
                'booking' => $booking,
            ],
        );
    }

    public function invoice(
        Request $request,
        FlightOrderAttempt $booking,
    ): View {
        abort_unless(
            (int) $booking->user_id ===
                (int) $request->user()->getAuthIdentifier(),
            404,
        );

        $booking->load('paymentAttempt');

        return view(
            'bookings.invoice',
            [
                'booking' => $booking,
            ],
        );
    }
}
