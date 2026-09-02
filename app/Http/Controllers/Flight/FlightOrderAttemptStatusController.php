<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Models\FlightOrderAttempt;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FlightOrderAttemptStatusController extends Controller
{
    public function show(
        Request $request,
        string $attemptReference,
        FlightOrderAttemptRecordStore $attemptStore,
    ): JsonResponse {
        $userId =
            (int) $request
                ->user()
                ->getAuthIdentifier();

        $attempt =
            $attemptStore->findForUser(
                $userId,
                $attemptReference,
            );

        if (! $attempt instanceof FlightOrderAttempt) {
            return $this->notFoundResponse();
        }

        $status =
            is_string(
                $attempt->status,
            )
                ? trim(
                    $attempt->status,
                )
                : '';

        $provider =
            is_string(
                $attempt->provider,
            )
                ? trim(
                    $attempt->provider,
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
            || $provider === ''
            || strlen(
                $provider,
            ) > 64
            || preg_match(
                '/^[a-z0-9_-]+$/',
                $provider,
            ) !== 1
        ) {
            return $this->notFoundResponse();
        }

        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        $status,

                    'provider' =>
                        $provider,
                ],

                'message' =>
                    'Flight order attempt status loaded.',
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
                    'Flight order attempt status is unavailable.',
            ], 404),
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