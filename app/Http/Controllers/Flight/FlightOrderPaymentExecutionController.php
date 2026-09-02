<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Services\Flight\FlightOrderPaymentExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderPaymentExecutionController extends Controller
{
    public function __invoke(
        Request $request,
        string $attemptReference,
        FlightOrderPaymentExecutionService $service,
    ): JsonResponse {
        $user = $request->user();

        /*
         * Payment execution authority is route-reference only.
         *
         * Amount, currency, supplier identity, provider and payment type
         * must never be accepted from request body input.
         */
        if (
            $user === null
            || $request->all() !== []
        ) {
            return $this->unavailable();
        }

        try {
            $result =
                $service->execute(
                    (int) $user->getAuthIdentifier(),
                    $attemptReference,
                );
        } catch (ServiceUnavailableHttpException) {
            return $this->temporaryUnavailable();
        }

        if (! is_array($result)) {
            return $this->unavailable();
        }

        $status =
            $result['status']
                ?? null;

        $provider =
            $result['provider']
                ?? null;

        $paymentAttemptReference =
            $result['attempt_reference']
                ?? null;

        if (
            ! is_string($status)
            || ! in_array(
                $status,
                [
                    'processing',
                    'succeeded',
                    'failed',
                ],
                true,
            )
            || $provider !== 'duffel'
            || ! is_string($paymentAttemptReference)
            || strlen($paymentAttemptReference) !== 64
            || preg_match(
                '/^[A-Za-z0-9]+$/',
                $paymentAttemptReference,
            ) !== 1
        ) {
            return $this->unavailable();
        }

        return $this->noStore(
            response()->json(
                [
                    'data' => [
                        'status' =>
                            $status,

                        'provider' =>
                            'duffel',

                        'attempt_reference' =>
                            $paymentAttemptReference,
                    ],
                ],
                $status === 'processing'
                    ? 202
                    : 200,
            ),
        );
    }

    private function unavailable(): JsonResponse
    {
        return $this->noStore(
            response()->json(
                [
                    'message' =>
                        'Flight payment execution is unavailable.',
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
                        'Flight payment execution is temporarily unavailable.',
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