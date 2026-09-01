<?php

namespace App\Contracts\Flight;

interface FlightOrderProvider
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
    ): array;
}
