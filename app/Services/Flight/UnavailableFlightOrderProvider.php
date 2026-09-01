<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderProvider;

final class UnavailableFlightOrderProvider implements FlightOrderProvider
{
    /**
     * @param  array<string, mixed>  $trustedConfirmationIntent
     * @return array{
     *     status: string,
     *     live_order_creation: bool,
     *     order_created: bool
     * }
     */
    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        return [
            'status' => 'unavailable',
            'live_order_creation' => false,
            'order_created' => false,
        ];
    }
}
