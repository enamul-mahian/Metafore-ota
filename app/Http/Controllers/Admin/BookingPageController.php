<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlightOrderAttempt;
use Illuminate\View\View;

final class BookingPageController extends Controller
{
    public function index(): View
    {
        $bookings = FlightOrderAttempt::query()
            ->with([
                'user:id,name,email',
                'paymentAttempt:id,flight_order_attempt_id,status,amount,currency,resolved_at',
            ])
            ->latest()
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.bookings.index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(FlightOrderAttempt $booking): View
    {
        $booking->load([
            'user:id,name,email',
            'paymentAttempt:id,flight_order_attempt_id,status,amount,currency,resolved_at',
        ]);

        return view('admin.bookings.show', [
            'booking' => $booking,
        ]);
    }
}
