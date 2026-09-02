<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Services\Flight\FlightOrderConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderConfirmationController extends Controller
{
    public function __invoke(
        Request $request,
        string $attemptReference,
        FlightOrderConfirmationService $service,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return $this->notFound();
        }

        try {
            $result =
                $service->retrieve(
                    (int) $user->getAuthIdentifier(),
                    $attemptReference,
                );
        } catch (ServiceUnavailableHttpException) {
            return $this->temporaryUnavailable();
        }

        if (! is_array($result)) {
            return $this->notFound();
        }

        if (
            ($result['status'] ?? null)
                !== 'confirmed'
            || ($result['provider'] ?? null)
                !== 'duffel'
            || ! is_string(
                $result['booking_reference']
                    ?? null,
            )
        ) {
            return $this->notFound();
        }

        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        'confirmed',

                    'provider' =>
                        'duffel',

                    'booking_reference' =>
                        $result[
                            'booking_reference'
                        ],
                ],

                'message' =>
                    'Flight booking confirmation loaded.',
            ]),
        );
    }

    private function notFound(): JsonResponse
    {
        return $this->noStore(
            response()->json(
                [
                    'message' =>
                        'Flight booking confirmation is unavailable.',
                ],
                404,
            ),
        );
    }

    private function temporaryUnavailable(): JsonResponse
    {
        return $this->noStore(
            response()->json(
                [
                    'message' =>
                        'Flight booking confirmation is temporarily unavailable.',
                ],
                503,
            ),
        );
    }

    private function noStore(
        JsonResponse $response,
    ): JsonResponse {
        return $response
            ->header(
                'Cache-Control',
                'private, no-store, max-age=0',
            )
            ->header(
                'Pragma',
                'no-cache',
            );
    }
}