<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Models\FlightOrderAttempt;
use App\Services\Flight\FlightOrderReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderReconciliationController extends Controller
{
    public function store(
        Request $request,
        string $attemptReference,
        FlightOrderReconciliationService $reconciliationService,
    ): JsonResponse {
        $userId =
            (int) $request
                ->user()
                ->getAuthIdentifier();

        try {
            $result =
                $reconciliationService
                    ->reconcile(
                        $userId,
                        $attemptReference,
                    );
        } catch (ServiceUnavailableHttpException) {
            return $this->unavailableResponse();
        }

        if (! is_array($result)) {
            return $this->notFoundResponse();
        }

        $statusValue =
            $result['status']
            ?? null;

        $providerValue =
            $result['provider']
            ?? null;

        $status =
            is_string(
                $statusValue,
            )
                ? trim(
                    $statusValue,
                )
                : '';

        $provider =
            is_string(
                $providerValue,
            )
                ? strtolower(
                    trim(
                        $providerValue,
                    ),
                )
                : '';

        if (
            ! in_array(
                $status,
                [
                    FlightOrderAttempt::STATUS_PROCESSING,
                    FlightOrderAttempt::STATUS_CREATED,
                    FlightOrderAttempt::STATUS_FAILED,
                ],
                true,
            )
        ) {
            return $this->notFoundResponse();
        }

        if ($provider === '') {
            return $this->notFoundResponse();
        }

        if (strlen($provider) > 64) {
            return $this->notFoundResponse();
        }

        if (
            preg_match(
                '/^[a-z0-9_-]+$/',
                $provider,
            ) !== 1
        ) {
            return $this->notFoundResponse();
        }

        if (
            $status
            === FlightOrderAttempt::STATUS_PROCESSING
        ) {
            return $this->processingResponse(
                $provider,
            );
        }

        return $this->terminalResponse(
            $status,
            $provider,
        );
    }

    private function processingResponse(
        string $provider,
    ): JsonResponse {
        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        FlightOrderAttempt::STATUS_PROCESSING,

                    'provider' =>
                        $provider,
                ],

                'message' =>
                    'Flight order reconciliation is still processing.',
            ], 202),
        );
    }

    private function terminalResponse(
        string $status,
        string $provider,
    ): JsonResponse {
        $message =
            $status
            === FlightOrderAttempt::STATUS_CREATED
                ? 'Flight order reconciliation confirmed the order.'
                : 'Flight order attempt is in a failed state.';

        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        $status,

                    'provider' =>
                        $provider,
                ],

                'message' =>
                    $message,
            ]),
        );
    }

    private function notFoundResponse(): JsonResponse
    {
        return $this->noStore(
            response()->json([
                'data' =>
                    null,

                'message' =>
                    'Flight order reconciliation is unavailable.',
            ], 404),
        );
    }

    private function unavailableResponse(): JsonResponse
    {
        return $this->noStore(
            response()->json([
                'data' =>
                    null,

                'message' =>
                    'Flight order reconciliation is temporarily unavailable.',
            ], 503),
        );
    }

    private function noStore(
        JsonResponse $response,
    ): JsonResponse {
        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }
}